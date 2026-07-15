<?php

/**
 * 
 */
class Designation extends Admin_Controller {

    function __construct() {

        parent::__construct();

        $this->load->helper('file');
        $this->config->load("payroll");

        $this->load->model('designation_model');
        $this->load->model('staff_model');
    }

    function designation() {

        $this->session->set_userdata('top_menu', 'HR');
        $this->session->set_userdata('sub_menu', 'admin/designation/designation');
        $designation = $this->designation_model->get();
        $data["title"] = $this->lang->line('add_designation');
        $data["designation"] = $designation;
        $this->form_validation->set_rules(
                'type', $this->lang->line('name'), array('required',
            array('check_exists', array($this->designation_model, 'valid_designation'))
                )
        );
        if ($this->form_validation->run()) {

            $type = $this->input->post("type");
            $designationid = $this->input->post("designationid");
            $status = $this->input->post("status");
            if (empty($designationid)) {

                if (!$this->rbac->hasPrivilege('designation', 'can_add')) {
                    access_denied();
                }
            } else {

                if (!$this->rbac->hasPrivilege('designation', 'can_edit')) {
                    access_denied();
                }
            }

            if (!empty($designationid)) {
                $data = array('designation' => $type, 'is_active' => 'yes', 'id' => $designationid);
            } else {

                $data = array('designation' => $type, 'is_active' => 'yes');
            }
            $insert_id = $this->designation_model->addDesignation($data);
            $this->session->set_flashdata('msg', '<div class="alert alert-success">' . $this->lang->line('success_message') . '</div>');
            redirect("admin/designation/designation");
        } else {

            $this->load->view("layout/header");
            $this->load->view("admin/staff/designation", $data);
            $this->load->view("layout/footer");
        }
    }

    function designationedit($id) {

        $result = $this->designation_model->get($id);
        $data["title"] = $this->lang->line('edit_designation');
        $data["result"] = $result;

        $designation = $this->designation_model->get();
        $data["designation"] = $designation;
        $this->load->view("layout/header");
        $this->load->view("admin/staff/designation", $data);
        $this->load->view("layout/footer");
    }

    function designationdelete($id) {

        $this->designation_model->deleteDesignation($id);
        redirect('admin/designation/designation');
    }

    public function tenantDesignationList()
    {
        $tenantId = $this->session->userdata('admin_tenant_id');
        if (!$tenantId) {
            show_404();

            return;
        }

        $designationList = $this->designation_model->getTenantScopedDesignationList((int) $tenantId);
        $this->load->view('admin/staff/tenant_designation_list', ['designationList' => $designationList]);
    }

    public function tenantDesignationCreate()
    {
        $tenantId = $this->session->userdata('admin_tenant_id');
        if (!$tenantId) {
            show_404();

            return;
        }

        $this->form_validation->set_rules('designation', 'Designation', 'trim|required|xss_clean');

        if ($this->input->method() === 'post' && $this->form_validation->run() !== false) {
            $newId = $this->designation_model->tenantScopedInsert('staff_designation', (int) $tenantId, [
                'designation' => $this->input->post('designation'),
                'is_active'   => 'yes',
            ]);
            $this->load->view('admin/staff/tenant_designation_create', ['created' => true, 'id' => $newId]);

            return;
        }

        $this->load->view('admin/staff/tenant_designation_create', ['created' => false]);
    }

    public function tenantDesignationEdit($id)
    {
        $tenantId = $this->session->userdata('admin_tenant_id');
        if (!$tenantId) {
            show_404();

            return;
        }

        $designation = $this->designation_model->tenantScopedFind('staff_designation', (int) $tenantId, (int) $id);
        if (!$designation) {
            show_404();

            return;
        }

        $this->form_validation->set_rules('designation', 'Designation', 'trim|required|xss_clean');

        if ($this->input->method() === 'post' && $this->form_validation->run() !== false) {
            $this->designation_model->tenantScopedUpdate('staff_designation', (int) $tenantId, (int) $id, [
                'designation' => $this->input->post('designation'),
            ]);
            $designation = $this->designation_model->tenantScopedFind('staff_designation', (int) $tenantId, (int) $id);
            $this->load->view('admin/staff/tenant_designation_edit', ['updated' => true, 'designation' => $designation]);

            return;
        }

        $this->load->view('admin/staff/tenant_designation_edit', ['updated' => false, 'designation' => $designation]);
    }

    public function tenantDesignationDelete($id)
    {
        $tenantId = $this->session->userdata('admin_tenant_id');
        if (!$tenantId) {
            show_404();

            return;
        }

        $deleted = $this->designation_model->tenantScopedDelete('staff_designation', (int) $tenantId, (int) $id);
        $this->load->view('admin/staff/tenant_designation_delete', ['deleted' => $deleted]);
    }

}

?>