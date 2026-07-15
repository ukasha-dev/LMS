<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Complainttype extends Admin_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->library('form_validation');
        $this->load->model("ComplaintType_model");
    }

    public function index()
    {
        if (!$this->rbac->hasPrivilege('setup_font_office', 'can_view')) {
            access_denied();
        }
        $this->form_validation->set_rules('complaint_type', $this->lang->line('complaint_type'), 'required');

        if ($this->form_validation->run() == false) {
            $data['complaint_type_list'] = $this->ComplaintType_model->get('complaint_type');
            $this->load->view('layout/header');
            $this->load->view('admin/frontoffice/complainttypeview', $data);
            $this->load->view('layout/footer');
        } else {
            $complaint_type = array(
                'complaint_type' => $this->input->post('complaint_type'),
                'description'    => $this->input->post('description'),
            );
            $this->ComplaintType_model->add('complaint_type', $complaint_type);
            $this->session->set_flashdata('msg', '<div class="alert alert-success">' . $this->lang->line('success_message') . '</div>');
            redirect('admin/complainttype');
        }
    }

    public function editcomplainttype($complainttype_id)
    {
        if (!$this->rbac->hasPrivilege('setup_font_office', 'can_edit')) {
            access_denied();
        }
        $this->form_validation->set_rules('complaint_type', $this->lang->line('complaint_type'), 'required');

        if ($this->form_validation->run() == false) {
            $data['complaint_type_list'] = $this->ComplaintType_model->get('complaint_type');
            $data['complaint_type_data'] = $this->ComplaintType_model->get('complaint_type', $complainttype_id);

            $this->load->view('layout/header');
            $this->load->view('admin/frontoffice/complainttypeeditview', $data);
            $this->load->view('layout/footer');
        } else {

            $complaint_type = array(
                'complaint_type' => $this->input->post('complaint_type'),
                'description'    => $this->input->post('description'),
            );
            $this->ComplaintType_model->update('complaint_type', $complainttype_id, $complaint_type);
            $this->session->set_flashdata('msg', '<div class="alert alert-success">
                ' . $this->lang->line('update_message') . '</div>');
            redirect('admin/complainttype');
        }
    }

    public function delete($id)
    {
        if (!$this->rbac->hasPrivilege('setup_font_office', 'can_delete')) {
            access_denied();
        }
        $this->ComplaintType_model->delete('complaint_type', $id);
        $this->session->set_flashdata('msg', '<div class="alert alert-success">' . $this->lang->line('delete_message') . '</div>');
        redirect('admin/complainttype');
    }

    public function tenantComplainttypeList()
    {
        $tenantId = $this->session->userdata('admin_tenant_id');
        if (!$tenantId) {
            show_404();

            return;
        }

        $complainttypeList = $this->ComplaintType_model->tenantScopedList('complaint_type', (int) $tenantId);
        $this->load->view('admin/frontoffice/tenant_complainttype_list', ['complainttypeList' => $complainttypeList]);
    }

    public function tenantComplainttypeCreate()
    {
        $tenantId = $this->session->userdata('admin_tenant_id');
        if (!$tenantId) {
            show_404();

            return;
        }

        $this->form_validation->set_rules('complaint_type', 'Complaint Type', 'trim|required|xss_clean');

        if ($this->input->method() === 'post' && $this->form_validation->run() !== false) {
            $newId = $this->ComplaintType_model->tenantScopedInsert('complaint_type', (int) $tenantId, [
                'complaint_type' => $this->input->post('complaint_type'),
                'description'    => $this->input->post('description'),
            ]);
            $this->load->view('admin/frontoffice/tenant_complainttype_create', ['created' => true, 'id' => $newId]);

            return;
        }

        $this->load->view('admin/frontoffice/tenant_complainttype_create', ['created' => false]);
    }

    public function tenantComplainttypeEdit($id)
    {
        $tenantId = $this->session->userdata('admin_tenant_id');
        if (!$tenantId) {
            show_404();

            return;
        }

        $complainttype = $this->ComplaintType_model->tenantScopedFind('complaint_type', (int) $tenantId, (int) $id);
        if (!$complainttype) {
            show_404();

            return;
        }

        $this->form_validation->set_rules('complaint_type', 'Complaint Type', 'trim|required|xss_clean');

        if ($this->input->method() === 'post' && $this->form_validation->run() !== false) {
            $this->ComplaintType_model->tenantScopedUpdate('complaint_type', (int) $tenantId, (int) $id, [
                'complaint_type' => $this->input->post('complaint_type'),
                'description'    => $this->input->post('description'),
            ]);
            $complainttype = $this->ComplaintType_model->tenantScopedFind('complaint_type', (int) $tenantId, (int) $id);
            $this->load->view('admin/frontoffice/tenant_complainttype_edit', ['updated' => true, 'complainttype' => $complainttype]);

            return;
        }

        $this->load->view('admin/frontoffice/tenant_complainttype_edit', ['updated' => false, 'complainttype' => $complainttype]);
    }

    public function tenantComplainttypeDelete($id)
    {
        $tenantId = $this->session->userdata('admin_tenant_id');
        if (!$tenantId) {
            show_404();

            return;
        }

        $deleted = $this->ComplaintType_model->tenantScopedDelete('complaint_type', (int) $tenantId, (int) $id);
        $this->load->view('admin/frontoffice/tenant_complainttype_delete', ['deleted' => $deleted]);
    }

}
