<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Exam extends Admin_Controller
{

    public function __construct()
    {
        parent::__construct();
    }

    public function examclasses($id)
    {
        if (!$this->rbac->hasPrivilege('exam', 'can_view')) {
            access_denied();
        }
        $this->session->set_userdata('top_menu', 'Examinations');
        $this->session->set_userdata('sub_menu', 'exam/index');
        $data['title']    = 'list of  Alloted';
        $exam             = $this->exam_model->get($id);
        $data['exam']     = $exam;
        $classsectionList = $this->examschedule_model->getclassandsectionbyexam($id);
        $array            = array();
        foreach ($classsectionList as $key => $value) {
            $s               = array();
            $exam_id         = $value['exam_id'];
            $class_id        = $value['class_id'];
            $section_id      = $value['section_id'];
            $result_prepare  = $this->examresult_model->checkexamresultpreparebyexam($exam_id, $class_id, $section_id);
            $s['exam_id']    = $exam_id;
            $s['class_id']   = $class_id;
            $s['section_id'] = $section_id;
            $s['class']      = $value['class'];
            $s['section']    = $value['section'];
            if ($result_prepare) {
                $s['result_prepare'] = "yes";
            } else {
                $s['result_prepare'] = "no";
            }
            $array[] = $s;
        }
        $data['classsectionList'] = $array;
        $this->load->view('layout/header');
        $this->load->view('admin/exam/examClasses', $data);
        $this->load->view('layout/footer');
    }

    public function index()
    {
        if (!$this->rbac->hasPrivilege('exam', 'can_view')) {
            access_denied();
        }
        $this->session->set_userdata('top_menu', 'Examinations');
        $this->session->set_userdata('sub_menu', 'exam/index');
        $data['title']      = 'Add Exam';
        $data['title_list'] = 'Exam List';
        $this->form_validation->set_rules('name', $this->lang->line('name'), 'trim|required|xss_clean');
        if ($this->form_validation->run() == false) {

        } else {
            $data = array(
                'name' => $this->input->post('name'),
                'note' => $this->input->post('note'),
            );
            $this->exam_model->add($data);
            $this->session->set_flashdata('msg', '<div class="alert alert-success text-left">' . $this->lang->line('success_message') . '</div>');
            redirect('admin/exam/index');
        }
        $exam_result      = $this->exam_model->get();
        $data['examlist'] = $exam_result;
        $this->load->view('layout/header', $data);
        $this->load->view('admin/exam/examList', $data);
        $this->load->view('layout/footer', $data);
    }

    public function view($id)
    {
        if (!$this->rbac->hasPrivilege('exam', 'can_view')) {
            access_denied();
        }
        $data['title'] = 'Exam List';
        $exam          = $this->exam_model->get($id);
        $data['exam']  = $exam;
        $this->load->view('layout/header', $data);
        $this->load->view('exam/examShow', $data);
        $this->load->view('layout/footer', $data);
    }

    public function getByFeecategory()
    {
        $feecategory_id = $this->input->get('feecategory_id');
        $data           = $this->feetype_model->getTypeByFeecategory($feecategory_id);
        echo json_encode($data);
    }

    public function getStudentCategoryFee()
    {
        $type     = $this->input->post('type');
        $class_id = $this->input->post('class_id');
        $data     = $this->exam_model->getTypeByFeecategory($type, $class_id);
        if (empty($data)) {
            $status = 'fail';
        } else {
            $status = 'success';
        }
        $array = array('status' => $status, 'data' => $data);
        echo json_encode($array);
    }

    public function delete($id)
    {
        if (!$this->rbac->hasPrivilege('exam', 'can_delete')) {
            access_denied();
        }
        $data['title'] = 'Exam List';
        $this->exam_model->remove($id);
        redirect('admin/exam/index');
    }

    public function create()
    {
        if (!$this->rbac->hasPrivilege('exam', 'can_add')) {
            access_denied();
        }
        $data['title'] = 'Add Exam';
        $this->form_validation->set_rules('exam', $this->lang->line('exam'), 'trim|required|xss_clean');
        if ($this->form_validation->run() == false) {
            $this->load->view('layout/header', $data);
            $this->load->view('exam/examCreate', $data);
            $this->load->view('layout/footer', $data);
        } else {
            $data = array(
                'exam' => $this->input->post('exam'),
                'note' => $this->input->post('note'),
            );
            $this->exam_model->add($data);
            $this->session->set_flashdata('msg', '<div class="alert alert-success text-left">' . $this->lang->line('success_message') . '</div>');
            redirect('exam/index');
        }
    }

    public function edit($id)
    {
        if (!$this->rbac->hasPrivilege('exam', 'can_edit')) {
            access_denied();
        }
        $data['title']      = 'Edit Exam';
        $data['id']         = $id;
        $exam               = $this->exam_model->get($id);
        $data['exam']       = $exam;
        $data['title_list'] = $this->lang->line('exam_list');
        $exam_result        = $this->exam_model->get();
        $data['examlist']   = $exam_result;
        $this->form_validation->set_rules('name', $this->lang->line('name'), 'trim|required|xss_clean');
        if ($this->form_validation->run() == false) {
            $this->load->view('layout/header', $data);
            $this->load->view('admin/exam/examEdit', $data);
            $this->load->view('layout/footer', $data);
        } else {
            $data = array(
                'id'   => $id,
                'name' => $this->input->post('name'),
                'note' => $this->input->post('note'),
            );
            $this->exam_model->add($data);
            $this->session->set_flashdata('msg', '<div class="alert alert-success text-left">' . $this->lang->line('update_message') . '</div>');
            redirect('admin/exam/index');
        }
    }

    public function tenantExamList()
    {
        $tenantId = $this->session->userdata('admin_tenant_id');
        if (!$tenantId) {
            show_404();

            return;
        }

        $examList = $this->exam_model->tenantScopedList('exams', (int) $tenantId);
        $this->load->view('admin/exam/tenant_exam_list', ['examList' => $examList]);
    }

    // Unlike every LOW-tier controller so far, `exams` has a real foreign
    // key (sesion_id -- yes, that's the actual column name, a pre-existing
    // typo in the schema) to `sessions`. A bare insert with a client-posted
    // session_id would let a tenant-scoped session attach an exam to
    // ANOTHER tenant's session row just by guessing/tampering with the id.
    // Every session_id is verified to belong to this tenant via
    // tenantScopedFind on `sessions` BEFORE it's used, exactly the same
    // ownership-check discipline the data-migration tools' natural-key
    // resolvers used, one layer up in the write path.

    public function tenantExamCreate()
    {
        $tenantId = $this->session->userdata('admin_tenant_id');
        if (!$tenantId) {
            show_404();

            return;
        }

        $this->form_validation->set_rules('name', 'Name', 'trim|required|xss_clean');
        $this->form_validation->set_rules('session_id', 'Session', 'trim|required|xss_clean');

        if ($this->input->method() === 'post' && $this->form_validation->run() !== false) {
            $sessionId = (int) $this->input->post('session_id');
            $session   = $this->session_model->tenantScopedFind('sessions', (int) $tenantId, $sessionId);
            if (!$session) {
                show_404();

                return;
            }

            $newId = $this->exam_model->tenantScopedInsert('exams', (int) $tenantId, [
                'name'      => $this->input->post('name'),
                'note'      => $this->input->post('note'),
                'sesion_id' => $sessionId,
            ]);
            $this->load->view('admin/exam/tenant_exam_create', ['created' => true, 'id' => $newId]);

            return;
        }

        $this->load->view('admin/exam/tenant_exam_create', ['created' => false]);
    }

    public function tenantExamEdit($id)
    {
        $tenantId = $this->session->userdata('admin_tenant_id');
        if (!$tenantId) {
            show_404();

            return;
        }

        $exam = $this->exam_model->tenantScopedFind('exams', (int) $tenantId, (int) $id);
        if (!$exam) {
            show_404();

            return;
        }

        $this->form_validation->set_rules('name', 'Name', 'trim|required|xss_clean');
        $this->form_validation->set_rules('session_id', 'Session', 'trim|required|xss_clean');

        if ($this->input->method() === 'post' && $this->form_validation->run() !== false) {
            $sessionId = (int) $this->input->post('session_id');
            $session   = $this->session_model->tenantScopedFind('sessions', (int) $tenantId, $sessionId);
            if (!$session) {
                show_404();

                return;
            }

            $this->exam_model->tenantScopedUpdate('exams', (int) $tenantId, (int) $id, [
                'name'      => $this->input->post('name'),
                'note'      => $this->input->post('note'),
                'sesion_id' => $sessionId,
            ]);
            $exam = $this->exam_model->tenantScopedFind('exams', (int) $tenantId, (int) $id);
            $this->load->view('admin/exam/tenant_exam_edit', ['updated' => true, 'exam' => $exam]);

            return;
        }

        $this->load->view('admin/exam/tenant_exam_edit', ['updated' => false, 'exam' => $exam]);
    }

    public function tenantExamDelete($id)
    {
        $tenantId = $this->session->userdata('admin_tenant_id');
        if (!$tenantId) {
            show_404();

            return;
        }

        $deleted = $this->exam_model->tenantScopedDelete('exams', (int) $tenantId, (int) $id);
        $this->load->view('admin/exam/tenant_exam_delete', ['deleted' => $deleted]);
    }

    public function examSearch()
    {
        $data['title'] = 'Search exam';
        if ($this->input->server('REQUEST_METHOD') == "POST") {
            $search = $this->input->post('search');
            if ($search == "search_filter") {
                $data['exp_title']  = 'exam Result From ' . $this->input->post('date_from') . " To " . $this->input->post('date_to');
                $date_from          = date('Y-m-d', $this->customlib->datetostrtotime($this->input->post('date_from')));
                $date_to            = date('Y-m-d', $this->customlib->datetostrtotime($this->input->post('date_to')));
                $resultList         = $this->exam_model->search("", $date_from, $date_to);
                $data['resultList'] = $resultList;
            } else {
                $data['exp_title']  = 'exam Result';
                $search_text        = $this->input->post('search_text');
                $resultList         = $this->exam_model->search($search_text, "", "");
                $data['resultList'] = $resultList;
            }
            $this->load->view('layout/header', $data);
            $this->load->view('admin/exam/examSearch', $data);
            $this->load->view('layout/footer', $data);
        } else {
            $this->load->view('layout/header', $data);
            $this->load->view('admin/exam/examSearch', $data);
            $this->load->view('layout/footer', $data);
        }
    }

}
