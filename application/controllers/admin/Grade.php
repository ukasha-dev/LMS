<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Grade extends Admin_Controller
{
    public $exam_type = array();
    public function __construct()
    {
        parent::__construct();
        $this->exam_type = $this->config->item('exam_type');
    }

    public function index()
    {
        if (!$this->rbac->hasPrivilege('marks_grade', 'can_view')) {
            access_denied();
        }
        $this->session->set_userdata('top_menu', 'Examinations');
        $this->session->set_userdata('sub_menu', 'Examinations/grade');
        $data['title']      = 'Add Arade';
        $data['title_list'] = 'Grade Details';
        $listgrade          = $this->grade_model->getGradeDetails();
        $data['examType']   = $this->exam_type;
        $data['listgrade']  = $listgrade;
        $this->form_validation->set_rules('exam_type', $this->lang->line('exam_type'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('name', $this->lang->line('grade_name'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('mark_from', $this->lang->line('percent_upto'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('mark_upto', $this->lang->line('percent_from'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('grade_point', $this->lang->line('grade_point'), 'trim|required|xss_clean');
        if ($this->form_validation->run() == false) {

            $this->load->view('layout/header');
            $this->load->view('admin/grade/creategrade', $data);
            $this->load->view('layout/footer');
        } else {
            $data = array(
                'exam_type'   => $this->input->post('exam_type'),
                'name'        => $this->input->post('name'),
                'mark_from'   => $this->input->post('mark_from'),
                'mark_upto'   => $this->input->post('mark_upto'),
                'point'       => $this->input->post('grade_point'),

                'description' => $this->input->post('description'),
            );
            $this->grade_model->add($data);
            $this->session->set_flashdata('msg', '<div class="alert alert-success text-left">' . $this->lang->line('success_message') . '</div>');
            redirect('admin/grade/index');
        }
    }

    public function edit($id)
    {
        if (!$this->rbac->hasPrivilege('marks_grade', 'can_edit')) {
            access_denied();
        }
        $data['title']      = 'Edit Grade';
        $data['title_list'] = 'Grade Details';
        $listgrade          = $this->grade_model->getGradeDetails();
        $data['examType']   = $this->exam_type;
        $data['listgrade']  = $listgrade;

        $data['id']        = $id;
        $editgrade         = $this->grade_model->get($id);
        $data['editgrade'] = $editgrade;
        $this->form_validation->set_rules('exam_type', $this->lang->line('exam_type'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('name', $this->lang->line('grade_name'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('mark_from', $this->lang->line('percent_upto'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('mark_upto', $this->lang->line('percent_from'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('grade_point', $this->lang->line('grade_point'), 'trim|required|xss_clean');
        if ($this->form_validation->run() == false) {

            $this->load->view('layout/header');
            $this->load->view('admin/grade/editgrade', $data);
            $this->load->view('layout/footer');
        } else {
            $data = array(
                'id'          => $this->input->post('id'),
                'exam_type'   => $this->input->post('exam_type'),
                'name'        => $this->input->post('name'),
                'mark_from'   => $this->input->post('mark_from'),
                'mark_upto'   => $this->input->post('mark_upto'),
                'point'       => $this->input->post('grade_point'),
                'description' => $this->input->post('description'),
            );
            $this->grade_model->add($data);
            $this->session->set_flashdata('msg', '<div class="alert alert-success text-left">' . $this->lang->line('update_message') . '</div>');
            redirect('admin/grade/index');
        }
    }

    public function delete($id)
    {
        if (!$this->rbac->hasPrivilege('marks_grade', 'can_delete')) {
            access_denied();
        }

        $this->grade_model->remove($id);
        redirect('admin/grade/index');
    }

    public function tenantGradeList()
    {
        $tenantId = $this->session->userdata('admin_tenant_id');
        if (!$tenantId) {
            show_404();

            return;
        }

        $gradeList = $this->grade_model->getTenantScopedGradeList((int) $tenantId);
        $this->load->view('admin/grade/tenant_grade_list', ['gradeList' => $gradeList]);
    }

    // The 3 methods below are this migration's first real WRITE cutover --
    // every prior tenant* controller method has been read-only. Each one
    // re-derives the tenant id from the session (never trusts client
    // input for it) and delegates ownership enforcement to the model's
    // tenantScoped* methods, which filter/inject tenant_id on every query.
    // Deliberately new methods, not edits to the legacy index()/edit()/
    // delete() above: those remain completely untouched for every school
    // still on its own per-branch database.

    public function tenantGradeCreate()
    {
        $tenantId = $this->session->userdata('admin_tenant_id');
        if (!$tenantId) {
            show_404();

            return;
        }

        $this->form_validation->set_rules('exam_type', 'Exam Type', 'trim|required|xss_clean');
        $this->form_validation->set_rules('name', 'Grade Name', 'trim|required|xss_clean');
        $this->form_validation->set_rules('mark_from', 'Mark From', 'trim|required|xss_clean');
        $this->form_validation->set_rules('mark_upto', 'Mark Upto', 'trim|required|xss_clean');
        $this->form_validation->set_rules('grade_point', 'Grade Point', 'trim|required|xss_clean');

        if ($this->input->method() === 'post' && $this->form_validation->run() !== false) {
            $newId = $this->grade_model->tenantScopedAdd((int) $tenantId, [
                'exam_type'   => $this->input->post('exam_type'),
                'name'        => $this->input->post('name'),
                'mark_from'   => $this->input->post('mark_from'),
                'mark_upto'   => $this->input->post('mark_upto'),
                'point'       => $this->input->post('grade_point'),
                'description' => $this->input->post('description'),
            ]);
            $this->load->view('admin/grade/tenant_grade_create', ['created' => true, 'id' => $newId]);

            return;
        }

        $this->load->view('admin/grade/tenant_grade_create', ['created' => false]);
    }

    public function tenantGradeEdit($id)
    {
        $tenantId = $this->session->userdata('admin_tenant_id');
        if (!$tenantId) {
            show_404();

            return;
        }

        $grade = $this->grade_model->getTenantScopedGrade((int) $tenantId, (int) $id);
        if (!$grade) {
            show_404();

            return;
        }

        $this->form_validation->set_rules('exam_type', 'Exam Type', 'trim|required|xss_clean');
        $this->form_validation->set_rules('name', 'Grade Name', 'trim|required|xss_clean');
        $this->form_validation->set_rules('mark_from', 'Mark From', 'trim|required|xss_clean');
        $this->form_validation->set_rules('mark_upto', 'Mark Upto', 'trim|required|xss_clean');
        $this->form_validation->set_rules('grade_point', 'Grade Point', 'trim|required|xss_clean');

        if ($this->input->method() === 'post' && $this->form_validation->run() !== false) {
            $this->grade_model->tenantScopedAdd((int) $tenantId, [
                'id'          => (int) $id,
                'exam_type'   => $this->input->post('exam_type'),
                'name'        => $this->input->post('name'),
                'mark_from'   => $this->input->post('mark_from'),
                'mark_upto'   => $this->input->post('mark_upto'),
                'point'       => $this->input->post('grade_point'),
                'description' => $this->input->post('description'),
            ]);
            $grade = $this->grade_model->getTenantScopedGrade((int) $tenantId, (int) $id);
            $this->load->view('admin/grade/tenant_grade_edit', ['updated' => true, 'grade' => $grade]);

            return;
        }

        $this->load->view('admin/grade/tenant_grade_edit', ['updated' => false, 'grade' => $grade]);
    }

    public function tenantGradeDelete($id)
    {
        $tenantId = $this->session->userdata('admin_tenant_id');
        if (!$tenantId) {
            show_404();

            return;
        }

        $deleted = $this->grade_model->tenantScopedDelete((int) $tenantId, (int) $id);
        $this->load->view('admin/grade/tenant_grade_delete', ['deleted' => $deleted]);
    }

}
