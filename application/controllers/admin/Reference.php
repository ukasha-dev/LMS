<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Reference extends Admin_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->library('form_validation');
        $this->load->model("reference_model");
    }

    public function index()
    {
        if (!$this->rbac->hasPrivilege('setup_font_office', 'can_view')) {
            access_denied();
        }
        $this->form_validation->set_rules('reference', $this->lang->line('reference'), 'required');

        if ($this->form_validation->run() == false) {
            $data['reference_list'] = $this->reference_model->reference_list();
            $this->load->view('layout/header');
            $this->load->view('admin/frontoffice/referenceview', $data);
            $this->load->view('layout/footer');
        } else {
            $reference = array(
                'reference'   => $this->input->post('reference'),
                'description' => $this->input->post('description'),
            );
            $this->reference_model->add($reference);
            $this->session->set_flashdata('msg', '<div class="alert alert-success">' . $this->lang->line('success_message') . '</div>');
            redirect('admin/reference');
        }
    }

    public function edit($reference_id)
    {
        if (!$this->rbac->hasPrivilege('setup_font_office', 'can_edit')) {
            access_denied();
        }
        $this->form_validation->set_rules('reference', $this->lang->line('reference'), 'required');

        if ($this->form_validation->run() == false) {
            $data['reference_list'] = $this->reference_model->reference_list();
            $data['reference_data'] = $this->reference_model->reference_list($reference_id);
            $this->load->view('layout/header');
            $this->load->view('admin/frontoffice/referenceeditview', $data);
            $this->load->view('layout/footer');
        } else {

            $reference = array(
                'reference'   => $this->input->post('reference'),
                'description' => $this->input->post('description'),
            );
            $this->reference_model->update($reference_id, $reference);
            $this->session->set_flashdata('msg', '<div class="alert alert-success">' . $this->lang->line('update_message') . '</div>');
            redirect('admin/reference');
        }
    }

    public function delete($id)
    {
        if (!$this->rbac->hasPrivilege('setup_font_office', 'can_delete')) {
            access_denied();
        }
        $this->reference_model->delete($id);
        $this->session->set_flashdata('msg', '<div class="alert alert-success">' . $this->lang->line('delete_message') . '</div>');
        redirect('admin/reference');
    }

    public function tenantReferenceList()
    {
        $tenantId = $this->session->userdata('admin_tenant_id');
        if (!$tenantId) {
            show_404();

            return;
        }

        $referenceList = $this->reference_model->tenantScopedList('reference', (int) $tenantId);
        $this->load->view('admin/reference/tenant_reference_list', ['referenceList' => $referenceList]);
    }

    public function tenantReferenceCreate()
    {
        $tenantId = $this->session->userdata('admin_tenant_id');
        if (!$tenantId) {
            show_404();

            return;
        }

        $this->form_validation->set_rules('reference', 'Reference', 'trim|required|xss_clean');

        if ($this->input->method() === 'post' && $this->form_validation->run() !== false) {
            $newId = $this->reference_model->tenantScopedInsert('reference', (int) $tenantId, [
                'reference'   => $this->input->post('reference'),
                'description' => $this->input->post('description'),
            ]);
            $this->load->view('admin/reference/tenant_reference_create', ['created' => true, 'id' => $newId]);

            return;
        }

        $this->load->view('admin/reference/tenant_reference_create', ['created' => false]);
    }

    public function tenantReferenceEdit($id)
    {
        $tenantId = $this->session->userdata('admin_tenant_id');
        if (!$tenantId) {
            show_404();

            return;
        }

        $reference = $this->reference_model->tenantScopedFind('reference', (int) $tenantId, (int) $id);
        if (!$reference) {
            show_404();

            return;
        }

        $this->form_validation->set_rules('reference', 'Reference', 'trim|required|xss_clean');

        if ($this->input->method() === 'post' && $this->form_validation->run() !== false) {
            $this->reference_model->tenantScopedUpdate('reference', (int) $tenantId, (int) $id, [
                'reference'   => $this->input->post('reference'),
                'description' => $this->input->post('description'),
            ]);
            $reference = $this->reference_model->tenantScopedFind('reference', (int) $tenantId, (int) $id);
            $this->load->view('admin/reference/tenant_reference_edit', ['updated' => true, 'reference' => $reference]);

            return;
        }

        $this->load->view('admin/reference/tenant_reference_edit', ['updated' => false, 'reference' => $reference]);
    }

    public function tenantReferenceDelete($id)
    {
        $tenantId = $this->session->userdata('admin_tenant_id');
        if (!$tenantId) {
            show_404();

            return;
        }

        $deleted = $this->reference_model->tenantScopedDelete('reference', (int) $tenantId, (int) $id);
        $this->load->view('admin/reference/tenant_reference_delete', ['deleted' => $deleted]);
    }

}
