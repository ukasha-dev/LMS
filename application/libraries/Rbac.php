<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Rbac
{

    private $userRoles = array();
    protected $permissions;
    public $perm_category;

    public function __construct()
    {

        $this->CI          = &get_instance();
        $this->permissions = array();
        $this->CI->config->load('mailsms');
        $this->perm_category = $this->CI->config->item('perm_category');
      
    }
 
    public function hasPrivilege($category = null, $permission = null)
    {    
        $roles            = $this->CI->customlib->getStaffRole();
        $logged_user_role = json_decode($roles)->name;

        if ($logged_user_role == 'Super Admin') {
            return true;
        }

        $admin = $this->CI->session->userdata('admin');

        $roles    = $admin['roles'];
        $role_key = key($roles);
        $role_id  = $roles[$role_key];

        // admin_tenant_id is only set for tenant-scoped (school_saas)
        // sessions, where roles_permissions.role_id is NOT guaranteed
        // unique across tenants -- it only stays that way today by
        // coincidence of how the data-migration tools allocate ids, not
        // by design. Every legacy per-branch-database session has no
        // admin_tenant_id at all, and that schema has no tenant_id column
        // on roles_permissions either, so the filter must stay entirely
        // conditional -- unconditionally adding it would break every
        // legacy session with a "column not found" SQL error.
        $tenantId  = $this->CI->session->userdata('admin_tenant_id');
        $role_perm = $this->CI->rolepermission_model->getPermissionByRoleandCategory($role_id, trim($category), $tenantId ?: null);

        if ($role_perm) {
            if (array_key_exists($permission, $role_perm)) {
               return ($role_perm[$permission]);
            }

        }       

        return false;
    }

  
    public function module_permission($module_name)
    {
        $module_perm = $this->CI->Module_model->getPermissionByModulename($module_name);
        return $module_perm;
    }

    public function unautherized()
    {
        $this->CI->load->view('layout/header');
        $this->CI->load->view('unauthorized');
        $this->CI->load->view('layout/footer');
    }

}
