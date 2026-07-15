<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Itemcategory extends Admin_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('url');
    }

    public function index()
    {
        if (!$this->rbac->hasPrivilege('item_category', 'can_view')) {
            access_denied();
        }
        $this->session->set_userdata('top_menu', 'Inventory');
        $this->session->set_userdata('sub_menu', 'itemcategory/index');
        $data['title']        = 'Item Categorey List';
        $category_result      = $this->itemcategory_model->get();
        $data['categorylist'] = $category_result;
        $this->load->view('layout/header', $data);
        $this->load->view('admin/itemcategory/itemcategoryList', $data);
        $this->load->view('layout/footer', $data);
    }

    public function delete($id)
    {
        if (!$this->rbac->hasPrivilege('item_category', 'can_delete')) {
            access_denied();
        }
        $data['title'] = 'Item Categorey List';
        $this->itemcategory_model->remove($id);
        redirect('admin/itemcategory/index');
    }

    public function create()
    {
        if (!$this->rbac->hasPrivilege('item_category', 'can_add')) {
            access_denied();
        }
        $data['title']        = 'Add Item category';
        $category_result      = $this->itemcategory_model->get();
        $data['categorylist'] = $category_result;
        $this->form_validation->set_rules('itemcategory', $this->lang->line('item_category'), 'trim|required|xss_clean');
        if ($this->form_validation->run() == false) {
            $this->load->view('layout/header', $data);
            $this->load->view('admin/itemcategory/itemcategoryList', $data);
            $this->load->view('layout/footer', $data);
        } else {
            $data = array(
                'item_category' => $this->input->post('itemcategory'),
                'description'   => $this->input->post('description'),
            );
            $this->itemcategory_model->add($data);
            $this->session->set_flashdata('msg', '<div class="alert alert-success text-left">' . $this->lang->line('success_message') . '</div>');
            redirect('admin/itemcategory/index');
        }
    }

    public function edit($id)
    {
        if (!$this->rbac->hasPrivilege('item_category', 'can_edit')) {
            access_denied();
        }
        $data['title']        = 'Edit Item Categorey';
        $category_result      = $this->itemcategory_model->get();
        $data['categorylist'] = $category_result;
        $data['id']           = $id;
        $category             = $this->itemcategory_model->get($id);
        $data['itemcategory'] = $category;
        $this->form_validation->set_rules('itemcategory', $this->lang->line('item_categorey'), 'trim|required|xss_clean');
        if ($this->form_validation->run() == false) {
            $this->load->view('layout/header', $data);
            $this->load->view('admin/itemcategory/itemcategoryEdit', $data);
            $this->load->view('layout/footer', $data);
        } else {
            $data = array(
                'id'            => $id,
                'item_category' => $this->input->post('itemcategory'),
                'description'   => $this->input->post('description'),
            );
            $this->itemcategory_model->add($data);
            $this->session->set_flashdata('msg', '<div class="alert alert-success">' . $this->lang->line('update_message') . '</div>');
            redirect('admin/itemcategory/index');
        }
    }

    public function tenantItemcategoryList()
    {
        $tenantId = $this->session->userdata('admin_tenant_id');
        if (!$tenantId) {
            show_404();

            return;
        }

        $itemcategoryList = $this->itemcategory_model->tenantScopedList('item_category', (int) $tenantId);
        $this->load->view('admin/itemcategory/tenant_itemcategory_list', ['itemcategoryList' => $itemcategoryList]);
    }

    public function tenantItemcategoryCreate()
    {
        $tenantId = $this->session->userdata('admin_tenant_id');
        if (!$tenantId) {
            show_404();

            return;
        }

        $this->form_validation->set_rules('item_category', 'Item Category', 'trim|required|xss_clean');

        if ($this->input->method() === 'post' && $this->form_validation->run() !== false) {
            $newId = $this->itemcategory_model->tenantScopedInsert('item_category', (int) $tenantId, [
                'item_category' => $this->input->post('item_category'),
                'description'   => $this->input->post('description'),
                'is_active'     => 'yes',
            ]);
            $this->load->view('admin/itemcategory/tenant_itemcategory_create', ['created' => true, 'id' => $newId]);

            return;
        }

        $this->load->view('admin/itemcategory/tenant_itemcategory_create', ['created' => false]);
    }

    public function tenantItemcategoryEdit($id)
    {
        $tenantId = $this->session->userdata('admin_tenant_id');
        if (!$tenantId) {
            show_404();

            return;
        }

        $itemcategory = $this->itemcategory_model->tenantScopedFind('item_category', (int) $tenantId, (int) $id);
        if (!$itemcategory) {
            show_404();

            return;
        }

        $this->form_validation->set_rules('item_category', 'Item Category', 'trim|required|xss_clean');

        if ($this->input->method() === 'post' && $this->form_validation->run() !== false) {
            $this->itemcategory_model->tenantScopedUpdate('item_category', (int) $tenantId, (int) $id, [
                'item_category' => $this->input->post('item_category'),
                'description'   => $this->input->post('description'),
            ]);
            $itemcategory = $this->itemcategory_model->tenantScopedFind('item_category', (int) $tenantId, (int) $id);
            $this->load->view('admin/itemcategory/tenant_itemcategory_edit', ['updated' => true, 'itemcategory' => $itemcategory]);

            return;
        }

        $this->load->view('admin/itemcategory/tenant_itemcategory_edit', ['updated' => false, 'itemcategory' => $itemcategory]);
    }

    public function tenantItemcategoryDelete($id)
    {
        $tenantId = $this->session->userdata('admin_tenant_id');
        if (!$tenantId) {
            show_404();

            return;
        }

        $deleted = $this->itemcategory_model->tenantScopedDelete('item_category', (int) $tenantId, (int) $id);
        $this->load->view('admin/itemcategory/tenant_itemcategory_delete', ['deleted' => $deleted]);
    }

}
