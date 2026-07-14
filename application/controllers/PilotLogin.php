<?php

defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/Pilot_Controller.php';

class PilotLogin extends Pilot_Controller
{
    public function __construct()
    {
        parent::__construct();
        // Deviation from the brief (documented, not silent): the brief's
        // code put this require_once at file scope, before the class
        // declaration, which runs before parent::__construct(). But
        // Tenant_Model extends MY_Model, and MY_Model is only defined as
        // a side effect of CI3's Loader::model() bootstrap block (see
        // system/core/Loader.php ~L297-326), which is triggered here by
        // the autoloaded Customlib library's constructor calling
        // $this->load->model('Notification_model') during
        // parent::__construct(). Requiring Tenant_Model.php before that
        // point throws "Class MY_Model not found". Moving the
        // require_once into the constructor body, after
        // parent::__construct(), matches the working pattern already
        // proven in PilotStudents.php.
        $this->load->database('school_saas_pilot');
        require_once APPPATH . 'core/Tenant_Model.php';
        $this->load->model('Tenant_Model', 'tenant_model');
        $this->load->library('enc_lib');
    }

    public function login()
    {
        if ($this->input->method() !== 'post') {
            echo '<form method="post" action="' . site_url('pilotlogin/login') . '">'
                . '<input type="hidden" name="tenant_id" value="25">'
                . 'Email: <input type="text" name="email"><br>'
                . 'Password: <input type="password" name="password"><br>'
                . '<button type="submit">Login</button>'
                . '</form>';

            return;
        }

        $email = $this->input->post('email');
        $password = $this->input->post('password');
        $tenantId = (int) $this->input->post('tenant_id');

        $this->session->set_userdata('pilot_tenant_id', $tenantId);

        $staffRows = $this->tenant_model->tenantGetAll('staff', ['email' => $email]);
        if (count($staffRows) !== 1) {
            $this->session->unset_userdata('pilot_tenant_id');
            echo 'Invalid email or password.';

            return;
        }

        $staff = $staffRows[0];
        if (!$this->enc_lib->passHashDyc($password, $staff['password'])) {
            $this->session->unset_userdata('pilot_tenant_id');
            echo 'Invalid email or password.';

            return;
        }

        if ((int) $staff['is_active'] !== 1) {
            $this->session->unset_userdata('pilot_tenant_id');
            echo 'Account disabled.';

            return;
        }

        $staffRoleRows = $this->tenant_model->tenantGetAll('staff_roles', ['staff_id' => $staff['id']]);
        $roleName = 'Unknown';
        if (count($staffRoleRows) === 1) {
            $roleRows = $this->tenant_model->tenantGetAll('roles', ['id' => $staffRoleRows[0]['role_id']]);
            if (count($roleRows) === 1) {
                $roleName = $roleRows[0]['name'];
            }
        }

        $sessionData = [
            'id' => $staff['id'],
            'username' => trim($staff['name'] . ' ' . ($staff['surname'] ?? '')),
            'email' => $staff['email'],
            'role' => $roleName,
            'tenant_id' => $tenantId,
        ];

        $this->session->set_userdata('pilot_admin', $sessionData);

        $roleId = ($roleName !== 'Unknown' && count($staffRoleRows) === 1) ? $staffRoleRows[0]['role_id'] : null;

        $this->session->set_userdata('admin', [
            'id' => $staff['id'],
            'username' => $sessionData['username'],
            'email' => $staff['email'],
            'roles' => [$roleName => $roleId],
            'language' => ['language' => 'English'],
            'db_array' => ['base_url' => '', 'folder_path' => '', 'db_group' => 'school_saas_pilot'],
            'superadmin_restriction' => 0,
        ]);
        $this->session->set_userdata('admin_tenant_id', $tenantId);

        redirect('admin/staff/tenantStaffList');
    }

    public function dashboard()
    {
        $sessionData = $this->session->userdata('pilot_admin');
        if (!$sessionData) {
            redirect('pilotlogin/login');

            return;
        }

        $this->load->view('pilot_dashboard', ['session' => $sessionData]);
    }
}
