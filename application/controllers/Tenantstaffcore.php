<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

// Tenant-safe CORE staff CRUD, in its own controller (not added to
// admin/Staff.php) so this stays isolated from that file's much larger
// legacy surface while its own structural issues are worked through.
//
// The legacy create()/edit() flow has two real, tenant-shaped bugs that a
// bare port would have inherited:
//  - Staff_model::check_data_exists()/check_email_exists() check
//    employee_id/email uniqueness with NO tenant_id filter at all -- two
//    unrelated schools sharing this table could not both have a staff
//    member with employee_id "EMP001" today. Every uniqueness check here
//    is scoped to tenant_id instead.
//  - staffid_auto_insert's auto-numbering reads Staff_model::lastRecord()
//    (globally last staff row, ORDER BY id DESC, no tenant filter) and
//    increments its numeric suffix -- a second tenant's first hire would
//    continue the first tenant's sequence instead of starting fresh.
//    Auto-numbering IS offered here, but deliberately not as a port of the
//    legacy mechanism: the legacy scheme reads its prefix/digit-count from
//    `sch_settings`, which today only has a row for tenant 25 (5 of 6
//    tenants have none) -- porting it as-is would work for one tenant and
//    404/misbehave for the rest. Instead, employee_id is optional; when
//    omitted, a fixed "STAFF-" prefix + a sequence computed purely from
//    this tenant's own existing `staff` rows is generated, so every
//    tenant's sequence starts at 1 independently regardless of what any
//    other tenant (or a missing settings row) has.
//
// Photo upload uses Tenant_media_storage (application/libraries/
// Tenant_media_storage.php), the same tenant-scoped storage pattern proven
// on Tenantstudentcore -- every tenant's files live under their own
// uploads/tenant_uploads/tenant_<id>/staff_images/ directory.
// Also DEFERRED: document uploads, payroll/leave-type assignment, custom
// fields. staff logins are a non-issue for delete here -- zero rows in
// `users` currently have role='staff' in school_saas (only parent/student
// were ever migrated), so there is no login to orphan; if a later stage
// adds staff logins, this delete route will need the same has-a-login
// guard Tenantstudentcore's delete has.
class Tenantstaffcore extends Admin_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('staff_model'));
        $this->load->library('enc_lib');
        $this->load->library('tenant_media_storage');
    }

    private function tenantId()
    {
        return $this->session->userdata('admin_tenant_id');
    }

    private function verifyFk(int $tenantId, string $table, $value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        return (bool) $this->staff_model->tenantScopedFind($table, $tenantId, (int) $value);
    }

    private function employeeIdTaken(int $tenantId, string $employeeId, int $excludeId = 0): bool
    {
        $this->db->where('tenant_id', $tenantId)->where('employee_id', $employeeId);
        if ($excludeId) {
            $this->db->where('id !=', $excludeId);
        }

        return $this->db->get('staff')->num_rows() > 0;
    }

    private function emailTaken(int $tenantId, string $email, int $excludeId = 0): bool
    {
        $this->db->where('tenant_id', $tenantId)->where('email', $email);
        if ($excludeId) {
            $this->db->where('id !=', $excludeId);
        }

        return $this->db->get('staff')->num_rows() > 0;
    }

    private const AUTO_EMPLOYEE_ID_PREFIX = 'STAFF-';

    // Tenant-scoped sequence: only ever looks at THIS tenant's own staff
    // rows, so it can never continue another tenant's numbering the way
    // Staff_model::lastRecord() does today.
    private function generateEmployeeId(int $tenantId): string
    {
        $lastRow = $this->db->where('tenant_id', $tenantId)
            ->like('employee_id', self::AUTO_EMPLOYEE_ID_PREFIX, 'after')
            ->order_by('id', 'DESC')
            ->limit(1)
            ->get('staff')->row_array();

        $nextNumber = 1;
        if ($lastRow) {
            $nextNumber = ((int) str_replace(self::AUTO_EMPLOYEE_ID_PREFIX, '', $lastRow['employee_id'])) + 1;
        }

        return self::AUTO_EMPLOYEE_ID_PREFIX . str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
    }

    public function tenantStaffCoreCreate()
    {
        $tenantId = $this->tenantId();
        if (!$tenantId) {
            show_404();

            return;
        }
        $tenantId = (int) $tenantId;

        $this->form_validation->set_rules('employee_id', 'Employee Id', 'trim|xss_clean');
        $this->form_validation->set_rules('name', 'Name', 'trim|required|xss_clean');
        $this->form_validation->set_rules('email', 'Email', 'trim|required|valid_email|xss_clean');

        if ($this->input->method() !== 'post' || $this->form_validation->run() === false) {
            $this->load->view('admin/staff/tenant_staff_core_create', ['created' => false]);

            return;
        }

        $employeeId    = $this->input->post('employee_id') ?: $this->generateEmployeeId($tenantId);
        $email         = $this->input->post('email');
        $departmentId  = $this->input->post('department');
        $designationId = $this->input->post('designation');
        $roleId        = $this->input->post('role_id');

        if ($this->employeeIdTaken($tenantId, $employeeId) || $this->emailTaken($tenantId, $email)) {
            $this->load->view('admin/staff/tenant_staff_core_create', ['created' => false, 'duplicate' => true]);

            return;
        }

        if (!$this->verifyFk($tenantId, 'department', $departmentId)
            || !$this->verifyFk($tenantId, 'staff_designation', $designationId)
            || !$this->verifyFk($tenantId, 'roles', $roleId)
        ) {
            show_404();

            return;
        }

        $password    = $this->role->get_random_password(6, 6, false, true, false);
        $insertImage = $this->tenant_media_storage->upload('photo', $tenantId, 'staff_images');

        $staffId = $this->staff_model->tenantScopedInsert('staff', $tenantId, [
            'employee_id' => $employeeId,
            'name'        => $this->input->post('name'),
            'surname'     => $this->input->post('surname'),
            'email'       => $email,
            'password'    => $this->enc_lib->passHashEnc($password),
            'gender'      => $this->input->post('gender'),
            'dob'         => $this->input->post('dob') ?: null,
            'department'  => $departmentId ?: null,
            'designation' => $designationId ?: null,
            'is_active'   => 1,
            'lang_id'     => 0,
            'currency_id' => 0,
            'image'       => $insertImage,
        ]);

        if ($roleId) {
            $this->staff_model->tenantScopedInsert('staff_roles', $tenantId, [
                'staff_id'  => $staffId,
                'role_id'   => (int) $roleId,
                'is_active' => 1,
            ]);
        }

        $this->load->view('admin/staff/tenant_staff_core_create', ['created' => true, 'id' => $staffId, 'employeeId' => $employeeId, 'image' => $insertImage]);
    }

    public function tenantStaffCoreEdit($id)
    {
        $tenantId = $this->tenantId();
        if (!$tenantId) {
            show_404();

            return;
        }
        $tenantId = (int) $tenantId;

        $staff = $this->staff_model->tenantScopedFind('staff', $tenantId, (int) $id);
        if (!$staff) {
            show_404();

            return;
        }

        $this->form_validation->set_rules('name', 'Name', 'trim|required|xss_clean');
        $this->form_validation->set_rules('email', 'Email', 'trim|required|valid_email|xss_clean');

        if ($this->input->method() !== 'post' || $this->form_validation->run() === false) {
            $this->load->view('admin/staff/tenant_staff_core_edit', ['updated' => false, 'staff' => $staff]);

            return;
        }

        $email         = $this->input->post('email');
        $departmentId  = $this->input->post('department');
        $designationId = $this->input->post('designation');

        if ($this->emailTaken($tenantId, $email, (int) $id)) {
            $this->load->view('admin/staff/tenant_staff_core_edit', ['updated' => false, 'staff' => $staff, 'duplicate' => true]);

            return;
        }

        if (!$this->verifyFk($tenantId, 'department', $departmentId)
            || !$this->verifyFk($tenantId, 'staff_designation', $designationId)
        ) {
            show_404();

            return;
        }

        $updateData = [
            'name'        => $this->input->post('name'),
            'surname'     => $this->input->post('surname'),
            'email'       => $email,
            'gender'      => $this->input->post('gender'),
            'dob'         => $this->input->post('dob') ?: null,
            'department'  => $departmentId ?: null,
            'designation' => $designationId ?: null,
        ];

        $newImage = $this->tenant_media_storage->upload('photo', $tenantId, 'staff_images');
        if ($newImage) {
            $this->tenant_media_storage->delete($staff['image']);
            $updateData['image'] = $newImage;
        }

        $this->staff_model->tenantScopedUpdate('staff', $tenantId, (int) $id, $updateData);

        $staff = $this->staff_model->tenantScopedFind('staff', $tenantId, (int) $id);
        $this->load->view('admin/staff/tenant_staff_core_edit', ['updated' => true, 'staff' => $staff]);
    }

    public function tenantStaffCoreDelete($id)
    {
        $tenantId = $this->tenantId();
        if (!$tenantId) {
            show_404();

            return;
        }
        $tenantId = (int) $tenantId;

        $staff = $this->staff_model->tenantScopedFind('staff', $tenantId, (int) $id);
        if (!$staff) {
            show_404();

            return;
        }

        $this->db->where('staff_id', (int) $id)->where('tenant_id', $tenantId)->delete('staff_roles');
        $deleted = $this->staff_model->tenantScopedDelete('staff', $tenantId, (int) $id);

        if ($deleted) {
            $this->tenant_media_storage->delete($staff['image']);
        }

        $this->load->view('admin/staff/tenant_staff_core_delete', ['deleted' => $deleted]);
    }

}
