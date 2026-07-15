<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Feetype extends Admin_Controller
{

    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        if (!$this->rbac->hasPrivilege('fees_type', 'can_view')) {
            access_denied();
        }
        $this->session->set_userdata('top_menu', 'Fees Collection');
        $this->session->set_userdata('sub_menu', 'feetype/index');

        $this->form_validation->set_rules(
            'code', $this->lang->line('fees_code'), array(
                'required',
                array('check_exists', array($this->feetype_model, 'check_exists')),
            )
        );
        $this->form_validation->set_rules('name', $this->lang->line('name'), 'required');
        if ($this->form_validation->run() == false) {

        } else {
            $data = array(
                'type'        => $this->input->post('name'),
                'code'        => $this->input->post('code'),
                'description' => $this->input->post('description'),
            );
            $this->feetype_model->add($data);
            $this->session->set_flashdata('msg', '<div class="alert alert-success text-left">' . $this->lang->line('success_message') . '</div>');
            redirect('admin/feetype/index');
        }
        $feegroup_result     = $this->feetype_model->get();
        $data['feetypeList'] = $feegroup_result;

        $this->load->view('layout/header', $data);
        $this->load->view('admin/feetype/feetypeList', $data);
        $this->load->view('layout/footer', $data);
    }

    public function delete($id)
    {
        if (!$this->rbac->hasPrivilege('fees_type', 'can_delete')) {
            access_denied();
        }

        $this->feetype_model->remove($id);
        redirect('admin/feetype/index');
    }

    public function edit($id)
    {
        if (!$this->rbac->hasPrivilege('fees_type', 'can_edit')) {
            access_denied();
        }
        $this->session->set_userdata('top_menu', 'Fees Collection');
        $this->session->set_userdata('sub_menu', 'feetype/index');
        $data['id']          = $id;
        $feetype             = $this->feetype_model->get($id);
        $data['feetype']     = $feetype;
        $feegroup_result     = $this->feetype_model->get();
        $data['feetypeList'] = $feegroup_result;
        $this->form_validation->set_rules(
            'name', $this->lang->line('name'), array(
                'required',
                array('check_exists', array($this->feetype_model, 'check_exists')),
            )
        );
        $this->form_validation->set_rules('code', $this->lang->line('fees_code'), 'required');
        if ($this->form_validation->run() == false) {
            $this->load->view('layout/header', $data);
            $this->load->view('admin/feetype/feetypeEdit', $data);
            $this->load->view('layout/footer', $data);
        } else {
            $data = array(
                'id'          => $id,
                'type'        => $this->input->post('name'),
                'code'        => $this->input->post('code'),
                'description' => $this->input->post('description'),
            );
            $this->feetype_model->add($data);
            $this->session->set_flashdata('msg', '<div class="alert alert-success text-left">' . $this->lang->line('update_message') . '</div>');
            redirect('admin/feetype/index');
        }
    }

    public function tenantFeetypeList()
    {
        $tenantId = $this->session->userdata('admin_tenant_id');
        if (!$tenantId) {
            show_404();

            return;
        }

        $feetypeList = $this->feetype_model->getTenantScopedFeetypeList((int) $tenantId);
        $this->load->view('admin/feetype/tenant_feetype_list', ['feetypeList' => $feetypeList]);
    }

    public function tenantFeetypeCreate()
    {
        $tenantId = $this->session->userdata('admin_tenant_id');
        if (!$tenantId) {
            show_404();

            return;
        }

        $this->form_validation->set_rules('name', 'Name', 'trim|required|xss_clean');
        $this->form_validation->set_rules('code', 'Code', 'trim|required|xss_clean');
        $this->form_validation->set_rules('nature', 'Nature', 'trim|required|xss_clean');

        if ($this->input->method() === 'post' && $this->form_validation->run() !== false) {
            $newId = $this->feetype_model->tenantScopedInsert('feetype', (int) $tenantId, [
                'type'        => $this->input->post('name'),
                'code'        => $this->input->post('code'),
                'description' => $this->input->post('description'),
                'nature'      => $this->input->post('nature'),
                'is_active'   => 'yes',
            ]);
            $this->load->view('admin/feetype/tenant_feetype_create', ['created' => true, 'id' => $newId]);

            return;
        }

        $this->load->view('admin/feetype/tenant_feetype_create', ['created' => false]);
    }

    public function tenantFeetypeEdit($id)
    {
        $tenantId = $this->session->userdata('admin_tenant_id');
        if (!$tenantId) {
            show_404();

            return;
        }

        $feetype = $this->feetype_model->tenantScopedFind('feetype', (int) $tenantId, (int) $id);
        if (!$feetype) {
            show_404();

            return;
        }

        $this->form_validation->set_rules('name', 'Name', 'trim|required|xss_clean');
        $this->form_validation->set_rules('code', 'Code', 'trim|required|xss_clean');
        $this->form_validation->set_rules('nature', 'Nature', 'trim|required|xss_clean');

        if ($this->input->method() === 'post' && $this->form_validation->run() !== false) {
            $this->feetype_model->tenantScopedUpdate('feetype', (int) $tenantId, (int) $id, [
                'type'        => $this->input->post('name'),
                'code'        => $this->input->post('code'),
                'description' => $this->input->post('description'),
                'nature'      => $this->input->post('nature'),
            ]);
            $feetype = $this->feetype_model->tenantScopedFind('feetype', (int) $tenantId, (int) $id);
            $this->load->view('admin/feetype/tenant_feetype_edit', ['updated' => true, 'feetype' => $feetype]);

            return;
        }

        $this->load->view('admin/feetype/tenant_feetype_edit', ['updated' => false, 'feetype' => $feetype]);
    }

    public function tenantFeetypeDelete($id)
    {
        $tenantId = $this->session->userdata('admin_tenant_id');
        if (!$tenantId) {
            show_404();

            return;
        }

        $deleted = $this->feetype_model->tenantScopedDelete('feetype', (int) $tenantId, (int) $id);
        $this->load->view('admin/feetype/tenant_feetype_delete', ['deleted' => $deleted]);
    }

}
