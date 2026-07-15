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
//    continue the first tenant's sequence instead of starting fresh. Auto-
//    numbering is DEFERRED entirely here (not silently ported broken):
//    this route always requires an explicit, tenant-scoped-unique
//    employee_id.
//
// Also DEFERRED: file uploads (photo/documents), payroll/leave-type
// assignment, custom fields. staff logins are a non-issue for delete here
// -- zero rows in `users` currently have role='staff' in school_saas (only
// parent/student were ever migrated), so there is no login to orphan; if a
// later stage adds staff logins, this delete route will need the same
// has-a-login guard Tenantstudentcore's delete has.
class Tenantstaffcore extends Admin_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('staff_model'));
        $this->load->library('enc_lib');
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

    public function tenantStaffCoreCreate()
    {
        $tenantId = $this->tenantId();
        if (!$tenantId) {
            show_404();

            return;
        }
        $tenantId = (int) $tenantId;

        $this->form_validation->set_rules('employee_id', 'Employee Id', 'trim|required|xss_clean');
        $this->form_validation->set_rules('name', 'Name', 'trim|required|xss_clean');
        $this->form_validation->set_rules('email', 'Email', 'trim|required|valid_email|xss_clean');

        if ($this->input->method() !== 'post' || $this->form_validation->run() === false) {
            $this->load->view('admin/staff/tenant_staff_core_create', ['created' => false]);

            return;
        }

        $employeeId    = $this->input->post('employee_id');
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

        $password = $this->role->get_random_password(6, 6, false, true, false);

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
        ]);

        if ($roleId) {
            $this->staff_model->tenantScopedInsert('staff_roles', $tenantId, [
                'staff_id'  => $staffId,
                'role_id'   => (int) $roleId,
                'is_active' => 1,
            ]);
        }

        $this->load->view('admin/staff/tenant_staff_core_create', ['created' => true, 'id' => $staffId]);
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

        $this->staff_model->tenantScopedUpdate('staff', $tenantId, (int) $id, [
            'name'        => $this->input->post('name'),
            'surname'     => $this->input->post('surname'),
            'email'       => $email,
            'gender'      => $this->input->post('gender'),
            'dob'         => $this->input->post('dob') ?: null,
            'department'  => $departmentId ?: null,
            'designation' => $designationId ?: null,
        ]);

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

        $this->load->view('admin/staff/tenant_staff_core_delete', ['deleted' => $deleted]);
    }

}
