<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Contenttype extends Admin_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->library('Customlib');
        $this->load->library('media_storage');
        $this->config->load('app-config');
        $this->load->library("datatables");
        $this->load->model(array('contenttype_model'));
    }

    public function index()
    {

        if (!$this->rbac->hasPrivilege('content_type', 'can_view')) {
            access_denied();
        }
        $this->session->set_userdata('top_menu', 'download_center');
        $this->session->set_userdata('sub_menu', 'admin/contenttype');
        $data['title']      = 'Add Contenttype';
        $data['title_list'] = 'Recent Contenttypes';

        $this->form_validation->set_rules('name', $this->lang->line('name'), 'trim|required|xss_clean');

        if ($this->form_validation->run() == false) {

        } else {

            $data = array(
                'name'        => $this->input->post('name'),
                'description' => $this->input->post('description'),
            );

            $insert_id = $this->contenttype_model->add($data);

            $this->session->set_flashdata('msg', '<div class="alert alert-success text-left">' . $this->lang->line('success_message') . '</div>');
            redirect('admin/contenttype/index');
        }

        $this->load->view('layout/header', $data);
        $this->load->view('admin/contenttype/index', $data);
        $this->load->view('layout/footer', $data);
    }

    public function delete($id)
    {
        if (!$this->rbac->hasPrivilege('content_type', 'can_delete')) {
            access_denied();
        }

        $this->contenttype_model->remove($id);
        redirect('admin/contenttype/index');
    }

    public function edit($id)
    {
        if (!$this->rbac->hasPrivilege('content_type', 'can_edit')) {
            access_denied();
        }
        
        $data['id']      = $id;
        $expense         = $this->contenttype_model->get($id);
        $data['expense'] = $expense;

        $this->form_validation->set_rules('name', $this->lang->line('name'), 'trim|required|xss_clean');

        if ($this->form_validation->run() == false) {
            $this->load->view('layout/header', $data);
            $this->load->view('admin/contenttype/edit', $data);
            $this->load->view('layout/footer', $data);
        } else {
            $data = array(
                'id'          => $id,
                'name'        => $this->input->post('name'),
                'description' => $this->input->post('description'),
            );
            $insert_id = $this->contenttype_model->add($data);

            $this->session->set_flashdata('msg', '<div class="alert alert-success text-left">' . $this->lang->line('update_message') . '</div>');
            redirect('admin/contenttype/index');
        }
    }

    public function getcontenttypelist()
    {
        $m = $this->contenttype_model->getcontenttypelist();
        $m = json_decode($m);

        $dt_data = array();
        if (!empty($m->data)) {
            foreach ($m->data as $key => $value) {
                $editbtn   = '';
                $deletebtn = '';
                $documents = '';

                if ($this->rbac->hasPrivilege('content_type', 'can_edit')) {
                    $editbtn = "<a href='" . base_url() . "admin/contenttype/edit/" . $value->id . "'   class='btn btn-default btn-xs'  data-toggle='tooltip' title='" . $this->lang->line('edit') . "'><i class='fa fa-pencil'></i></a>";
                }
                if ($this->rbac->hasPrivilege('content_type', 'can_delete')) {
                    $deletebtn = '';
                    $deletebtn = "<a onclick='return confirm(" . '"' . $this->lang->line('delete_confirm') . '"' . ");' href='" . base_url() . "admin/contenttype/delete/" . $value->id . "' class='btn btn-default btn-xs' title='" . $this->lang->line('delete') . "' data-toggle='tooltip'><i class='fa fa-trash'></i></a>";
                }

                $row   = array();
                $row[] = $value->name;

                if ($value->description == "") {
                    $row[] = $this->lang->line('no_description');
                } else {
                    $row[] = $value->description;
                }

                $row[]     = $editbtn . ' ' . $deletebtn;
                $dt_data[] = $row;
            }
        }

        $json_data = array(
            "draw"            => intval($m->draw),
            "recordsTotal"    => intval($m->recordsTotal),
            "recordsFiltered" => intval($m->recordsFiltered),
            "data"            => $dt_data,
        );
        echo json_encode($json_data);
    }

    public function tenantContentTypeList()
    {
        $tenantId = $this->session->userdata('admin_tenant_id');
        if (!$tenantId) {
            show_404();

            return;
        }

        $contentTypeList = $this->contenttype_model->tenantScopedList('content_types', (int) $tenantId);
        $this->load->view('admin/contenttype/tenant_content_type_list', ['contentTypeList' => $contentTypeList]);
    }

    public function tenantContentTypeCreate()
    {
        $tenantId = $this->session->userdata('admin_tenant_id');
        if (!$tenantId) {
            show_404();

            return;
        }

        $this->form_validation->set_rules('name', 'Name', 'trim|required|xss_clean');

        if ($this->input->method() === 'post' && $this->form_validation->run() !== false) {
            $newId = $this->contenttype_model->tenantScopedInsert('content_types', (int) $tenantId, [
                'name'        => $this->input->post('name'),
                'description' => $this->input->post('description'),
                'is_active'   => 1,
            ]);
            $this->load->view('admin/contenttype/tenant_content_type_create', ['created' => true, 'id' => $newId]);

            return;
        }

        $this->load->view('admin/contenttype/tenant_content_type_create', ['created' => false]);
    }

    public function tenantContentTypeEdit($id)
    {
        $tenantId = $this->session->userdata('admin_tenant_id');
        if (!$tenantId) {
            show_404();

            return;
        }

        $contentType = $this->contenttype_model->tenantScopedFind('content_types', (int) $tenantId, (int) $id);
        if (!$contentType) {
            show_404();

            return;
        }

        $this->form_validation->set_rules('name', 'Name', 'trim|required|xss_clean');

        if ($this->input->method() === 'post' && $this->form_validation->run() !== false) {
            $this->contenttype_model->tenantScopedUpdate('content_types', (int) $tenantId, (int) $id, [
                'name'        => $this->input->post('name'),
                'description' => $this->input->post('description'),
            ]);
            $contentType = $this->contenttype_model->tenantScopedFind('content_types', (int) $tenantId, (int) $id);
            $this->load->view('admin/contenttype/tenant_content_type_edit', ['updated' => true, 'contentType' => $contentType]);

            return;
        }

        $this->load->view('admin/contenttype/tenant_content_type_edit', ['updated' => false, 'contentType' => $contentType]);
    }

    public function tenantContentTypeDelete($id)
    {
        $tenantId = $this->session->userdata('admin_tenant_id');
        if (!$tenantId) {
            show_404();

            return;
        }

        $deleted = $this->contenttype_model->tenantScopedDelete('content_types', (int) $tenantId, (int) $id);
        $this->load->view('admin/contenttype/tenant_content_type_delete', ['deleted' => $deleted]);
    }

}
