<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Visitors extends Admin_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->library('form_validation');
        $this->load->library('media_storage');
        $this->load->library('tenant_media_storage');
        $this->config->load('front_office');
        $this->meeting_with = $this->config->item('meeting_with');
    }

    public function index()
    {
        if (!$this->rbac->hasPrivilege('visitor_book', 'can_view')) {
            access_denied();
        }
        $this->session->set_userdata('top_menu', 'front_office');
        $this->session->set_userdata('sub_menu', 'admin/visitors');

        $data['visitor_list'] = $this->visitors_model->visitors_list();
        $data['Purpose']      = $this->visitors_model->getPurpose();
        $data['meeting_with'] = $this->meeting_with;
        $data['stafflist']    = $this->staff_model->searchFullText("", 1);
        $data['classlist']    = $this->class_model->get();

        $this->load->view('layout/header');
        $this->load->view('admin/frontoffice/visitorview', $data);
        $this->load->view('layout/footer');
    }

    public function delete()
    {
        if (!$this->rbac->hasPrivilege('visitor_book', 'can_delete')) {
            access_denied();
        }

        $id  = $this->input->post('id');
        $row = $this->visitors_model->visitors_list($id);

        if ($row['image'] != '') {
            $this->media_storage->filedelete($row['image'], "uploads/front_office/visitors/");
        }

        $this->visitors_model->delete($id);
        echo json_encode(array('message' => $this->lang->line('delete_message')));
    }

    public function details($id)
    {
        if (!$this->rbac->hasPrivilege('visitor_book', 'can_view')) {
            access_denied();
        }

        $data['data'] = $this->visitors_model->visitors_list($id);
        $this->load->view('admin/frontoffice/visitormodelview', $data);
    }

    public function download($id)
    {
        $result = $this->visitors_model->visitors_list($id);
        $this->media_storage->filedownload($result['image'], "./uploads/front_office/visitors");
    }

    public function check_default($post_string)
    {
        return $post_string == "" ? false : true;
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

    public function add()
    {
        $this->form_validation->set_rules('meeting_with', $this->lang->line('meeting_with'), 'required');
        $this->form_validation->set_rules('purpose', $this->lang->line('purpose'), 'required');
        $this->form_validation->set_rules('name', $this->lang->line('visitor_name'), 'required');
        $this->form_validation->set_rules('date', $this->lang->line('date'), 'required');
        $this->form_validation->set_rules('file', $this->lang->line('image'), 'callback_handle_upload[file]');
        
        $meeting_with       = $this->input->post('meeting_with');
        if ($meeting_with == 'staff') {
            $this->form_validation->set_rules('staff_id', $this->lang->line('staff'), 'required');                  
        } else if ($meeting_with == 'student') {
            $this->form_validation->set_rules('class_id', $this->lang->line('class'), 'required');   
            $this->form_validation->set_rules('class_section_id', $this->lang->line('section'), 'required');   
            $this->form_validation->set_rules('student_session_id', $this->lang->line('student'), 'required');              
        }
        

        if ($this->form_validation->run() == false) {
            $msg = array(
                'purpose'      => form_error('purpose'),
                'meeting_with' => form_error('meeting_with'),
                'name'         => form_error('name'),
                'date'         => form_error('date'),
                'file'         => form_error('file'),
            );
            
            if ($meeting_with == 'staff') {
                $msg['staff_id'] = form_error('staff_id') ;                   
            } else if ($meeting_with == 'student') {
                $msg['class_id'] = form_error('class_id') ;
                $msg['class_section_id'] = form_error('class_section_id') ;
                $msg['student_session_id'] = form_error('student_session_id') ;               
            }       

            $array = array('status' => 'fail', 'error' => $msg, 'message' => '');
        } else {

            $meeting_with       = $this->input->post('meeting_with');
            $staff_id           = NULL;
            $student_session_id = NULL;

            if ($meeting_with == 'staff') {
                $staff_id = $this->input->post('staff_id');
            } else {
                $student_session_id = $this->input->post('student_session_id');
            }

            $img_name = $this->media_storage->fileupload("file", "./uploads/front_office/visitors/");

            $visitors = array(
                'purpose'            => $this->input->post('purpose'),
                'name'               => $this->input->post('name'),
                'contact'            => $this->input->post('contact'),
                'id_proof'           => $this->input->post('id_proof'),
                'no_of_people'       => $this->input->post('pepples'),
                'date'               => date('Y-m-d', $this->customlib->datetostrtotime($this->input->post('date'))),
                'in_time'            => $this->input->post('time'),
                'out_time'           => $this->input->post('out_time'),
                'note'               => $this->input->post('note'),
                'meeting_with'       => $meeting_with,
                'staff_id'           => $staff_id,
                'student_session_id' => $student_session_id,
                'image'              => $img_name,
            );

            $visitor_id = $this->visitors_model->add($visitors);           

            $msg   = $this->lang->line('success_message');
            $array = array('status' => 'success', 'error' => '', 'message' => $msg);
        }
        echo json_encode($array);
    }

    public function editvisitor()
    {
        $visitorid            = $this->input->post('visitorid');
        $data['Purpose']      = $this->visitors_model->getPurpose();
        $data['visitor_data'] = $this->visitors_model->visitors_list($visitorid);
        $data['meeting_with'] = $this->meeting_with;
        $data['stafflist']    = $this->staff_model->searchFullText("", 1);
        $data['classlist']    = $this->class_model->get();
        $page = $this->load->view('admin/frontoffice/_visitoreditview', $data, true);
        echo json_encode(array('page' => $page));
    }

    public function edit()
    {
        $this->form_validation->set_rules('purpose', $this->lang->line('purpose'), 'required');
        $this->form_validation->set_rules('edit_meeting_with', $this->lang->line('meeting_with'), 'required');
        $this->form_validation->set_rules('name', $this->lang->line('visitor_name'), 'required');
        $this->form_validation->set_rules('file', $this->lang->line('file'), 'callback_handle_upload[file]');
        $this->form_validation->set_rules('date', $this->lang->line('date'), 'required');
        
        $meeting_with       = $this->input->post('edit_meeting_with');     

        if ($meeting_with == 'staff') {
            $this->form_validation->set_rules('edit_staff_id', $this->lang->line('staff'), 'required');   
        } else if ($meeting_with == 'student') {
            $this->form_validation->set_rules('edit_class_id', $this->lang->line('class'), 'required');
            $this->form_validation->set_rules('edit_class_section_id', $this->lang->line('section'), 'required');
            $this->form_validation->set_rules('edit_student_session_id', $this->lang->line('student'), 'required');          
        }
            
        if ($this->form_validation->run() == false) {
            $msg = array(
                'purpose'           => form_error('purpose'),
                'edit_meeting_with' => form_error('edit_meeting_with'),
                'name'              => form_error('name'),
                'date'              => form_error('date'),
                'file'              => form_error('file'),
            );
                
            if ($meeting_with == 'staff') {
                $msg['edit_staff_id'] =  form_error('edit_staff_id');
            } else if ($meeting_with == 'student') {
                $msg['edit_class_id'] =  form_error('edit_class_id');
                $msg['edit_class_section_id'] =  form_error('edit_class_section_id');
                $msg['edit_student_session_id'] =  form_error('edit_student_session_id');                
            }           

            $array = array('status' => 'fail', 'error' => $msg, 'message' => '');
        } else {

            $meeting_with       = $this->input->post('edit_meeting_with');
            $staff_id           = NULL;
            $student_session_id = NULL;

            if ($meeting_with == 'staff') {
                $staff_id = $this->input->post('edit_staff_id');
            } else {
                $student_session_id = $this->input->post('edit_student_session_id');
            }

            $visitors_list = $this->visitors_model->visitors_list($this->input->post('visitor_id'));

            $visitors = array(
                'purpose'            => $this->input->post('purpose'),
                'name'               => $this->input->post('name'),
                'contact'            => $this->input->post('contact'),
                'id_proof'           => $this->input->post('id_proof'),
                'no_of_people'       => $this->input->post('pepples'),
                'date'               => date('Y-m-d', $this->customlib->datetostrtotime($this->input->post('date'))),
                'in_time'            => $this->input->post('time'),
                'out_time'           => $this->input->post('out_time'),
                'note'               => $this->input->post('note'),
                'meeting_with'       => $meeting_with,
                'staff_id'           => $staff_id,
                'student_session_id' => $student_session_id,
            );

            if (isset($_FILES["file"]) && $_FILES['file']['name'] != '' && (!empty($_FILES['file']['name']))) {

                $img_name = $this->media_storage->fileupload("file", "./uploads/front_office/visitors/");
            } else {
                $img_name = $visitors_list['image'];
            }

            $visitors['image'] = $img_name;

            if (isset($_FILES["file"]) && $_FILES['file']['name'] != '' && (!empty($_FILES['file']['name']))) {

                if ($visitors_list['image'] != '') {
                    $this->media_storage->filedelete($visitors_list['image'], "uploads/front_office/visitors/");
                }
            }

            $this->visitors_model->update($this->input->post('visitor_id'), $visitors);         

            $msg   = $this->lang->line('success_message');
            $array = array('status' => 'success', 'error' => '', 'message' => $msg);
        }
        echo json_encode($array);
    }

    public function getstudent()
    {
        $class_id            = $this->input->post('class_id');
        $section_id          = $this->input->post('section_id');
        $data['studentlist'] = $this->visitors_model->getstudent($class_id, $section_id);
        echo json_encode($data);
    }

    public function staffvisitor()
    {
        $this->session->set_userdata('top_menu', 'visitors');
        $userdata             = $this->customlib->getUserData();
        $staffid              = $userdata['id'];
        $data['visitor_list'] = $this->visitors_model->visitorbystaffid($staffid);
        $this->load->view('layout/header');
        $this->load->view('admin/frontoffice/staffvisitorview', $data);
        $this->load->view('layout/footer');
    }

    // The legacy add()/edit() post class_id/class_section_id only to drive
    // an ajax student lookup -- the actually-stored FK is student_session_id
    // alone, so that's the only per-meeting-with FK verified here. `purpose`
    // is stored as free text in the legacy flow too (no FK id, no join
    // anywhere in Visitors_model), so it's accepted as-is, matching real
    // legacy behavior rather than inventing a stricter rule the app itself
    // doesn't enforce.
    public function tenantVisitorCreate()
    {
        $tenantId = $this->session->userdata('admin_tenant_id');
        if (!$tenantId) {
            show_404();

            return;
        }
        $tenantId = (int) $tenantId;

        $this->form_validation->set_rules('purpose', 'Purpose', 'trim|required|xss_clean');
        $this->form_validation->set_rules('name', 'Name', 'trim|required|xss_clean');
        $this->form_validation->set_rules('date', 'Date', 'trim|required|xss_clean');
        $this->form_validation->set_rules('meeting_with', 'Meeting With', 'trim|required|xss_clean');

        if ($this->input->method() !== 'post' || $this->form_validation->run() === false) {
            $this->load->view('admin/frontoffice/tenant_visitor_create', ['created' => false]);

            return;
        }

        $meetingWith      = $this->input->post('meeting_with');
        $staffId          = $meetingWith === 'staff' ? (int) $this->input->post('staff_id') : null;
        $studentSessionId = $meetingWith === 'student' ? (int) $this->input->post('student_session_id') : null;

        if (($staffId && !$this->visitors_model->tenantScopedFind('staff', $tenantId, $staffId))
            || ($studentSessionId && !$this->visitors_model->tenantScopedFind('student_session', $tenantId, $studentSessionId))
        ) {
            show_404();

            return;
        }

        $visitorId = $this->visitors_model->tenantScopedInsert('visitors_book', $tenantId, [
            'purpose'            => $this->input->post('purpose'),
            'name'               => $this->input->post('name'),
            'contact'            => (string) $this->input->post('contact'),
            'id_proof'           => (string) $this->input->post('id_proof'),
            'no_of_people'       => (int) $this->input->post('no_of_people'),
            'date'               => $this->input->post('date'),
            'in_time'            => (string) $this->input->post('in_time'),
            'out_time'           => (string) $this->input->post('out_time'),
            'note'               => (string) $this->input->post('note'),
            'meeting_with'       => $meetingWith,
            'staff_id'           => $staffId,
            'student_session_id' => $studentSessionId,
            'image'              => $this->tenant_media_storage->upload('photo', $tenantId, 'visitors'),
        ]);

        $this->load->view('admin/frontoffice/tenant_visitor_create', ['created' => true, 'id' => $visitorId]);
    }

    public function tenantVisitorEdit($id)
    {
        $tenantId = $this->session->userdata('admin_tenant_id');
        if (!$tenantId) {
            show_404();

            return;
        }
        $tenantId = (int) $tenantId;

        $visitor = $this->visitors_model->tenantScopedFind('visitors_book', $tenantId, (int) $id);
        if (!$visitor) {
            show_404();

            return;
        }

        $this->form_validation->set_rules('purpose', 'Purpose', 'trim|required|xss_clean');
        $this->form_validation->set_rules('name', 'Name', 'trim|required|xss_clean');
        $this->form_validation->set_rules('date', 'Date', 'trim|required|xss_clean');
        $this->form_validation->set_rules('meeting_with', 'Meeting With', 'trim|required|xss_clean');

        if ($this->input->method() !== 'post' || $this->form_validation->run() === false) {
            $this->load->view('admin/frontoffice/tenant_visitor_edit', ['updated' => false, 'visitor' => $visitor]);

            return;
        }

        $meetingWith      = $this->input->post('meeting_with');
        $staffId          = $meetingWith === 'staff' ? (int) $this->input->post('staff_id') : null;
        $studentSessionId = $meetingWith === 'student' ? (int) $this->input->post('student_session_id') : null;

        if (($staffId && !$this->visitors_model->tenantScopedFind('staff', $tenantId, $staffId))
            || ($studentSessionId && !$this->visitors_model->tenantScopedFind('student_session', $tenantId, $studentSessionId))
        ) {
            show_404();

            return;
        }

        $updateData = [
            'purpose'            => $this->input->post('purpose'),
            'name'               => $this->input->post('name'),
            'contact'            => (string) $this->input->post('contact'),
            'id_proof'           => (string) $this->input->post('id_proof'),
            'no_of_people'       => (int) $this->input->post('no_of_people'),
            'date'               => $this->input->post('date'),
            'in_time'            => (string) $this->input->post('in_time'),
            'out_time'           => (string) $this->input->post('out_time'),
            'note'               => (string) $this->input->post('note'),
            'meeting_with'       => $meetingWith,
            'staff_id'           => $staffId,
            'student_session_id' => $studentSessionId,
        ];

        $newImage = $this->tenant_media_storage->upload('photo', $tenantId, 'visitors');
        if ($newImage) {
            $this->tenant_media_storage->delete($visitor['image']);
            $updateData['image'] = $newImage;
        }

        $this->visitors_model->tenantScopedUpdate('visitors_book', $tenantId, (int) $id, $updateData);

        $visitor = $this->visitors_model->tenantScopedFind('visitors_book', $tenantId, (int) $id);
        $this->load->view('admin/frontoffice/tenant_visitor_edit', ['updated' => true, 'visitor' => $visitor]);
    }

    public function tenantVisitorDelete($id)
    {
        $tenantId = $this->session->userdata('admin_tenant_id');
        if (!$tenantId) {
            show_404();

            return;
        }
        $tenantId = (int) $tenantId;

        $visitor = $this->visitors_model->tenantScopedFind('visitors_book', $tenantId, (int) $id);
        if (!$visitor) {
            show_404();

            return;
        }

        $deleted = $this->visitors_model->tenantScopedDelete('visitors_book', $tenantId, (int) $id);
        if ($deleted) {
            $this->tenant_media_storage->delete($visitor['image']);
        }

        $this->load->view('admin/frontoffice/tenant_visitor_delete', ['deleted' => $deleted]);
    }

}
