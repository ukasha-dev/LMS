<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Roomtype extends Admin_Controller
{

    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        if (!$this->rbac->hasPrivilege('room_type', 'can_view')) {
            access_denied();
        }
        $roomtypelist         = $this->roomtype_model->get();
        $data['roomtypelist'] = $roomtypelist;
        $this->session->set_userdata('top_menu', 'Hostel');
        $this->session->set_userdata('sub_menu', 'roomtype/index');
        $listroomtype         = $this->roomtype_model->lists();
        $data['listroomtype'] = $listroomtype;
        $ght                  = $this->customlib->getHostaltype();
        $data['ght']          = $ght;
        $this->load->view('layout/header');
        $this->load->view('admin/roomtype/create', $data);
        $this->load->view('layout/footer');
    }

    public function create()
    {
        if (!$this->rbac->hasPrivilege('room_type', 'can_add')) {
            access_denied();
        }
        $data['title'] = 'Add Library';
        $this->form_validation->set_rules('room_type', $this->lang->line('room_type'), 'trim|required|xss_clean');
        $roomtypelist         = $this->roomtype_model->get();
        $data['roomtypelist'] = $roomtypelist;
        if ($this->form_validation->run() == false) {
            $this->load->view('layout/header');
            $this->load->view('admin/roomtype/create', $data);
            $this->load->view('layout/footer');
        } else {
            $data = array(
                'room_type'   => $this->input->post('room_type'),
                'description' => $this->input->post('description'),
            );
            $this->roomtype_model->add($data);
            $this->session->set_flashdata('msg', '<div class="alert alert-success text-left">' . $this->lang->line('success_message') . '</div>');
            redirect('admin/roomtype/index');
        }
    }

    public function edit($id)
    {
        if (!$this->rbac->hasPrivilege('room_type', 'can_edit')) {
            access_denied();
        }
        $data['title']        = 'Add Hostel';
        $data['id']           = $id;
        $roomtype             = $this->roomtype_model->get($id);
        $data['roomtype']     = $this->roomtype_model->get($id);
        $roomtypelist         = $this->roomtype_model->get();
        $data['roomtypelist'] = $roomtypelist;
        $this->form_validation->set_rules('room_type', $this->lang->line('room_type'), 'trim|required|xss_clean');
        if ($this->form_validation->run() == false) {
            $this->load->view('layout/header');
            $this->load->view('admin/roomtype/edit', $data);
            $this->load->view('layout/footer');
        } else {
            $data = array(
                'id'          => $this->input->post('id'),
                'room_type'   => $this->input->post('room_type'),
                'description' => $this->input->post('description'),
            );
            $this->roomtype_model->add($data);
            $this->session->set_flashdata('msg', '<div class="alert alert-success text-left">' . $this->lang->line('update_message') . '</div>');
            redirect('admin/roomtype/index');
        }
    }

    public function delete($id)
    {
        if (!$this->rbac->hasPrivilege('room_type', 'can_delete')) {
            access_denied();
        }
        $data['title'] = 'Fees Master List';
        $this->roomtype_model->remove($id);
        redirect('admin/roomtype/index');
    }

    public function tenantRoomtypeList()
    {
        $tenantId = $this->session->userdata('admin_tenant_id');
        if (!$tenantId) {
            show_404();

            return;
        }

        $roomtypeList = $this->roomtype_model->tenantScopedList('room_types', (int) $tenantId);
        $this->load->view('admin/roomtype/tenant_roomtype_list', ['roomtypeList' => $roomtypeList]);
    }

    public function tenantRoomtypeCreate()
    {
        $tenantId = $this->session->userdata('admin_tenant_id');
        if (!$tenantId) {
            show_404();

            return;
        }

        $this->form_validation->set_rules('room_type', 'Room Type', 'trim|required|xss_clean');

        if ($this->input->method() === 'post' && $this->form_validation->run() !== false) {
            $newId = $this->roomtype_model->tenantScopedInsert('room_types', (int) $tenantId, [
                'room_type'   => $this->input->post('room_type'),
                'description' => $this->input->post('description'),
            ]);
            $this->load->view('admin/roomtype/tenant_roomtype_create', ['created' => true, 'id' => $newId]);

            return;
        }

        $this->load->view('admin/roomtype/tenant_roomtype_create', ['created' => false]);
    }

    public function tenantRoomtypeEdit($id)
    {
        $tenantId = $this->session->userdata('admin_tenant_id');
        if (!$tenantId) {
            show_404();

            return;
        }

        $roomtype = $this->roomtype_model->tenantScopedFind('room_types', (int) $tenantId, (int) $id);
        if (!$roomtype) {
            show_404();

            return;
        }

        $this->form_validation->set_rules('room_type', 'Room Type', 'trim|required|xss_clean');

        if ($this->input->method() === 'post' && $this->form_validation->run() !== false) {
            $this->roomtype_model->tenantScopedUpdate('room_types', (int) $tenantId, (int) $id, [
                'room_type'   => $this->input->post('room_type'),
                'description' => $this->input->post('description'),
            ]);
            $roomtype = $this->roomtype_model->tenantScopedFind('room_types', (int) $tenantId, (int) $id);
            $this->load->view('admin/roomtype/tenant_roomtype_edit', ['updated' => true, 'roomtype' => $roomtype]);

            return;
        }

        $this->load->view('admin/roomtype/tenant_roomtype_edit', ['updated' => false, 'roomtype' => $roomtype]);
    }

    public function tenantRoomtypeDelete($id)
    {
        $tenantId = $this->session->userdata('admin_tenant_id');
        if (!$tenantId) {
            show_404();

            return;
        }

        $deleted = $this->roomtype_model->tenantScopedDelete('room_types', (int) $tenantId, (int) $id);
        $this->load->view('admin/roomtype/tenant_roomtype_delete', ['deleted' => $deleted]);
    }

}
