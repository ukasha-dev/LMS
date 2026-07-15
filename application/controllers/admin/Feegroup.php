<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class FeeGroup extends Admin_Controller
{

    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        if (!$this->rbac->hasPrivilege('fees_group', 'can_view')) {
            access_denied();
        }
        $this->session->set_userdata('top_menu', 'Fees Collection');
        $this->session->set_userdata('sub_menu', 'admin/feegroup');

        $this->form_validation->set_rules(
            'name', $this->lang->line('name'), array(
                'required',
                array('check_exists', array($this->feegroup_model, 'check_exists')),
            )
        );
        if ($this->form_validation->run() == false) {

        } else {
            $data = array(
                'name'        => $this->input->post('name'),
                'description' => $this->input->post('description'),
            );
            $this->feegroup_model->add($data);
            $this->session->set_flashdata('msg', '<div class="alert alert-success text-left">' . $this->lang->line('success_message') . '</div>');
            redirect('admin/feegroup/index');
        }
        $feegroup_result      = $this->feegroup_model->get();
        $data['feegroupList'] = $feegroup_result;

        $this->load->view('layout/header', $data);
        $this->load->view('admin/feegroup/feegroupList', $data);
        $this->load->view('layout/footer', $data);
    }

    public function delete($id)
    {
        if (!$this->rbac->hasPrivilege('fees_group', 'can_delete')) {
            access_denied();
        }
        $this->feegroup_model->remove($id);
        redirect('admin/feegroup/index');
    }

    public function edit($id)
    {
        if (!$this->rbac->hasPrivilege('fees_group', 'can_edit')) {
            access_denied();
        }
        $this->session->set_userdata('top_menu', 'Fees Collection');
        $this->session->set_userdata('sub_menu', 'admin/feegroup');
        $data['id']           = $id;
        $feegroup             = $this->feegroup_model->get($id);
        $data['feegroup']     = $feegroup;
        $feegroup_result      = $this->feegroup_model->get();
        $data['feegroupList'] = $feegroup_result;
        $this->form_validation->set_rules(
            'name', $this->lang->line('name'), array(
                'required',
                array('check_exists', array($this->feegroup_model, 'check_exists')),
            )
        );

        if ($this->form_validation->run() == false) {
            $this->load->view('layout/header', $data);
            $this->load->view('admin/feegroup/feegroupEdit', $data);
            $this->load->view('layout/footer', $data);
        } else {
            $data = array(
                'id'          => $id,
                'name'        => $this->input->post('name'),
                'description' => $this->input->post('description'),
            );
            $this->feegroup_model->add($data);
            $this->session->set_flashdata('msg', '<div class="alert alert-success text-left">' . $this->lang->line('success_message') . '</div>');
            redirect('admin/feegroup/index');
        }
    }

    public function tenantFeegroupList()
    {
        $tenantId = $this->session->userdata('admin_tenant_id');
        if (!$tenantId) {
            show_404();

            return;
        }

        $feegroupList = $this->feegroup_model->getTenantScopedFeegroupList((int) $tenantId);
        $this->load->view('admin/feegroup/tenant_feegroup_list', ['feegroupList' => $feegroupList]);
    }

    public function tenantFeeGroupFeetypeList()
    {
        $tenantId = $this->session->userdata('admin_tenant_id');
        if (!$tenantId) {
            show_404();

            return;
        }

        $feeGroupFeetypeList = $this->feegrouptype_model->getTenantScopedFeeGroupTypeList((int) $tenantId);
        $this->load->view('admin/feegroup/tenant_feegroup_feetype_list', ['feeGroupFeetypeList' => $feeGroupFeetypeList]);
    }

    public function tenantFeegroupCreate()
    {
        $tenantId = $this->session->userdata('admin_tenant_id');
        if (!$tenantId) {
            show_404();

            return;
        }

        $this->form_validation->set_rules('name', 'Name', 'trim|required|xss_clean');
        $this->form_validation->set_rules('nature', 'Nature', 'trim|required|xss_clean');

        if ($this->input->method() === 'post' && $this->form_validation->run() !== false) {
            $newId = $this->feegroup_model->tenantScopedInsert('fee_groups', (int) $tenantId, [
                'name'        => $this->input->post('name'),
                'description' => $this->input->post('description'),
                'nature'      => $this->input->post('nature'),
                'is_active'   => 'yes',
            ]);
            $this->load->view('admin/feegroup/tenant_feegroup_create', ['created' => true, 'id' => $newId]);

            return;
        }

        $this->load->view('admin/feegroup/tenant_feegroup_create', ['created' => false]);
    }

    public function tenantFeegroupEdit($id)
    {
        $tenantId = $this->session->userdata('admin_tenant_id');
        if (!$tenantId) {
            show_404();

            return;
        }

        $feegroup = $this->feegroup_model->tenantScopedFind('fee_groups', (int) $tenantId, (int) $id);
        if (!$feegroup) {
            show_404();

            return;
        }

        $this->form_validation->set_rules('name', 'Name', 'trim|required|xss_clean');
        $this->form_validation->set_rules('nature', 'Nature', 'trim|required|xss_clean');

        if ($this->input->method() === 'post' && $this->form_validation->run() !== false) {
            $this->feegroup_model->tenantScopedUpdate('fee_groups', (int) $tenantId, (int) $id, [
                'name'        => $this->input->post('name'),
                'description' => $this->input->post('description'),
                'nature'      => $this->input->post('nature'),
            ]);
            $feegroup = $this->feegroup_model->tenantScopedFind('fee_groups', (int) $tenantId, (int) $id);
            $this->load->view('admin/feegroup/tenant_feegroup_edit', ['updated' => true, 'feegroup' => $feegroup]);

            return;
        }

        $this->load->view('admin/feegroup/tenant_feegroup_edit', ['updated' => false, 'feegroup' => $feegroup]);
    }

    public function tenantFeegroupDelete($id)
    {
        $tenantId = $this->session->userdata('admin_tenant_id');
        if (!$tenantId) {
            show_404();

            return;
        }

        $deleted = $this->feegroup_model->tenantScopedDelete('fee_groups', (int) $tenantId, (int) $id);
        $this->load->view('admin/feegroup/tenant_feegroup_delete', ['deleted' => $deleted]);
    }

}
