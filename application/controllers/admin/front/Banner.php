<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Banner extends Admin_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->config('ci-blog');
           $this->load->library('media_storage');   
        $this->banner_content = $this->config->item('ci_front_banner_content');
    }

    public function index()
    {
        if (!$this->rbac->hasPrivilege('banner_images', 'can_view')) {
            access_denied();
        }
        $data = array();
        $this->session->set_userdata('top_menu', 'Front CMS');
        $this->session->set_userdata('sub_menu', 'admin/front/banner');
        $result = $this->cms_program_model->getByCategory($this->banner_content);
        if (!empty($result)) {
            $data['banner_images'] = $this->cms_program_model->front_cms_program_photos($result[0]['id']);
        }

        $this->load->view('layout/header');
        $this->load->view('admin/front/banner/index', $data);
        $this->load->view('layout/footer');
    }

    public function add()
    {
        if (!$this->rbac->hasPrivilege('banner_images', 'can_add')) {
            access_denied();
        }
        $this->session->set_userdata('top_menu', 'Front CMS');
        $this->session->set_userdata('sub_menu', 'admin/front/banner');
        $banner_content = $this->config->item('ci_front_banner_content');
        $this->form_validation->set_rules('content_id', $this->lang->line('fees_payment_id'), 'required|trim|xss_clean');
        if ($this->form_validation->run() == false) {
            $data = array(
                'content_id' => form_error('content_id'),
            );
            $array = array('status' => '0', 'error' => $this->lang->line('something_went_wrong'));
            echo json_encode($array);
        } else {

            $data = array(
                'program_id'       => 0,
                'media_gallery_id' => $this->input->post('content_id'),
            );

            $response = $this->cms_program_model->banner($banner_content, $data);
            if ($response) {
                $array = array('status' => '1', 'error' => '', 'msg' => $this->lang->line('success_message'));
            } else {
                $array = array('status' => '0', 'error' => $this->lang->line('something_went_wrong'), 'msg' => $this->lang->line('something_went_wrong'));
            }
            echo json_encode($array);
        }
    }

    // media_gallery_id isn't a DB-enforced FK on front_cms_program_photos,
    // but it's a real reference into front_cms_media_gallery -- verified
    // via tenantScopedFind before linking, same treatment as any other
    // cross-table reference in this migration. program_id is get-or-create
    // on the singleton "Banner Images" program row (type='banner'),
    // mirroring the legacy banner()/bannerDelete() shape.
    public function tenantBannerAdd()
    {
        $tenantId = $this->session->userdata('admin_tenant_id');
        if (!$tenantId) {
            show_404();

            return;
        }
        $tenantId = (int) $tenantId;

        $this->form_validation->set_rules('content_id', 'Media', 'required|trim|xss_clean');
        if ($this->form_validation->run() === false) {
            $this->load->view('admin/front/banner/tenant_banner_add', ['created' => false]);

            return;
        }

        $mediaId = (int) $this->input->post('content_id');
        if (!$this->cms_program_model->tenantScopedFind('front_cms_media_gallery', $tenantId, $mediaId)) {
            show_404();

            return;
        }

        $programs = $this->cms_program_model->tenantScopedList('front_cms_programs', $tenantId, ['type' => $this->banner_content]);
        if (!empty($programs)) {
            $programId = (int) $programs[0]['id'];
        } else {
            $programId = $this->cms_program_model->tenantScopedInsert('front_cms_programs', $tenantId, [
                'type'  => $this->banner_content,
                'title' => 'Banner Images',
            ]);
        }

        $id = $this->cms_program_model->tenantScopedInsert('front_cms_program_photos', $tenantId, [
            'program_id'       => $programId,
            'media_gallery_id' => $mediaId,
        ]);

        $this->load->view('admin/front/banner/tenant_banner_add', ['created' => true, 'id' => $id]);
    }

    public function tenantBannerDelete($id)
    {
        $tenantId = $this->session->userdata('admin_tenant_id');
        if (!$tenantId) {
            show_404();

            return;
        }
        $tenantId = (int) $tenantId;

        $deleted = $this->cms_program_model->tenantScopedDelete('front_cms_program_photos', $tenantId, (int) $id);
        $this->load->view('admin/front/banner/tenant_banner_delete', ['deleted' => $deleted]);
    }

    public function remove()
    {
        if (!$this->rbac->hasPrivilege('banner_images', 'can_delete')) {
            access_denied();
        }
        $banner_content = $this->config->item('ci_front_banner_content');
        $this->form_validation->set_rules('content_id', $this->lang->line('fees_payment_id'), 'required|trim|xss_clean');
        if ($this->form_validation->run() == false) {
            $data = array(
                'content_id' => form_error('content_id'),
            );
            $array = array('status' => '0', 'error' => $this->lang->line('something_went_wrong'));
            echo json_encode($array);
        } else {
            $media_gallery_id = $this->input->post('content_id');
            $response         = $this->cms_program_model->bannerDelete($banner_content, $media_gallery_id);
            if ($response) {
                $array = array('status' => '1', 'error' => '', 'msg' => $this->lang->line('delete_message'));
            } else {
                $array = array('status' => '0', 'error' => $this->lang->line('something_went_wrong'), 'msg' => $this->lang->line('something_went_wrong'));
            }
            echo json_encode($array);
        }
    }

}
