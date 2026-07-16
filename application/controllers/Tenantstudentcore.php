<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

// Tenant-safe CORE student CRUD, deliberately kept in its own controller
// rather than added to Student.php -- that file has real, pre-existing,
// unrelated uncommitted work in the working tree and must never be touched.
//
// "Core" here means exactly what the current school_saas schema and this
// pass's scope cover: demographic basics + class/section/session placement,
// with every posted foreign id verified to belong to this tenant before use.
// Sibling linking is supported here specifically BECAUSE it was a known
// cross-tenant IDOR in the legacy code (Student_model::get($sibling_id) is
// unfiltered, so a crafted sibling_id from another tenant would silently
// inherit that tenant's parent_id) -- rather than leave the gap open, the
// safe version is offered here: sibling_id is verified via tenantScopedFind
// before its parent_id is ever read.
// Deliberately still DEFERRED (not attempted here): file uploads (photo/
// parent pics/documents), parent account + student login creation, and
// transport/fee-discount assignment.
class Tenantstudentcore extends Admin_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('student_model', 'class_model', 'section_model', 'category_model'));
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

        return (bool) $this->student_model->tenantScopedFind($table, $tenantId, (int) $value);
    }

    public function tenantStudentCoreCreate()
    {
        $tenantId = $this->tenantId();
        if (!$tenantId) {
            show_404();

            return;
        }

        $this->form_validation->set_rules('firstname', 'First Name', 'trim|required|xss_clean');
        $this->form_validation->set_rules('admission_no', 'Admission No', 'trim|required|xss_clean');
        $this->form_validation->set_rules('class_id', 'Class', 'trim|required|xss_clean');
        $this->form_validation->set_rules('section_id', 'Section', 'trim|required|xss_clean');
        $this->form_validation->set_rules('session_id', 'Session', 'trim|required|xss_clean');

        if ($this->input->method() !== 'post' || $this->form_validation->run() === false) {
            $this->load->view('student/tenant_student_core_create', ['created' => false]);

            return;
        }

        $tenantId          = (int) $tenantId;
        $classId            = (int) $this->input->post('class_id');
        $sectionId          = (int) $this->input->post('section_id');
        $sessionId          = (int) $this->input->post('session_id');
        $categoryId         = $this->input->post('category_id');
        $schoolHouseId      = $this->input->post('school_house_id');
        $hostelRoomId       = $this->input->post('hostel_room_id');
        $siblingId          = $this->input->post('sibling_id');

        if (!$this->verifyFk($tenantId, 'classes', $classId)
            || !$this->verifyFk($tenantId, 'sections', $sectionId)
            || !$this->verifyFk($tenantId, 'sessions', $sessionId)
            || !$this->verifyFk($tenantId, 'categories', $categoryId)
            || !$this->verifyFk($tenantId, 'school_houses', $schoolHouseId)
            || !$this->verifyFk($tenantId, 'hostel_rooms', $hostelRoomId)
            || !$this->verifyFk($tenantId, 'students', $siblingId)
        ) {
            show_404();

            return;
        }

        $insertData = [
            'firstname'       => $this->input->post('firstname'),
            'middlename'      => $this->input->post('middlename'),
            'lastname'        => $this->input->post('lastname'),
            'admission_no'    => $this->input->post('admission_no'),
            'gender'          => $this->input->post('gender'),
            'dob'             => $this->input->post('dob') ?: null,
            'category_id'     => $categoryId ?: null,
            'school_house_id' => $schoolHouseId ?: null,
            'hostel_room_id'  => $hostelRoomId ?: null,
            'is_active'       => 'yes',
        ];

        if ($siblingId) {
            $sibling = $this->student_model->tenantScopedFind('students', $tenantId, (int) $siblingId);
            $insertData['parent_id'] = (int) $sibling['parent_id'];
        }

        $studentId = $this->student_model->tenantScopedInsert('students', $tenantId, $insertData);

        $this->student_model->tenantScopedInsert('student_session', $tenantId, [
            'student_id' => $studentId,
            'class_id'   => $classId,
            'section_id' => $sectionId,
            'session_id' => $sessionId,
        ]);

        $this->load->view('student/tenant_student_core_create', ['created' => true, 'id' => $studentId]);
    }

    public function tenantStudentCoreEdit($id)
    {
        $tenantId = $this->tenantId();
        if (!$tenantId) {
            show_404();

            return;
        }
        $tenantId = (int) $tenantId;

        $student = $this->student_model->tenantScopedFind('students', $tenantId, (int) $id);
        if (!$student) {
            show_404();

            return;
        }

        $this->form_validation->set_rules('firstname', 'First Name', 'trim|required|xss_clean');
        $this->form_validation->set_rules('class_id', 'Class', 'trim|required|xss_clean');
        $this->form_validation->set_rules('section_id', 'Section', 'trim|required|xss_clean');

        if ($this->input->method() !== 'post' || $this->form_validation->run() === false) {
            $session = $this->db->where('student_id', (int) $id)->where('tenant_id', $tenantId)
                ->order_by('id', 'DESC')->limit(1)->get('student_session')->row_array();
            $this->load->view('student/tenant_student_core_edit', ['updated' => false, 'student' => $student, 'session' => $session]);

            return;
        }

        $classId       = (int) $this->input->post('class_id');
        $sectionId     = (int) $this->input->post('section_id');
        $categoryId    = $this->input->post('category_id');
        $schoolHouseId = $this->input->post('school_house_id');
        $hostelRoomId  = $this->input->post('hostel_room_id');
        $siblingId     = $this->input->post('sibling_id');

        if (!$this->verifyFk($tenantId, 'classes', $classId)
            || !$this->verifyFk($tenantId, 'sections', $sectionId)
            || !$this->verifyFk($tenantId, 'categories', $categoryId)
            || !$this->verifyFk($tenantId, 'school_houses', $schoolHouseId)
            || !$this->verifyFk($tenantId, 'hostel_rooms', $hostelRoomId)
            || !$this->verifyFk($tenantId, 'students', $siblingId)
        ) {
            show_404();

            return;
        }

        $updateData = [
            'firstname'       => $this->input->post('firstname'),
            'middlename'      => $this->input->post('middlename'),
            'lastname'        => $this->input->post('lastname'),
            'gender'          => $this->input->post('gender'),
            'dob'             => $this->input->post('dob') ?: null,
            'category_id'     => $categoryId ?: null,
            'school_house_id' => $schoolHouseId ?: null,
            'hostel_room_id'  => $hostelRoomId ?: null,
        ];

        if ($siblingId) {
            $sibling = $this->student_model->tenantScopedFind('students', $tenantId, (int) $siblingId);
            $updateData['parent_id'] = (int) $sibling['parent_id'];
        }

        $this->student_model->tenantScopedUpdate('students', $tenantId, (int) $id, $updateData);

        $existingSession = $this->db->where('student_id', (int) $id)->where('tenant_id', $tenantId)
            ->order_by('id', 'DESC')->limit(1)->get('student_session')->row_array();

        if ($existingSession) {
            $this->student_model->tenantScopedUpdate('student_session', $tenantId, (int) $existingSession['id'], [
                'class_id'   => $classId,
                'section_id' => $sectionId,
            ]);
        } else {
            $sessionId = (int) $this->input->post('session_id');
            if (!$this->verifyFk($tenantId, 'sessions', $sessionId)) {
                show_404();

                return;
            }
            $this->student_model->tenantScopedInsert('student_session', $tenantId, [
                'student_id' => (int) $id,
                'class_id'   => $classId,
                'section_id' => $sectionId,
                'session_id' => $sessionId,
            ]);
        }

        $student = $this->student_model->tenantScopedFind('students', $tenantId, (int) $id);
        $session = $this->db->where('student_id', (int) $id)->where('tenant_id', $tenantId)
            ->order_by('id', 'DESC')->limit(1)->get('student_session')->row_array();
        $this->load->view('student/tenant_student_core_edit', ['updated' => true, 'student' => $student, 'session' => $session]);
    }

    // Deliberately refuses (404) rather than cascading through a linked
    // login: this route never creates a `users` row, so it only knows how
    // to safely remove a student that doesn't have one. Every real migrated
    // student has a real login and is NOT deletable through this route --
    // cascading that safely is a separate, dedicated piece of work.
    public function tenantStudentCoreDelete($id)
    {
        $tenantId = $this->tenantId();
        if (!$tenantId) {
            show_404();

            return;
        }
        $tenantId = (int) $tenantId;

        $student = $this->student_model->tenantScopedFind('students', $tenantId, (int) $id);
        if (!$student) {
            show_404();

            return;
        }

        $hasLogin = (bool) $this->db->where('user_id', (int) $id)->where('role', 'student')
            ->where('tenant_id', $tenantId)->get('users')->row_array();
        if ($hasLogin) {
            show_404();

            return;
        }

        $this->db->where('student_id', (int) $id)->where('tenant_id', $tenantId)->delete('student_session');
        $deleted = $this->student_model->tenantScopedDelete('students', $tenantId, (int) $id);

        $this->load->view('student/tenant_student_core_delete', ['deleted' => $deleted]);
    }

}
