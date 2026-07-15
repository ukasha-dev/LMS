<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Itemstore extends Admin_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('file');

        $this->load->helper('url');
    }

    public function index()
    {
        if (!$this->rbac->hasPrivilege('store', 'can_view')) {
            access_denied();
        }
        $this->session->set_userdata('top_menu', 'Inventory');
        $this->session->set_userdata('sub_menu', 'itemstore/index');
        $data['title']         = 'Item Store List';
        $itemstore_result      = $this->itemstore_model->get();
        $data['itemstorelist'] = $itemstore_result;
        $this->load->view('layout/header', $data);
        $this->load->view('admin/itemstore/itemstoreList', $data);
        $this->load->view('layout/footer', $data);
    }

    public function delete($id)
    {
        if (!$this->rbac->hasPrivilege('store', 'can_delete')) {
            access_denied();
        }
        $data['title'] = 'Item Store List';
        $this->itemstore_model->remove($id);
        redirect('admin/itemstore/index');
    }

    public function create()
    {
        if (!$this->rbac->hasPrivilege('store', 'can_add')) {
            access_denied();
        }
        $data['title']         = 'Add Item store';
        $itemstore_result      = $this->itemstore_model->get();
        $data['itemstorelist'] = $itemstore_result;

        $this->form_validation->set_rules('name', $this->lang->line('item_store_name'), 'trim|required|xss_clean');

        if ($this->form_validation->run() == false) {
            $this->load->view('layout/header', $data);
            $this->load->view('admin/itemstore/itemstoreList', $data);
            $this->load->view('layout/footer', $data);
        } else {
            $data = array(
                'item_store'  => $this->input->post('name'),
                'code'        => $this->input->post('code'),
                'description' => $this->input->post('description'),
            );
            $this->itemstore_model->add($data);
            $this->session->set_flashdata('msg', '<div class="alert alert-success text-left">' . $this->lang->line('success_message') . '</div>');
            redirect('admin/itemstore/index');
        }
    }

    public function edit($id)
    {
        if (!$this->rbac->hasPrivilege('store', 'can_edit')) {
            access_denied();
        }

        $data['title']         = 'Edit Item Store';
        $itemstore_result      = $this->itemstore_model->get();
        $data['itemstorelist'] = $itemstore_result;
        $data['id']            = $id;
        $store                 = $this->itemstore_model->get($id);
        $data['itemstore']     = $store;

        $this->form_validation->set_rules('name', $this->lang->line('item_store_name'), 'trim|required|xss_clean');

        if ($this->form_validation->run() == false) {
            $this->load->view('layout/header', $data);
            $this->load->view('admin/itemstore/itemstoreEdit', $data);
            $this->load->view('layout/footer', $data);
        } else {
            $data = array(
                'id'          => $id,
                'item_store'  => $this->input->post('name'),
                'code'        => $this->input->post('code'),
                'description' => $this->input->post('description'),
            );
            $this->itemstore_model->add($data);
            $this->session->set_flashdata('msg', '<div class="alert alert-success">' . $this->lang->line('update_message') . '</div>');
            redirect('admin/itemstore/index');
        }
    }

    public function tenantItemstoreList()
    {
        $tenantId = $this->session->userdata('admin_tenant_id');
        if (!$tenantId) {
            show_404();

            return;
        }

        $itemstoreList = $this->itemstore_model->tenantScopedList('item_store', (int) $tenantId);
        $this->load->view('admin/itemstore/tenant_itemstore_list', ['itemstoreList' => $itemstoreList]);
    }

    public function tenantItemstoreCreate()
    {
        $tenantId = $this->session->userdata('admin_tenant_id');
        if (!$tenantId) {
            show_404();

            return;
        }

        $this->form_validation->set_rules('name', 'Name', 'trim|required|xss_clean');

        if ($this->input->method() === 'post' && $this->form_validation->run() !== false) {
            $newId = $this->itemstore_model->tenantScopedInsert('item_store', (int) $tenantId, [
                'item_store'  => $this->input->post('name'),
                'code'        => $this->input->post('code'),
                'description' => $this->input->post('description'),
            ]);
            $this->load->view('admin/itemstore/tenant_itemstore_create', ['created' => true, 'id' => $newId]);

            return;
        }

        $this->load->view('admin/itemstore/tenant_itemstore_create', ['created' => false]);
    }

    public function tenantItemstoreEdit($id)
    {
        $tenantId = $this->session->userdata('admin_tenant_id');
        if (!$tenantId) {
            show_404();

            return;
        }

        $itemstore = $this->itemstore_model->tenantScopedFind('item_store', (int) $tenantId, (int) $id);
        if (!$itemstore) {
            show_404();

            return;
        }

        $this->form_validation->set_rules('name', 'Name', 'trim|required|xss_clean');

        if ($this->input->method() === 'post' && $this->form_validation->run() !== false) {
            $this->itemstore_model->tenantScopedUpdate('item_store', (int) $tenantId, (int) $id, [
                'item_store'  => $this->input->post('name'),
                'code'        => $this->input->post('code'),
                'description' => $this->input->post('description'),
            ]);
            $itemstore = $this->itemstore_model->tenantScopedFind('item_store', (int) $tenantId, (int) $id);
            $this->load->view('admin/itemstore/tenant_itemstore_edit', ['updated' => true, 'itemstore' => $itemstore]);

            return;
        }

        $this->load->view('admin/itemstore/tenant_itemstore_edit', ['updated' => false, 'itemstore' => $itemstore]);
    }

    public function tenantItemstoreDelete($id)
    {
        $tenantId = $this->session->userdata('admin_tenant_id');
        if (!$tenantId) {
            show_404();

            return;
        }

        $deleted = $this->itemstore_model->tenantScopedDelete('item_store', (int) $tenantId, (int) $id);
        $this->load->view('admin/itemstore/tenant_itemstore_delete', ['deleted' => $deleted]);
    }

}
