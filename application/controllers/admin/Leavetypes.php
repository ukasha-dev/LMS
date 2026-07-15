<?php

class LeaveTypes extends Admin_Controller
{

    public function __construct()
    {

        parent::__construct();
        $this->load->helper('file');
        $this->config->load("payroll");
        $this->load->model('leavetypes_model');
        $this->load->model('staff_model');
    }

    public function index()
    {
        $this->session->set_userdata('top_menu', 'HR');
        $this->session->set_userdata('sub_menu', 'admin/leavetypes');
        $data["title"]     = $this->lang->line('add_leave_type');
        $LeaveTypes        = $this->leavetypes_model->getLeaveType();
        $data["leavetype"] = $LeaveTypes;
        $this->load->view("layout/header");
        $this->load->view("admin/staff/leavetypes", $data);
        $this->load->view("layout/footer");
    }

    public function createleavetype()
    {
        $this->form_validation->set_rules(
            'type', $this->lang->line('name'), array('required',
                array('check_exists', array($this->leavetypes_model, 'valid_leave_type')),
            )
        );
        
        $leavetypeid = $this->input->post("leavetypeid");
        
        if (!empty($leavetypeid)) {
            $data["title"] = $this->lang->line('edit_leave_type');            
            $result            = $this->staff_model->getLeaveType($leavetypeid);        
            $data["result"]    = $result;        
        } else {
            $data["title"] = $this->lang->line('add_leave_type');
        }  
        
        if ($this->form_validation->run()) {

            $type        = $this->input->post("type");
            
            $status      = $this->input->post("status");
            if (empty($leavetypeid)) {

                if (!$this->rbac->hasPrivilege('leave_types', 'can_add')) {
                    access_denied();
                }
            } else {

                if (!$this->rbac->hasPrivilege('leave_types', 'can_edit')) {
                    access_denied();
                }
            }

            if (!empty($leavetypeid)) {
                $data = array('type' => $type, 'is_active' => 'yes', 'id' => $leavetypeid);
            } else {

                $data = array('type' => $type, 'is_active' => 'yes');
            }

            $insert_id = $this->leavetypes_model->addLeaveType($data);
            $this->session->set_flashdata('msg', '<div class="alert alert-success">' . $this->lang->line('success_message') . '</div>');
            redirect("admin/leavetypes");
        } else {

            $LeaveTypes        = $this->leavetypes_model->getLeaveType();
            $data["leavetype"] = $LeaveTypes;
            $this->load->view("layout/header");
            $this->load->view("admin/staff/leavetypes", $data);
            $this->load->view("layout/footer");
        }
    }

    public function leaveedit($id)
    {
        $result            = $this->staff_model->getLeaveType($id);
        $data["title"]     = $this->lang->line('edit_leave_type');
        $data["result"]    = $result;
        $LeaveTypes        = $this->leavetypes_model->getLeaveType();
        $data["leavetype"] = $LeaveTypes;
        $this->load->view("layout/header");
        $this->load->view("admin/staff/leavetypes", $data);
        $this->load->view("layout/footer");
    }

    public function leavedelete($id)
    {
        $this->leavetypes_model->deleteLeaveType($id);
        redirect('admin/leavetypes');
    }

    public function tenantLeaveTypesList()
    {
        $tenantId = $this->session->userdata('admin_tenant_id');
        if (!$tenantId) {
            show_404();

            return;
        }

        $leaveTypesList = $this->leavetypes_model->getTenantScopedLeaveTypesList((int) $tenantId);
        $this->load->view('admin/staff/tenant_leave_types_list', ['leaveTypesList' => $leaveTypesList]);
    }

    public function tenantLeaveTypesCreate()
    {
        $tenantId = $this->session->userdata('admin_tenant_id');
        if (!$tenantId) {
            show_404();

            return;
        }

        $this->form_validation->set_rules('type', 'Leave Type', 'trim|required|xss_clean');

        if ($this->input->method() === 'post' && $this->form_validation->run() !== false) {
            $newId = $this->leavetypes_model->tenantScopedInsert('leave_types', (int) $tenantId, [
                'type'      => $this->input->post('type'),
                'is_active' => 'yes',
            ]);
            $this->load->view('admin/staff/tenant_leave_types_create', ['created' => true, 'id' => $newId]);

            return;
        }

        $this->load->view('admin/staff/tenant_leave_types_create', ['created' => false]);
    }

    public function tenantLeaveTypesEdit($id)
    {
        $tenantId = $this->session->userdata('admin_tenant_id');
        if (!$tenantId) {
            show_404();

            return;
        }

        $leaveType = $this->leavetypes_model->tenantScopedFind('leave_types', (int) $tenantId, (int) $id);
        if (!$leaveType) {
            show_404();

            return;
        }

        $this->form_validation->set_rules('type', 'Leave Type', 'trim|required|xss_clean');

        if ($this->input->method() === 'post' && $this->form_validation->run() !== false) {
            $this->leavetypes_model->tenantScopedUpdate('leave_types', (int) $tenantId, (int) $id, [
                'type' => $this->input->post('type'),
            ]);
            $leaveType = $this->leavetypes_model->tenantScopedFind('leave_types', (int) $tenantId, (int) $id);
            $this->load->view('admin/staff/tenant_leave_types_edit', ['updated' => true, 'leaveType' => $leaveType]);

            return;
        }

        $this->load->view('admin/staff/tenant_leave_types_edit', ['updated' => false, 'leaveType' => $leaveType]);
    }

    public function tenantLeaveTypesDelete($id)
    {
        $tenantId = $this->session->userdata('admin_tenant_id');
        if (!$tenantId) {
            show_404();

            return;
        }

        $deleted = $this->leavetypes_model->tenantScopedDelete('leave_types', (int) $tenantId, (int) $id);
        $this->load->view('admin/staff/tenant_leave_types_delete', ['deleted' => $deleted]);
    }

}
