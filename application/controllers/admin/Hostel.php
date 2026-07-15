<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Hostel extends Admin_Controller
{

    public function __construct()
    {
        parent::__construct();

        $this->load->library('Customlib');
    }

    public function index()
    {

        if (!$this->rbac->hasPrivilege('hostel', 'can_view')) {
            access_denied();
        }
        $this->session->set_userdata('top_menu', 'Hostel');
        $this->session->set_userdata('sub_menu', 'hostel/index');
        $listhostel         = $this->hostel_model->listhostel();
        $data['listhostel'] = $listhostel;
        $ght                = $this->customlib->getHostaltype();
        $data['ght']        = $ght;
        $this->load->view('layout/header');
        $this->load->view('admin/hostel/createhostel', $data);
        $this->load->view('layout/footer');
    }

    public function create()
    {
        if (!$this->rbac->hasPrivilege('hostel', 'can_add')) {
            access_denied();
        }
        $data['title'] = 'Add Library';
        $this->form_validation->set_rules('hostel_name', $this->lang->line('hostel_name'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('type', $this->lang->line('type'), 'trim|required|xss_clean');
        if ($this->form_validation->run() == false) {
            $listhostel         = $this->hostel_model->listhostel();
            $data['listhostel'] = $listhostel;
            $ght                = $this->customlib->getHostaltype();
            $data['ght']        = $ght;
            $this->load->view('layout/header');
            $this->load->view('admin/hostel/createhostel', $data);
            $this->load->view('layout/footer');
        } else {
            $data = array(
                'hostel_name' => $this->input->post('hostel_name'),
                'type'        => $this->input->post('type'),
                'address'     => $this->input->post('address'),
                'intake'      => $this->input->post('intake'),
                'description' => $this->input->post('description'),
            );
            $this->hostel_model->addhostel($data);
            $this->session->set_flashdata('msg', '<div class="alert alert-success text-left">' . $this->lang->line('success_message') . '</div>');
            redirect('admin/hostel/index');
        }
    }

    public function edit($id)
    {
        if (!$this->rbac->hasPrivilege('hostel', 'can_edit')) {
            access_denied();
        }
        $data['title']      = 'Add Hostel';
        $data['id']         = $id;
        $edithostel         = $this->hostel_model->get($id);
        $data['edithostel'] = $edithostel;
        $ght                = $this->customlib->getHostaltype();
        $data['ght']        = $ght;
        $this->form_validation->set_rules('hostel_name', $this->lang->line('hostel_name'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('type', $this->lang->line('type'), 'trim|required|xss_clean');
        if ($this->form_validation->run() == false) {
            $listhostel         = $this->hostel_model->listhostel();
            $data['listhostel'] = $listhostel;
            $this->load->view('layout/header');
            $this->load->view('admin/hostel/edithostel', $data);
            $this->load->view('layout/footer');
        } else {
            $data = array(
                'id'          => $this->input->post('id'),
                'hostel_name' => $this->input->post('hostel_name'),
                'type'        => $this->input->post('type'),
                'address'     => $this->input->post('address'),
                'intake'      => $this->input->post('intake'),
                'description' => $this->input->post('description'),
            );
            $this->hostel_model->addhostel($data);
            $this->session->set_flashdata('msg', '<div class="alert alert-success text-left">' . $this->lang->line('update_message') . '</div>');
            redirect('admin/hostel/index');
        }
    }

    public function delete($id)
    {
        if (!$this->rbac->hasPrivilege('hostel', 'can_delete')) {
            access_denied();
        }
        $data['title'] = 'Fees Master List';
        $this->hostel_model->remove($id);
        redirect('admin/hostel/index');
    }

    public function tenantHostelList()
    {
        $tenantId = $this->session->userdata('admin_tenant_id');
        if (!$tenantId) {
            show_404();

            return;
        }

        $hostelList = $this->hostel_model->tenantScopedList('hostel', (int) $tenantId);
        $this->load->view('admin/hostel/tenant_hostel_list', ['hostelList' => $hostelList]);
    }

    public function tenantHostelCreate()
    {
        $tenantId = $this->session->userdata('admin_tenant_id');
        if (!$tenantId) {
            show_404();

            return;
        }

        $this->form_validation->set_rules('hostel_name', 'Hostel Name', 'trim|required|xss_clean');
        $this->form_validation->set_rules('type', 'Type', 'trim|required|xss_clean');

        if ($this->input->method() === 'post' && $this->form_validation->run() !== false) {
            $newId = $this->hostel_model->tenantScopedInsert('hostel', (int) $tenantId, [
                'hostel_name' => $this->input->post('hostel_name'),
                'type'        => $this->input->post('type'),
                'address'     => $this->input->post('address'),
                'intake'      => $this->input->post('intake'),
                'description' => $this->input->post('description'),
                'is_active'   => 'yes',
            ]);
            $this->load->view('admin/hostel/tenant_hostel_create', ['created' => true, 'id' => $newId]);

            return;
        }

        $this->load->view('admin/hostel/tenant_hostel_create', ['created' => false]);
    }

    public function tenantHostelEdit($id)
    {
        $tenantId = $this->session->userdata('admin_tenant_id');
        if (!$tenantId) {
            show_404();

            return;
        }

        $hostel = $this->hostel_model->tenantScopedFind('hostel', (int) $tenantId, (int) $id);
        if (!$hostel) {
            show_404();

            return;
        }

        $this->form_validation->set_rules('hostel_name', 'Hostel Name', 'trim|required|xss_clean');
        $this->form_validation->set_rules('type', 'Type', 'trim|required|xss_clean');

        if ($this->input->method() === 'post' && $this->form_validation->run() !== false) {
            $this->hostel_model->tenantScopedUpdate('hostel', (int) $tenantId, (int) $id, [
                'hostel_name' => $this->input->post('hostel_name'),
                'type'        => $this->input->post('type'),
                'address'     => $this->input->post('address'),
                'intake'      => $this->input->post('intake'),
                'description' => $this->input->post('description'),
            ]);
            $hostel = $this->hostel_model->tenantScopedFind('hostel', (int) $tenantId, (int) $id);
            $this->load->view('admin/hostel/tenant_hostel_edit', ['updated' => true, 'hostel' => $hostel]);

            return;
        }

        $this->load->view('admin/hostel/tenant_hostel_edit', ['updated' => false, 'hostel' => $hostel]);
    }

    public function tenantHostelDelete($id)
    {
        $tenantId = $this->session->userdata('admin_tenant_id');
        if (!$tenantId) {
            show_404();

            return;
        }

        $deleted = $this->hostel_model->tenantScopedDelete('hostel', (int) $tenantId, (int) $id);
        $this->load->view('admin/hostel/tenant_hostel_delete', ['deleted' => $deleted]);
    }

}
