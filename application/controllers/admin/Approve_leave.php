<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class approve_leave extends Admin_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->library('media_storage');
        $this->load->library('tenant_media_storage');
        $this->sch_setting_detail = $this->setting_model->getSetting();
    }

    public function unauthorized()
    {
        $data = array();
        $this->load->view('layout/header', $data);
        $this->load->view('unauthorized', $data);
        $this->load->view('layout/footer', $data);
    }
 
    public function index()
    {
 
        if (!$this->rbac->hasPrivilege('approve_leave', 'can_view')) {
            access_denied();
        }
        $this->session->set_userdata('top_menu', 'Attendance');
        $this->session->set_userdata('sub_menu', 'Attendance/approve_leave');
        $class               = $this->class_model->get();
        $data['classlist']   = $class;
        $data['class_id']    = $class_id    = '';
        $data['section_id']  = $section_id  = '';
        $data['sch_setting'] = $this->setting_model->getSetting();
        $data['results']     = array();

        if (isset($_POST['class_id']) && $_POST['class_id'] != '') {
            $data['class_id'] = $class_id = $_POST['class_id'];
        } else {
            $listaudit = $this->apply_leave_model->get(null, null, null);
        }

        if (isset($_POST['section_id']) && $_POST['section_id'] != '') {
            $data['section_id'] = $section_id = $_POST['section_id'];
        }
        $this->form_validation->set_rules('class_id', $this->lang->line('class'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('section_id', $this->lang->line('section'), 'trim|required|xss_clean');
        if ($this->form_validation->run() == false) {
 
        } else {
            $listaudit = $this->apply_leave_model->get(null, $class_id, $section_id);
        }

        $data['results'] = $listaudit;

        $this->load->view('layout/header');
        $this->load->view('admin/approve_leave/index', $data);
        $this->load->view('layout/footer');
    }

    public function get_details()
    {
        $userdata = $this->customlib->getUserData();
        $role_id  = $userdata["role_id"];
        $can_edit = 1;

        if (isset($role_id) && ($userdata["role_id"] == 2) && ($userdata["class_teacher"] == "yes")) {
            $myclasssubjects = $this->apply_leave_model->canApproveLeave($userdata["id"], $this->input->post('class_id'), $this->input->post('section_id'));
            $can_edit        = $myclasssubjects;
        }

        if ($can_edit == 0) {

            $data = array('status' => 'fail', 'error' => $this->lang->line('not_authoried'));
        } else {
            $data                 = $this->apply_leave_model->get($_POST['id'], null, null);
            
            $data['leave_status'] = $data['status'];
            $data['from_date']    = date($this->customlib->getSchoolDateFormat(), strtotime($data['from_date']));
            $data['to_date']      = date($this->customlib->getSchoolDateFormat(), strtotime($data['to_date']));
            $data['apply_date']   = date($this->customlib->getSchoolDateFormat(), strtotime($data['apply_date']));
        }
        echo json_encode($data);
    }

    public function add()
    {
        $student_id = '';
        $this->form_validation->set_rules('class', $this->lang->line('class'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('section', $this->lang->line('section'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('apply_date', $this->lang->line('apply_date'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('from_date', $this->lang->line('from_date'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('to_date', $this->lang->line('to_date'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('student', $this->lang->line('student'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('leave_status', $this->lang->line('leave_status'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('userfile', $this->lang->line('file'), 'callback_handle_upload[userfile]');
        if ($this->form_validation->run() == false) {

            $msg = array(
                'class'        => form_error('class'),
                'section'      => form_error('section'),
                'student'      => form_error('student'),
                'apply_date'   => form_error('apply_date'),
                'from_date'    => form_error('from_date'),
                'to_date'      => form_error('to_date'),
                'userfile'     => form_error('userfile'),
                'leave_status' => form_error('leave_status'),
            );

            $array = array('status' => 'fail', 'error' => $msg, 'message' => '');
        } else {          

            $img_name = $this->media_storage->fileupload("userfile", "./uploads/student_leavedocuments/");

            $data = array(
                'apply_date'         => date('Y-m-d', $this->customlib->datetostrtotime($this->input->post('apply_date'))),
                'from_date'          => date('Y-m-d', $this->customlib->datetostrtotime($this->input->post('from_date'))),
                'to_date'            => date('Y-m-d', $this->customlib->datetostrtotime($this->input->post('to_date'))),
                'student_session_id' => $this->input->post('student'),
                'reason'             => $this->input->post('message'),
                'request_type'       => '1',
                'status'             => $this->input->post('leave_status'),
            );
            
            if ($data['status'] != 0) {
                $data['approve_by'] = $this->customlib->getStaffID();
                $data['approve_date'] = date('Y-m-d');
            } 

            if ($this->input->post('leave_id') == '') {
                $data['docs'] = $img_name;
                $leave_id     = $this->apply_leave_model->add($data);
                $data['id']   = $leave_id;
            } else {
                $data['id'] = $this->input->post('leave_id');

                $leave_list = $this->apply_leave_model->get($this->input->post('leave_id'));

                if (isset($_FILES["userfile"]) && $_FILES['userfile']['name'] != '' && (!empty($_FILES['userfile']['name']))) {
                    $img_name = $img_name;
                } else {
                    $img_name = $leave_list['docs'];
                }

                $data['docs'] = $img_name;

                if (isset($_FILES["userfile"]) && $_FILES['userfile']['name'] != '' && (!empty($_FILES['userfile']['name']))) {
                    if ($leave_list['docs'] != '') {
                        $this->media_storage->filedelete($leave_list['docs'], "uploads/student_leavedocuments");
                    }
                }

                $this->apply_leave_model->add($data);
            }

            $array = array('status' => 'success', 'error' => '', 'message' => $this->lang->line('success_message'));
        }

        echo json_encode($array);
    }

    public function searchByClassSection($class_id, $student_id)
    {
        $section_id          = $_REQUEST['section_id'];
        $resultlist          = $this->student_model->searchByClassSection($class_id, $section_id);
        $data['resultlist']  = $resultlist;
        $data['select_id']   = $student_id;
        $data['sch_setting'] = $this->sch_setting_detail;
        $this->load->view('admin/approve_leave/_student_list', $data);
    }

    public function status()
    {
        $userdata = $this->customlib->getUserData();
        $role_id  = $userdata["role_id"];
        $can_edit = 1;

        if (isset($role_id) && ($userdata["role_id"] == 2) && ($userdata["class_teacher"] == "yes")) {
            $myclasssubjects = $this->apply_leave_model->canApproveLeave($userdata["id"], $this->input->post('class_id'), $this->input->post('section_id'));
            $can_edit        = $myclasssubjects;
        }

        if ($can_edit == 0) {
            $msg   = array('leave' => $this->lang->line('not_authoried'));
            $array = array('status' => 0, 'error' => $this->lang->line('not_authoried'));
        } else {
            if ($_POST['status'] == 1) {
                $data['approve_by'] = $this->customlib->getStaffID();
            } else {
                $data['approve_by'] = 0;
            }

            $data['status'] = $_POST['status'];
            $this->db->where('id', $_POST['id']);
            $this->db->update('student_applyleave', $data);
            $msg   = array('leave' => $this->lang->line('success_message'));
            $array = array('status' => 1, 'success' => $this->lang->line('success_message'));
        }
        echo json_encode($array);
    }

    public function remove_leave()
    {
        $userdata = $this->customlib->getUserData();
        $role_id  = $userdata["role_id"];
        $can_edit = 1;

        if (isset($role_id) && ($userdata["role_id"] == 2) && ($userdata["class_teacher"] == "yes")) {
            $myclasssubjects = $this->apply_leave_model->canApproveLeave($userdata["id"], $this->input->post('class_id'), $this->input->post('section_id'));
            $can_edit        = $myclasssubjects;
        }

        if ($can_edit == 0) {
            $array = array('status' => 0, 'error' => $this->lang->line('not_authoried'));
        } else {
            $row = $this->apply_leave_model->get($_POST['id']);
            if ($row['docs'] != '') {
                $this->media_storage->filedelete($row['docs'], "uploads/student_leavedocuments/");
            }

            $this->apply_leave_model->remove_leave($_POST['id']);
            $array = array('status' => 1, 'success' => $this->lang->line('delete_message'));
        }
        echo json_encode($array);
    }

    public function download($id)
    {
        $approve_leave = $this->apply_leave_model->get($id);
        $this->media_storage->filedownload($approve_leave['docs'], "uploads/student_leavedocuments");
    }

    public function handle_upload($str, $var)
    {
        $image_validate = $this->config->item('file_validate');
        $result         = $this->filetype_model->get();
        if (isset($_FILES[$var]) && !empty($_FILES[$var]['name'])) {

            $file_type = $_FILES[$var]['type'];
            $file_size = $_FILES[$var]["size"];
            $file_name = $_FILES[$var]["name"];

            $allowed_extension = array_map('trim', array_map('strtolower', explode(',', $result->file_extension)));
            $allowed_mime_type = array_map('trim', array_map('strtolower', explode(',', $result->file_mime)));
            $ext               = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            if ($files = filesize($_FILES[$var]['tmp_name'])) {

                if (!in_array($file_type, $allowed_mime_type)) {
                    $this->form_validation->set_message('handle_upload', $this->lang->line('file_type_not_allowed'));
                    return false;
                }

                if (!in_array($ext, $allowed_extension) || !in_array($file_type, $allowed_mime_type)) {
                    $this->form_validation->set_message('handle_upload', $this->lang->line('extension_not_allowed'));
                    return false;
                }

                if ($file_size > $result->file_size) {
                    $this->form_validation->set_message('handle_upload', $this->lang->line('file_size_shoud_be_less_than') . number_format($result->file_size / 1048576, 2) . " MB");
                    return false;
                }

            } else {
                $this->form_validation->set_message('handle_upload', $this->lang->line('file_type_extension_error_uploading_image'));
                return false;
            }

            return true;
        }
        return true;

    }

    // Base entity only (student_applyleave). request_type is hardcoded to
    // '1' (self-apply), matching legacy add(). status/approve_by are left
    // at their pending defaults on create -- approving/rejecting is a
    // separate action (tenantApproveLeaveEdit) that ports the real
    // class-teacher/subject-teacher authorization check legacy status()/
    // remove_leave() perform, since that check is core to the approve
    // action itself, not a side-effect to defer.
    public function tenantApproveLeaveCreate()
    {
        $tenantId = $this->session->userdata('admin_tenant_id');
        if (!$tenantId) {
            show_404();

            return;
        }
        $tenantId = (int) $tenantId;

        $this->form_validation->set_rules('student_session_id', 'Student', 'trim|required|xss_clean');
        $this->form_validation->set_rules('apply_date', 'Apply Date', 'trim|required|xss_clean');
        $this->form_validation->set_rules('from_date', 'From Date', 'trim|required|xss_clean');
        $this->form_validation->set_rules('to_date', 'To Date', 'trim|required|xss_clean');
        $this->form_validation->set_rules('reason', 'Reason', 'trim|required|xss_clean');

        if ($this->input->method() !== 'post' || $this->form_validation->run() === false) {
            $this->load->view('admin/approve_leave/tenant_approve_leave_create', ['created' => false]);

            return;
        }

        $studentSessionId = (int) $this->input->post('student_session_id');
        if (!$this->apply_leave_model->tenantScopedFind('student_session', $tenantId, $studentSessionId)) {
            show_404();

            return;
        }

        $id = $this->apply_leave_model->tenantScopedInsert('student_applyleave', $tenantId, [
            'student_session_id' => $studentSessionId,
            'apply_date'         => $this->input->post('apply_date'),
            'from_date'          => $this->input->post('from_date'),
            'to_date'            => $this->input->post('to_date'),
            'reason'             => $this->input->post('reason'),
            'request_type'       => 1,
            'status'             => 0,
            'docs'               => $this->tenant_media_storage->upload('userfile', $tenantId, 'student_leavedocuments') ?: '',
        ]);

        $this->load->view('admin/approve_leave/tenant_approve_leave_create', ['created' => true, 'id' => $id]);
    }

    // Approve/reject action, mirrors legacy status(). Ports canApproveLeave's
    // class-teacher/subject-teacher authorization for role_id==2 staff, but
    // sources class_id/section_id from the tenant-verified student_session
    // row instead of trusting raw POST (legacy trusts POST directly, which
    // is safe only under the old per-tenant-database isolation and would be
    // a cross-tenant class_id/section_id spoofing vector here).
    public function tenantApproveLeaveEdit($id)
    {
        $tenantId = $this->session->userdata('admin_tenant_id');
        if (!$tenantId) {
            show_404();

            return;
        }
        $tenantId = (int) $tenantId;

        $leave = $this->apply_leave_model->tenantScopedFind('student_applyleave', $tenantId, (int) $id);
        if (!$leave) {
            show_404();

            return;
        }

        if ($this->input->method() === 'post') {
            $userdata = $this->customlib->getUserData();
            if (isset($userdata['role_id']) && $userdata['role_id'] == 2 && $userdata['class_teacher'] == 'yes') {
                $session = $this->apply_leave_model->tenantScopedFind('student_session', $tenantId, (int) $leave['student_session_id']);
                if (!$session || !$this->apply_leave_model->canApproveLeave($userdata['id'], $session['class_id'], $session['section_id'])) {
                    show_404();

                    return;
                }
            }

            $status = (int) $this->input->post('status');
            $this->apply_leave_model->tenantScopedUpdate('student_applyleave', $tenantId, (int) $id, [
                'status'       => $status,
                'approve_by'   => $status === 1 ? (int) $this->customlib->getStaffID() : 0,
                'approve_date' => date('Y-m-d'),
            ]);
            $leave = $this->apply_leave_model->tenantScopedFind('student_applyleave', $tenantId, (int) $id);
        }

        $this->load->view('admin/approve_leave/tenant_approve_leave_edit', ['leave' => $leave]);
    }

    public function tenantApproveLeaveDelete($id)
    {
        $tenantId = $this->session->userdata('admin_tenant_id');
        if (!$tenantId) {
            show_404();

            return;
        }
        $tenantId = (int) $tenantId;

        $leave = $this->apply_leave_model->tenantScopedFind('student_applyleave', $tenantId, (int) $id);
        if (!$leave) {
            $this->load->view('admin/approve_leave/tenant_approve_leave_delete', ['deleted' => false]);

            return;
        }

        $userdata = $this->customlib->getUserData();
        if (isset($userdata['role_id']) && $userdata['role_id'] == 2 && $userdata['class_teacher'] == 'yes') {
            $session = $this->apply_leave_model->tenantScopedFind('student_session', $tenantId, (int) $leave['student_session_id']);
            if (!$session || !$this->apply_leave_model->canApproveLeave($userdata['id'], $session['class_id'], $session['section_id'])) {
                show_404();

                return;
            }
        }

        $deleted = $this->apply_leave_model->tenantScopedDelete('student_applyleave', $tenantId, (int) $id);
        if ($deleted && !empty($leave['docs'])) {
            $this->tenant_media_storage->delete($leave['docs']);
        }

        $this->load->view('admin/approve_leave/tenant_approve_leave_delete', ['deleted' => $deleted]);
    }

}
