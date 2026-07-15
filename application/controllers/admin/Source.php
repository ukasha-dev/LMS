<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Source extends Admin_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->library('form_validation');

        $this->load->model("source_model");
    }

    public function index()
    {
        if (!$this->rbac->hasPrivilege('setup_font_office', 'can_view')) {
            access_denied();
        }
        $this->form_validation->set_rules('source', $this->lang->line('source'), 'required|trim');

        if ($this->form_validation->run() == false) {
            $data['source_list'] = $this->source_model->source_list();
            $this->load->view('layout/header');
            $this->load->view('admin/frontoffice/sourceview', $data);
            $this->load->view('layout/footer');
        } else {

            $source = array(
                'source'      => $this->input->post('source'),
                'description' => $this->input->post('description'),
            );
            $this->source_model->add($source);
            $this->session->set_flashdata('msg', '<div class="alert alert-success">' . $this->lang->line('success_message') . '</div>');
            redirect('admin/source');
        }
    }

    public function edit($source_id)
    {
        if (!$this->rbac->hasPrivilege('setup_font_office', 'can_edit')) {
            access_denied();
        }
        $this->form_validation->set_rules('source', $this->lang->line('source'), 'required');

        if ($this->form_validation->run() == false) {
            $data['source_list'] = $this->source_model->source_list();
            $data['source_data'] = $this->source_model->source_list($source_id);
            $this->load->view('layout/header');
            $this->load->view('admin/frontoffice/sourceeditview', $data);
            $this->load->view('layout/footer');
        } else {
            $source = array(
                'source'      => $this->input->post('source'),
                'description' => $this->input->post('description'),
            );
            $this->source_model->update($source_id, $source);
            $this->session->set_flashdata('msg', '<div class="alert alert-success">' . $this->lang->line('update_message') . '</div>');
            redirect('admin/source');
        }
    }

    public function delete($id)
    {
        if (!$this->rbac->hasPrivilege('setup_font_office', 'can_delete')) {
            access_denied();
        }
        $this->source_model->delete($id);
        $this->session->set_flashdata('msg', '<div class="alert alert-success">' . $this->lang->line('delete_message') . '</div>');
        redirect('admin/source');
    }

    public function tenantSourceList()
    {
        $tenantId = $this->session->userdata('admin_tenant_id');
        if (!$tenantId) {
            show_404();

            return;
        }

        $sourceList = $this->source_model->tenantScopedList('source', (int) $tenantId);
        $this->load->view('admin/source/tenant_source_list', ['sourceList' => $sourceList]);
    }

    public function tenantSourceCreate()
    {
        $tenantId = $this->session->userdata('admin_tenant_id');
        if (!$tenantId) {
            show_404();

            return;
        }

        $this->form_validation->set_rules('source', 'Source', 'trim|required|xss_clean');

        if ($this->input->method() === 'post' && $this->form_validation->run() !== false) {
            $newId = $this->source_model->tenantScopedInsert('source', (int) $tenantId, [
                'source'      => $this->input->post('source'),
                'description' => $this->input->post('description'),
            ]);
            $this->load->view('admin/source/tenant_source_create', ['created' => true, 'id' => $newId]);

            return;
        }

        $this->load->view('admin/source/tenant_source_create', ['created' => false]);
    }

    public function tenantSourceEdit($id)
    {
        $tenantId = $this->session->userdata('admin_tenant_id');
        if (!$tenantId) {
            show_404();

            return;
        }

        $source = $this->source_model->tenantScopedFind('source', (int) $tenantId, (int) $id);
        if (!$source) {
            show_404();

            return;
        }

        $this->form_validation->set_rules('source', 'Source', 'trim|required|xss_clean');

        if ($this->input->method() === 'post' && $this->form_validation->run() !== false) {
            $this->source_model->tenantScopedUpdate('source', (int) $tenantId, (int) $id, [
                'source'      => $this->input->post('source'),
                'description' => $this->input->post('description'),
            ]);
            $source = $this->source_model->tenantScopedFind('source', (int) $tenantId, (int) $id);
            $this->load->view('admin/source/tenant_source_edit', ['updated' => true, 'source' => $source]);

            return;
        }

        $this->load->view('admin/source/tenant_source_edit', ['updated' => false, 'source' => $source]);
    }

    public function tenantSourceDelete($id)
    {
        $tenantId = $this->session->userdata('admin_tenant_id');
        if (!$tenantId) {
            show_404();

            return;
        }

        $deleted = $this->source_model->tenantScopedDelete('source', (int) $tenantId, (int) $id);
        $this->load->view('admin/source/tenant_source_delete', ['deleted' => $deleted]);
    }

}
