<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Disable_reason extends Admin_Controller
{

    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        if (!$this->rbac->hasPrivilege('disable_reason', 'can_view')) {
            access_denied();
        }
        $this->session->set_userdata('top_menu', 'Student Information');
        $this->session->set_userdata('sub_menu', 'student/disable_reason');
        $data['results'] = $this->disable_reason_model->get();
        $this->form_validation->set_rules('name', $this->lang->line('disable_reason'), 'trim|required|xss_clean');

        if ($this->form_validation->run() == true) {

       

            $data = array(
                'reason' => $this->input->post('name'),
            );

            if ($id == '') {
                $leave_id = $this->disable_reason_model->add($data);
            } else {
                $data['id'] = $this->input->post('reason_id');

                $this->disable_reason_model->add($data);
            }
            $this->session->set_flashdata('msg', '<div class="alert alert-success text-left">' . $this->lang->line('success_message') . '</div>');
            redirect('admin/disable_reason');
        }

            $this->load->view('layout/header');
            $this->load->view('admin/disable_reason/disable_reason', $data);
            $this->load->view('layout/footer');
    }

    public function edit($id)
    {
        if (!$this->rbac->hasPrivilege('disable_reason', 'can_edit')) {
            access_denied();
        }
        $data['id'] = $id;

        $this->session->set_userdata('top_menu', 'Student Information');
        $this->session->set_userdata('sub_menu', 'student/disable_reason');
        $data['data']    = $this->disable_reason_model->get($id);
        $data['results'] = $this->disable_reason_model->get();
        $data['name']    = $data['data']['reason'];
        $this->form_validation->set_rules('name', $this->lang->line('disable_reason'), 'trim|required|xss_clean');

        if ($this->form_validation->run() == false) {

            $this->load->view('layout/header');
            $this->load->view('admin/disable_reason/disable_reasonedit', $data);
            $this->load->view('layout/footer');
        } else {

            $data = array(
                'reason' => $this->input->post('name'),
            );

            $data['id'] = $id;

            $this->disable_reason_model->add($data);

            $this->session->set_flashdata('msg', '<div class="alert alert-success text-left">' . $this->lang->line('update_message') . '</div>');
            redirect('admin/disable_reason');
        }
    }

    public function get_details($id)
    {
        $data = $this->disable_reason_model->get($id);
        echo json_encode($data);
    }

    public function delete($id)
    {
        if (!$this->rbac->hasPrivilege('disable_reason', 'can_delete')) {
            access_denied();
        }
        $this->disable_reason_model->remove($id);

        $this->session->set_flashdata('message', '<div class="alert alert-success text-left">' . $this->lang->line('delete_message') . '</div>');
        redirect('admin/disable_reason');
    }

    public function tenantDisableReasonList()
    {
        $tenantId = $this->session->userdata('admin_tenant_id');
        if (!$tenantId) {
            show_404();

            return;
        }

        $disableReasonList = $this->disable_reason_model->tenantScopedList('disable_reason', (int) $tenantId);
        $this->load->view('admin/disable_reason/tenant_disable_reason_list', ['disableReasonList' => $disableReasonList]);
    }

    public function tenantDisableReasonCreate()
    {
        $tenantId = $this->session->userdata('admin_tenant_id');
        if (!$tenantId) {
            show_404();

            return;
        }

        $this->form_validation->set_rules('reason', 'Reason', 'trim|required|xss_clean');

        if ($this->input->method() === 'post' && $this->form_validation->run() !== false) {
            $newId = $this->disable_reason_model->tenantScopedInsert('disable_reason', (int) $tenantId, [
                'reason' => $this->input->post('reason'),
            ]);
            $this->load->view('admin/disable_reason/tenant_disable_reason_create', ['created' => true, 'id' => $newId]);

            return;
        }

        $this->load->view('admin/disable_reason/tenant_disable_reason_create', ['created' => false]);
    }

    public function tenantDisableReasonEdit($id)
    {
        $tenantId = $this->session->userdata('admin_tenant_id');
        if (!$tenantId) {
            show_404();

            return;
        }

        $disableReason = $this->disable_reason_model->tenantScopedFind('disable_reason', (int) $tenantId, (int) $id);
        if (!$disableReason) {
            show_404();

            return;
        }

        $this->form_validation->set_rules('reason', 'Reason', 'trim|required|xss_clean');

        if ($this->input->method() === 'post' && $this->form_validation->run() !== false) {
            $this->disable_reason_model->tenantScopedUpdate('disable_reason', (int) $tenantId, (int) $id, [
                'reason' => $this->input->post('reason'),
            ]);
            $disableReason = $this->disable_reason_model->tenantScopedFind('disable_reason', (int) $tenantId, (int) $id);
            $this->load->view('admin/disable_reason/tenant_disable_reason_edit', ['updated' => true, 'disableReason' => $disableReason]);

            return;
        }

        $this->load->view('admin/disable_reason/tenant_disable_reason_edit', ['updated' => false, 'disableReason' => $disableReason]);
    }

    public function tenantDisableReasonDelete($id)
    {
        $tenantId = $this->session->userdata('admin_tenant_id');
        if (!$tenantId) {
            show_404();

            return;
        }

        $deleted = $this->disable_reason_model->tenantScopedDelete('disable_reason', (int) $tenantId, (int) $id);
        $this->load->view('admin/disable_reason/tenant_disable_reason_delete', ['deleted' => $deleted]);
    }

}
