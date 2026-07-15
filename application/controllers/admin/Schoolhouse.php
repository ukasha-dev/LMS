<?php

class Schoolhouse extends Admin_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model("schoolhouse_model");
    }

    public function index()
    {
        if (!$this->rbac->hasPrivilege('student_houses', 'can_view')) {
            access_denied();
        }
        $this->session->set_userdata('top_menu', 'Student Information');
        $this->session->set_userdata('sub_menu', 'admin/schoolhouse');
        $data['title']       = $this->lang->line('add_school_house');
        $data["house_name"]  = "";
        $data["description"] = "";
        $houselist           = $this->schoolhouse_model->get();
        $data["houselist"]   = $houselist;
        $this->load->view('layout/header', $data);
        $this->load->view('admin/schoolhouse/houselist', $data);
        $this->load->view('layout/footer', $data);
    }

    public function create()
    {
        if (!$this->rbac->hasPrivilege('student_houses', 'can_add')) {
            access_denied();
        }
        $data['title']       = $this->lang->line('add_school_house');
        $houselist           = $this->schoolhouse_model->get();
        $data["houselist"]   = $houselist;
        $data["house_name"]  = "";
        $data["description"] = "";
        $this->form_validation->set_rules('house_name', $this->lang->line('name'), 'trim|required|xss_clean');
        if ($this->form_validation->run() == false) {
            $this->load->view('layout/header', $data);
            $this->load->view('admin/schoolhouse/houselist', $data);
            $this->load->view('layout/footer', $data);
        } else {
            $data = array(
                'house_name'  => $this->input->post('house_name'),
                'is_active'   => 'yes',
                'description' => $this->input->post('description'),
            );
            $this->schoolhouse_model->add($data);

            $this->session->set_flashdata('msg', '<div class="alert alert-success text-left">' . $this->lang->line('success_message') . '</div>');
            redirect('admin/schoolhouse/index');
        }
    }

    public function edit($id)
    {
        if (!$this->rbac->hasPrivilege('student_houses', 'can_edit')) {
            access_denied();
        }
        $data['title']       = $this->lang->line('edit_school_house');
        $houselist           = $this->schoolhouse_model->get();
        $data["houselist"]   = $houselist;
        $data['id']          = $id;
        $house               = $this->schoolhouse_model->get($id);
        $data["house"]       = $house;
        $data["house_name"]  = $house["house_name"];
        $data["description"] = $house["description"];
        $this->form_validation->set_rules('house_name', $this->lang->line('name'), 'trim|required|xss_clean');
        if ($this->form_validation->run() == false) {
            $this->load->view('layout/header', $data);
            $this->load->view('admin/schoolhouse/houselist', $data);
            $this->load->view('layout/footer', $data);
        } else {
            $data = array(
                'id'          => $id,
                'house_name'  => $this->input->post('house_name'),
                'is_active'   => 'yes',
                'description' => $this->input->post('description'),
            );
            $this->schoolhouse_model->add($data);
            $this->session->set_flashdata('msg', '<div class="alert alert-success text-left">' . $this->lang->line('update_message') . '</div>');
            redirect('admin/schoolhouse');
        }
    }

    public function delete($id)
    {
        if (!$this->rbac->hasPrivilege('student_houses', 'can_delete')) {
            access_denied();
        }
        if (!empty($id)) {
            $this->schoolhouse_model->delete($id);
            $this->session->set_flashdata('msgdelete', '<div class="alert alert-success text-left">' . $this->lang->line('delete_message') . '</div>');
        }
        redirect('admin/schoolhouse/');
    }

    public function tenantSchoolhouseList()
    {
        $tenantId = $this->session->userdata('admin_tenant_id');
        if (!$tenantId) {
            show_404();

            return;
        }

        $schoolhouseList = $this->schoolhouse_model->tenantScopedList('school_houses', (int) $tenantId);
        $this->load->view('admin/schoolhouse/tenant_schoolhouse_list', ['schoolhouseList' => $schoolhouseList]);
    }

    public function tenantSchoolhouseCreate()
    {
        $tenantId = $this->session->userdata('admin_tenant_id');
        if (!$tenantId) {
            show_404();

            return;
        }

        $this->form_validation->set_rules('house_name', 'House Name', 'trim|required|xss_clean');

        if ($this->input->method() === 'post' && $this->form_validation->run() !== false) {
            $newId = $this->schoolhouse_model->tenantScopedInsert('school_houses', (int) $tenantId, [
                'house_name'  => $this->input->post('house_name'),
                'description' => $this->input->post('description'),
                'is_active'   => 'yes',
            ]);
            $this->load->view('admin/schoolhouse/tenant_schoolhouse_create', ['created' => true, 'id' => $newId]);

            return;
        }

        $this->load->view('admin/schoolhouse/tenant_schoolhouse_create', ['created' => false]);
    }

    public function tenantSchoolhouseEdit($id)
    {
        $tenantId = $this->session->userdata('admin_tenant_id');
        if (!$tenantId) {
            show_404();

            return;
        }

        $schoolhouse = $this->schoolhouse_model->tenantScopedFind('school_houses', (int) $tenantId, (int) $id);
        if (!$schoolhouse) {
            show_404();

            return;
        }

        $this->form_validation->set_rules('house_name', 'House Name', 'trim|required|xss_clean');

        if ($this->input->method() === 'post' && $this->form_validation->run() !== false) {
            $this->schoolhouse_model->tenantScopedUpdate('school_houses', (int) $tenantId, (int) $id, [
                'house_name'  => $this->input->post('house_name'),
                'description' => $this->input->post('description'),
            ]);
            $schoolhouse = $this->schoolhouse_model->tenantScopedFind('school_houses', (int) $tenantId, (int) $id);
            $this->load->view('admin/schoolhouse/tenant_schoolhouse_edit', ['updated' => true, 'schoolhouse' => $schoolhouse]);

            return;
        }

        $this->load->view('admin/schoolhouse/tenant_schoolhouse_edit', ['updated' => false, 'schoolhouse' => $schoolhouse]);
    }

    public function tenantSchoolhouseDelete($id)
    {
        $tenantId = $this->session->userdata('admin_tenant_id');
        if (!$tenantId) {
            show_404();

            return;
        }

        $deleted = $this->schoolhouse_model->tenantScopedDelete('school_houses', (int) $tenantId, (int) $id);
        $this->load->view('admin/schoolhouse/tenant_schoolhouse_delete', ['deleted' => $deleted]);
    }

}
