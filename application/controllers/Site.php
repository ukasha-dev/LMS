<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Site extends Public_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->check_installation();
        if ($this->config->item('installed') == true) {
            $this->db->reconnect();
        }

        $this->load->model(array("staff_model", "sharecontent_model"));
        $this->load->library('Auth');
        $this->load->library('Enc_lib');
        $this->load->library('customlib');
        $this->load->library('captchalib');
        $this->load->library('mailsmsconf');
        $this->load->library('mailer');
        $this->load->library('media_storage');
        $this->load->config('ci-blog');
        $this->mailer;
        $this->sch_setting = $this->setting_model->getSetting();
    }

    private function check_installation()
    {
        if ($this->uri->segment(1) !== 'install') {
            $this->load->config('migration');
            if ($this->config->item('installed') == false && $this->config->item('migration_enabled') == false) {
                redirect(base_url() . 'install/start');
            } else {
                if (is_dir(APPPATH . 'controllers/install')) {
                    echo '<h3>Delete the install folder from application/controllers/install</h3>';
                    die;
                }
            }
        }
    }

    public function login()
    {
        $app_name = $this->setting_model->get();
        $app_name = $app_name[0]['name'];

        if ($this->auth->logged_in()) {
            $this->auth->is_logged_in(true);
        }
        
        if ($this->module_lib->hasModule('google_authenticator') 
            && $this->module_lib->hasActive('google_authenticator')) {

            redirect('gauthenticate/login');
     
        }	
        
        $data          = array();
        $data['title'] = 'Login';
        $school        = $this->setting_model->get();

        $data['name'] = $app_name;

        $notice_content     = $this->config->item('ci_front_notice_content');
        $notices            = $this->cms_program_model->getByCategory($notice_content, array('start' => 0, 'limit' => 5));
        $data['notice']     = $notices;
        $data['school']     = $school[0];
        $is_captcha         = $this->captchalib->is_captcha('login');
        $data["is_captcha"] = $is_captcha;
        if ($this->captchalib->is_captcha('login')) {
            if($this->input->post('captcha')){
                $this->form_validation->set_rules('captcha', $this->lang->line('captcha'), 'trim|required|callback_check_captcha');
            }else{
                $this->form_validation->set_rules('captcha', $this->lang->line('captcha'), 'trim|required');
            }
        }
        $this->form_validation->set_rules('username', $this->lang->line('username'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('password', $this->lang->line('password'), 'trim|required|xss_clean');
        if ($this->form_validation->run() == false) {
            $captcha               = $this->captchalib->generate_captcha();
            $data['captcha_image'] = isset($captcha['image']) ? $captcha['image'] : "";
            $data['name']          = $app_name;
            $this->load->view('admin/login', $data);
        } else {
            $login_post = array(
                'email'    => $this->input->post('username'),
                'password' => $this->input->post('password'),
            );
            if ($this->captchalib->is_captcha('login')) {
            $data['captcha_image'] = $this->captchalib->generate_captcha()['image'];
            }
            $setting_result        = $this->setting_model->get();

            // --- REAL LOGIN GATE (Phase 4 Stage 1) ---
            // Tenant 25 (al_hafeez_campus/branch_25) check, run BEFORE and
            // completely independent of the legacy multi-branch loop
            // below. school_saas is the authoritative password check;
            // branch_25's own row (fetched directly here, not via the
            // loop) is the fallback so a stale school_saas password can
            // never lock a real user out. If either matches, we resolve
            // directly to branch_25 and skip the legacy loop entirely for
            // this login.
            //
            // Why this can't live inside the legacy loop (discovered live
            // during this stage's adversarial review, 2026-07-17, and
            // NOT fixed here -- out of scope, pre-existing, affects all 6
            // real schools, not just tenant 25): the loop's own
            // email+password matching is unreliable once duplicate
            // credentials exist across branch databases. Two real
            // collisions were found live: (1) `school_saas_pilot` (a
            // migration-infrastructure connection group, not a real
            // school) sits in the same $db array the loop iterates,
            // before any branch_* entry, and can match first;
            // (2) `smart_school` (branch_20) contains byte-identical
            // password hashes for a large fraction of at least 5 of the
            // 6 real schools' real staff (93 cross-database collisions
            // confirmed live across all 6 real school databases),
            // consistent with smart_school having been used as an
            // onboarding template that was never cleaned up in the
            // schools cloned from it. Either collision can cause the
            // legacy loop to match a DIFFERENT school's database before
            // ever reaching the tenant a login is actually for. This
            // block sidesteps that entire class of problem for tenant 25
            // specifically by checking a tenant-scoped source first,
            // rather than attempting to fix the loop's ordering (which
            // cannot be done safely without investigating the same
            // collision risk for the other 5 tenants, a separate,
            // dedicated data-integrity investigation this stage does not
            // attempt). Never reassigns $this->db.
            $found_group = 'default';
            try {
                require_once APPPATH . '../tools/multitenant/RealLoginGate.php';
                include(APPPATH . 'config/database.php');
                $realLoginDbConfig = $db['school_saas_pilot'];
                $realLoginPdo = new PDO(
                    'mysql:host=' . $realLoginDbConfig['hostname'] . ';dbname=' . $realLoginDbConfig['database'] . ';charset=utf8mb4',
                    $realLoginDbConfig['username'],
                    $realLoginDbConfig['password']
                );
                $realLoginPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                $branch25Config = $db['branch_25'] ?? null;
                $branch25RowPassword = null;
                if ($branch25Config) {
                    $branch25Pdo = new PDO(
                        'mysql:host=' . $branch25Config['hostname'] . ';dbname=' . $branch25Config['database'] . ';charset=utf8mb4',
                        $branch25Config['username'],
                        $branch25Config['password']
                    );
                    $branch25Pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    $branch25Stmt = $branch25Pdo->prepare('SELECT password FROM staff WHERE email = :email LIMIT 1');
                    $branch25Stmt->execute(['email' => $login_post['email']]);
                    $branch25Row = $branch25Stmt->fetch(PDO::FETCH_ASSOC);
                    $branch25RowPassword = $branch25Row['password'] ?? null;
                }

                $realLoginGate = new RealLoginGate($realLoginPdo);
                $gateResult = $realLoginGate->verify(
                    $login_post['email'],
                    $login_post['password'],
                    25,
                    [$this->enc_lib, 'passHashDyc'],
                    function () use ($login_post, $branch25RowPassword) {
                        return $branch25RowPassword !== null
                            && $this->enc_lib->passHashDyc($login_post['password'], $branch25RowPassword);
                    }
                );
                if ($gateResult['source'] === 'legacy') {
                    log_message('error', '[RealLoginGate] PASSWORD_DRIFT_DETECTED tenant_id=25 email=' . $login_post['email']);
                }
                if ($gateResult['success']) {
                    $found_group = 'branch_25';
                }
            } catch (\Throwable $e) {
                log_message('error', '[RealLoginGate] EXCEPTION ' . $e->getMessage());
                // $found_group stays 'default'; the legacy loop below
                // still runs completely normally as the fallback.
            }
            // --- END REAL LOGIN GATE ---

            if ($found_group === 'branch_25') {
                $CI =& get_instance();
                $new_db = $CI->load->database($found_group, TRUE);
                $CI->db->close();
                $CI->db = $new_db;
                $this->db = $new_db;
                $this->setting_model->db = $new_db;
                $this->staff_model->db = $new_db;
                $this->staffroles_model->db = $new_db;
                $this->customlib->db = $new_db;
                $this->config->set_item('active_db_group', $found_group);
                $setting_result = $this->setting_model->get();
            } else {
            // --- MULTI BRANCH STAFF LOGIN FIX START --- (unmodified from before this stage)
            include(APPPATH . 'config/database.php');
            if (isset($db) && is_array($db) && count($db) > 1) {
                $found_group = 'default';
                foreach ($db as $group_name => $config_item) {
                    if ($group_name === 'default') continue;
                    $test_db = @$this->load->database($group_name, TRUE);
                    if ($test_db && $test_db->conn_id) {
                        $test_db->select('password');
                        $test_db->where('email', $login_post['email']);
                        $test_db->limit(1);
                        $query = $test_db->get('staff');
                        if ($query && $query->num_rows() == 1) {
                            $row = $query->row();
                            if ($this->enc_lib->passHashDyc($login_post['password'], $row->password)) {
                                $found_group = $group_name;
                                $test_db->close();
                                break;
                            }
                        }
                        $test_db->close();
                    }
                }

                if ($found_group !== 'default') {
                    $CI =& get_instance();
                    $new_db = $CI->load->database($found_group, TRUE);
                    $CI->db->close();
                    $CI->db = $new_db;
                    $this->db = $new_db;
                    $this->setting_model->db = $new_db;
                    $this->staff_model->db = $new_db;
                    $this->staffroles_model->db = $new_db;
                    $this->customlib->db = $new_db;
                    $this->config->set_item('active_db_group', $found_group);
                    $setting_result = $this->setting_model->get(); // Refresh settings from new DB
                }
            }
            // --- MULTI BRANCH STAFF LOGIN FIX END ---
            }

            $result                = $this->staff_model->checkLogin($login_post);

            if (!empty($result->language_id)) {
                $lang_array = array('lang_id' => $result->language_id, 'language' => $result->language);
                if ($result->is_rtl == 1) {
                    $is_rtl = "enabled";
                } else {
                    $is_rtl = "disabled";
                }

            } else {
                $lang_array = array('lang_id' => $setting_result[0]['lang_id'], 'language' => $setting_result[0]['language']);
                if ($setting_result[0]['is_rtl'] == 1) {
                    $is_rtl = "enabled";
                } else {
                    $is_rtl = "disabled";
                }
            }

            if ($result) {
                if ($result->is_active) {
                    if ($result->surname != "") {
                        $logusername = $result->name . " " . $result->surname;
                    } else {
                        $logusername = $result->name;
                    }

                    $session_data = array(
                        'id'                     => $result->id,
                        'username'               => $logusername,
                        'email'                  => $result->email,
                        'image'                  =>$result->image,
                        'roles'                  => $result->roles,
                        'date_format'            => $setting_result[0]['date_format'],                        
                        'currency'               => ($result->currency == 0) ? $setting_result[0]['currency']: $result->currency,
                        'currency_base_price'    => ($result->base_price == 0) ? $setting_result[0]['base_price']: $result->base_price,
                        'currency_format'        => $setting_result[0]['currency_format'],
                        'currency_symbol'        => ($result->symbol == "0") ? $setting_result[0]['currency_symbol'] : $result->symbol,
                        'currency_place'         => $setting_result[0]['currency_place'],
                        'start_month'            => $setting_result[0]['start_month'],
                        'start_week'             => date("w", strtotime($setting_result[0]['start_week'])),
                        'school_name'            => $setting_result[0]['name'],
                        'timezone'               => $setting_result[0]['timezone'],
                        'sch_name'               => $setting_result[0]['name'],
                        'language'               => $lang_array,
                        'is_rtl'                 => $is_rtl,
                        'theme'                  => $setting_result[0]['theme'],
                        'gender'                 => $result->gender,                     
                        'db_array'               => ['base_url'               => $setting_result[0]['base_url'],
                                                     'folder_path'            => $setting_result[0]['folder_path'],
                                                     'db_group'               => (isset($found_group) ? $found_group : 'default')
                                                    ],
                        'superadmin_restriction' => $setting_result[0]['superadmin_restriction'],
                        'saas_key'               => $setting_result[0]['saas_key'],
                        'admin_panel_whatsapp'   		=> $setting_result[0]['admin_panel_whatsapp'],
                        'admin_panel_whatsapp_mobile'   => $setting_result[0]['admin_panel_whatsapp_mobile'],
                        'admin_panel_whatsapp_from'   	=> $setting_result[0]['admin_panel_whatsapp_from'],
                        'admin_panel_whatsapp_to'  		=> $setting_result[0]['admin_panel_whatsapp_to'],						
                    );

                    $this->session->set_userdata('admin', $session_data);

                    $role      = $this->customlib->getStaffRole();
                    $role_name = json_decode($role)->name;
                    $this->customlib->setUserLog($this->input->post('username'), $role_name);

                    // --- SHADOW TENANT LOGIN VERIFY (Phase 3 Stage 5) ---
                    // Read-only, pilot-tenant-only, best-effort proof that
                    // school_saas agrees with this real login. Never sets
                    // session data, never changes the redirect below, never
                    // touches $this->db (builds its own separate PDO
                    // connection instead), and any
                    // failure here is swallowed. branch_25 == al_hafeez_campus
                    // == tenant_id 25 (multi_branch.id 25, confirmed live) —
                    // this never runs for the other 5 schools.
                    if (isset($found_group) && $found_group === 'branch_25') {
                        try {
                            require_once APPPATH . '../tools/multitenant/ShadowLoginVerifier.php';
                            $shadowDbConfig = $db['school_saas_pilot'];
                            $shadowPdo = new PDO(
                                'mysql:host=' . $shadowDbConfig['hostname'] . ';dbname=' . $shadowDbConfig['database'] . ';charset=utf8mb4',
                                $shadowDbConfig['username'],
                                $shadowDbConfig['password']
                            );
                            $shadowPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                            $shadowVerifier = new ShadowLoginVerifier($shadowPdo);
                            $shadowResult = $shadowVerifier->verify(
                                $login_post['email'],
                                $login_post['password'],
                                25,
                                [$this->enc_lib, 'passHashDyc']
                            );
                            log_message(
                                $shadowResult['matched'] ? 'info' : 'error',
                                '[ShadowTenantLoginVerify] email=' . $login_post['email']
                                . ' matched=' . ($shadowResult['matched'] ? '1' : '0')
                                . ' reason=' . $shadowResult['reason']
                            );
                        } catch (\Throwable $e) {
                            log_message('error', '[ShadowTenantLoginVerify] EXCEPTION ' . $e->getMessage());
                        }
                    }
                    // --- END SHADOW TENANT LOGIN VERIFY ---

                    if (isset($_SESSION['redirect_to'])) {
                        redirect($_SESSION['redirect_to']);
                    } else {
                        redirect('admin/admin/dashboard');
                    }

                } else {
                    $data['name']          = $app_name;
                    $data['error_message'] = $this->lang->line('your_account_is_disabled_please_contact_to_administrator');

                    $this->load->view('admin/login', $data);
                }
            } else {
                $data['name']          = $app_name;
                $data['error_message'] = $this->lang->line('invalid_username_or_password');
                $this->load->view('admin/login', $data);
            }
        }
    }

    public function logout()
    {
        $admin_session   = $this->session->userdata('admin');
        $student_session = $this->session->userdata('student');
        $this->auth->logout();
        if ($admin_session) {
            redirect('site/login');
        } else if ($student_session) {
            redirect('site/userlogin');
        } else {
            redirect('site/userlogin');
        }
    }

    public function download_content($share_id, $content_id)
    {
        $content_id = $this->enc_lib->dycrypt($content_id);
        $content    = $this->sharecontent_model->checkvalid($share_id, $content_id);
        if ($content) {
            $this->media_storage->filedownload($content->img_name, $content->dir_path);
        } else {
            echo $this->lang->line('invalid_or_expired_link_please_check_it_again');
        }
    }

    public function forgotpassword()
    {
       
        $app_name     = $this->setting_model->get();
        $data['name'] = $app_name[0]['name'];
        $this->form_validation->set_rules('email', $this->lang->line('email'), 'trim|valid_email|required|xss_clean');
        
        $notice_content     = $this->config->item('ci_front_notice_content');
        $notices            = $this->cms_program_model->getByCategory($notice_content, array('start' => 0, 'limit' => 5));
        $data['notice']     = $notices;
        $data['school']     = $app_name[0];
         
        if ($this->form_validation->run() == false) {
            $this->load->view('admin/forgotpassword', $data);
        } else {
            $email = $this->input->post('email');

            $result = $this->staff_model->getByEmail($email);

            if ($result && $result->email != "") {
                if ($result->is_active == '1') {
                    $verification_code = $this->enc_lib->encrypt(uniqid(mt_rand()));
                    $update_record     = array('id' => $result->id, 'verification_code' => $verification_code);
                    $this->staff_model->add($update_record);
                    $name           = $result->name;
                    $resetPassLink  = site_url('admin/resetpassword') . "/" . $verification_code;
                    $sender_details = array('resetPassLink' => $resetPassLink, 'name' => $name, 'username' => $result->surname, 'staff_email' => $email);
                    $this->mailsmsconf->mailsms('forgot_password', $sender_details);
                    $this->session->set_flashdata('message', $this->lang->line('please_check_your_email_to_recover_your_password'));
                } else {
                    $this->session->set_flashdata('disable_message', $this->lang->line('your_account_is_disabled_please_contact_to_administrator'));
                }

                redirect('site/login', 'refresh');
            } else {

                $data['error_message'] = $this->lang->line('incorrect_email');
                
            }
            
            $this->load->view('admin/forgotpassword', $data);
        }
    }

    //reset password - final step for forgotten password
    public function admin_resetpassword($verification_code = null)
    {
        $app_name     = $this->setting_model->get();
        $data['name'] = $app_name[0]['name'];
        $data['admin_login_page_background'] = $app_name[0]['admin_login_page_background'];
        if (!$verification_code) {
            show_404();
        }

        $user = $this->staff_model->getByVerificationCode($verification_code);
        $notice_content     = $this->config->item('ci_front_notice_content');
        $notices            = $this->cms_program_model->getByCategory($notice_content, array('start' => 0, 'limit' => 5));
        $data['notice']     = $notices;
        
        if ($user) {
            //if the code is valid then display the password reset form
            $this->form_validation->set_rules('password', $this->lang->line('password'), 'required');
            $this->form_validation->set_rules('confirm_password', $this->lang->line('confirm_password'), 'required|matches[password]');
            if ($this->form_validation->run() == false) {
                
                $data['verification_code'] = $verification_code;
                //render
                $this->load->view('admin/admin_resetpassword', $data);
            } else {

                // finally change the password
                $password      = $this->input->post('password');
                $update_record = array(
                    'id'                => $user->id,
                    'password'          => $this->enc_lib->passHashEnc($password),
                    'verification_code' => "",
                );

                $change = $this->staff_model->update($update_record);
                if ($change) {
                    //if the password was successfully changed
                    $this->session->set_flashdata('message', $this->lang->line("password_reset_successfully"));
                    redirect('site/login', 'refresh');
                } else {
                    $this->session->set_flashdata('message', $this->lang->line("something_went_wrong"));
                    redirect('admin_resetpassword/' . $verification_code, 'refresh');
                }
            }
        } else {
            //if the code is invalid then send them back to the forgot password page
            $this->session->set_flashdata('message', $this->lang->line('invalid_link'));
            redirect("site/forgotpassword", 'refresh');
        }
    }
    
    //reset password - final step for forgotten password
    public function share($key)
    {
        $data               = array();
        $id                 = $this->enc_lib->dycrypt($key);
        $data['branch_url']             = $this->customlib->getBaseUrl();
        $data['share_data'] = $this->sharecontent_model->getShareContentWithDocuments($id);       
        $this->load->view('share', $data);
    }
    
    //reset password - final step for forgotten password
    public function resetpassword($role = null, $verification_code = null)
    {
        $app_name     = $this->setting_model->get();
        $data['app_name'] = $app_name;
        if (!$role || !$verification_code) {
            show_404();
        }
        
        $notice_content     = $this->config->item('ci_front_notice_content');
        $notices            = $this->cms_program_model->getByCategory($notice_content, array('start' => 0, 'limit' => 5));
        $data['notice']     = $notices;

        $user = $this->user_model->getUserByCodeUsertype($role, $verification_code);

        if ($user) {
            //if the code is valid then display the password reset form
            $this->form_validation->set_rules('password', $this->lang->line('password'), 'required');
            $this->form_validation->set_rules('confirm_password', $this->lang->line('confirm_password'), 'required|matches[password]');
            if ($this->form_validation->run() == false) {

                $data['role']              = $role;
                $data['verification_code'] = $verification_code;
                //render
                $this->load->view('resetpassword', $data);
            } else {

                // finally change the password

                $update_record = array(
                    'id'                => $user->user_tbl_id,
                    'password'          => $this->input->post('password'),
                    'verification_code' => "",
                );

                $change = $this->user_model->saveNewPass($update_record);
                if ($change) {
                    //if the password was successfully changed
                    $this->session->set_flashdata('message', $this->lang->line('password_reset_successfully'));
                    redirect('site/userlogin', 'refresh');
                } else {
                    $this->session->set_flashdata('message', $this->lang->line("something_went_wrong"));
                    redirect('user/resetpassword/' . $role . '/' . $verification_code, 'refresh');
                }
            }
        } else {
            //if the code is invalid then send them back to the forgot password page
            $this->session->set_flashdata('message', $this->lang->line('invalid_link'));
            redirect("site/ufpassword", 'refresh');
        }
    }

    public function ufpassword()
    {  
        
        $notice_content     = $this->config->item('ci_front_notice_content');
        $notices            = $this->cms_program_model->getByCategory($notice_content, array('start' => 0, 'limit' => 5));
        $data['notice']     = $notices; 
        
        $this->form_validation->set_rules('username', $this->lang->line('email'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('user[]', $this->lang->line('user_type'), 'trim|required|xss_clean');
        if ($this->form_validation->run() == false) {

            $this->load->view('ufpassword', $data);
        } else {
            $email    = $this->input->post('username');
            $usertype = $this->input->post('user[]');
            $result   = $this->user_model->forgotPassword($usertype[0], $email);
        
            if ($result && $result->email != "") {

                $verification_code = $this->enc_lib->encrypt(uniqid(mt_rand()));
                $update_record     = array('id' => $result->user_tbl_id, 'verification_code' => $verification_code);
                $this->user_model->updateVerCode($update_record);

                if ($usertype[0] == "student") {
                    $name     = $this->customlib->getFullName($result->firstname, $result->middlename, $result->lastname, $this->sch_setting->middlename, $this->sch_setting->lastname);
                    $username = $result->username;
                } else {
                    $name     = $result->guardian_name;
                    $username = $result->username;
                }

                $resetPassLink  = site_url('user/resetpassword') . '/' . $usertype[0] . "/" . $verification_code;
                $sender_details = array('resetPassLink' => $resetPassLink, 'name' => $name, 'username' => $username);
                if ($usertype[0] == "student") {
                    $sender_details['email'] = $email;
                } else {
                    $sender_details['guardian_email'] = $email;
                }
                $this->mailsmsconf->mailsms('forgot_password', $sender_details);
                $this->session->set_flashdata('message', $this->lang->line("please_check_your_email_to_recover_your_password"));
                redirect('site/userlogin', 'refresh');
            } else {
                $data = array(
                     
                    'error_message' => $this->lang->line('invalid_email_or_user_type'),
                );
            }
            
            $data['notice']     = $notices; 
        
            $this->load->view('ufpassword', $data);
        }
    }

   public function userlogin(){
    
        $school = $this->setting_model->get();

        if($school[0]['student_panel_login']==0) {
            $student_login_status=0;
        }else{
            $student_login_status=1;
        }
        if($school[0]['parent_panel_login']==0){
            $parent_login_status=0;
        }else{
            $parent_login_status=1;
        }
        if($student_login_status==0 && $parent_login_status==0){
             redirect('site/login', 'refresh');
        }

        if ($this->auth->user_logged_in()) {
            $this->auth->user_redirect();
        }
        
        if ($this->module_lib->hasModule('google_authenticator') 
            && $this->module_lib->hasActive('google_authenticator')) {
                redirect('gauthenticate/userlogin');     
        }

        $data               = array();
        $data['title']      = 'Login';
        $data['name']       = $school[0]['name'];
        $notice_content     = $this->config->item('ci_front_notice_content');
        $notices            = $this->cms_program_model->getByCategory($notice_content, array('start' => 0, 'limit' => 5));
        $data['notice']     = $notices;
        $data['school']     = $school[0];
        $is_captcha         = $this->captchalib->is_captcha('userlogin');
        $data["is_captcha"] = $is_captcha;
        if ($is_captcha) {
            
            if($this->input->post('captcha')){
                $this->form_validation->set_rules('captcha', $this->lang->line('captcha'), 'trim|required|callback_check_captcha');
            }else{
                $this->form_validation->set_rules('captcha', $this->lang->line('captcha'), 'trim|required');
            }  
            
        }
        $this->form_validation->set_rules('username', $this->lang->line('username'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('password', $this->lang->line('password'), 'trim|required|xss_clean');
        if ($this->form_validation->run() == false) {
            if ($this->captchalib->is_captcha('userlogin')) {
                $data['captcha_image'] = $this->captchalib->generate_captcha()['image'];
            }
            $this->load->view('userlogin', $data);
        } else {
            $login_post = array(
                'username' => $this->input->post('username'),
                'password' => $this->input->post('password'),
            );
            $data['captcha_image'] = $this->captchalib->generate_captcha()['image'];
            
            // --- REAL USER LOGIN GATE (Phase 4 Stage 2) ---
            // Independent pre-loop check for tenant 25 (al_hafeez_campus/branch_25)
            // only. On a match, $found_group is set directly and the legacy loop
            // below is skipped entirely for this login. On no match, $found_group
            // stays 'default' and the legacy loop runs 100% unmodified, exactly as
            // before this stage, for every case including tenant 25's own
            // non-matches. This mirrors Phase 4 Stage 1's final architecture
            // (tools/multitenant/RealLoginGate.php's wiring in login()) applied
            // from the start here, not discovered reactively: school_saas_pilot
            // and branch_20 (smart_school) both precede branch_25 in the same $db
            // array this method also includes below, and smart_school is known to
            // carry template-contaminated student data (see the roadmap's Phase 4
            // Stage 1 entry, finding #2, and this stage's own smaller-scale
            // confirmation). Never reassigns $this->db in this block except via
            // the swap below.
            $found_group = 'default';
            try {
                require_once APPPATH . '../tools/multitenant/RealUserLoginGate.php';
                include(APPPATH . 'config/database.php');
                $realUserLoginDbConfig = $db['school_saas_pilot'];
                $realUserLoginPdo = new PDO(
                    'mysql:host=' . $realUserLoginDbConfig['hostname'] . ';dbname=' . $realUserLoginDbConfig['database'] . ';charset=utf8mb4',
                    $realUserLoginDbConfig['username'],
                    $realUserLoginDbConfig['password']
                );
                $realUserLoginPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                $branch25Config = $db['branch_25'] ?? null;
                $branch25UserLoginFallback = function () use ($branch25Config, $login_post): bool {
                    if ($branch25Config === null) {
                        return false;
                    }
                    $branch25Pdo = new PDO(
                        'mysql:host=' . $branch25Config['hostname'] . ';dbname=' . $branch25Config['database'] . ';charset=utf8mb4',
                        $branch25Config['username'],
                        $branch25Config['password']
                    );
                    $branch25Pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    $branch25Stmt = $branch25Pdo->prepare(
                        'SELECT users.id
                         FROM users
                         LEFT JOIN students
                           ON (students.id = users.user_id OR students.parent_id = users.id)
                         WHERE users.password = :password
                         AND (
                           users.username = :identifier
                           OR students.admission_no = :identifier
                           OR students.mobileno = :identifier
                           OR students.email = :identifier
                           OR students.guardian_phone = :identifier
                           OR students.guardian_email = :identifier
                         )
                         LIMIT 1'
                    );
                    $branch25Stmt->execute([
                        'identifier' => $login_post['username'],
                        'password' => $login_post['password'],
                    ]);

                    return $branch25Stmt->fetch(PDO::FETCH_ASSOC) !== false;
                };

                $realUserLoginGate = new RealUserLoginGate($realUserLoginPdo);
                $gateResult = $realUserLoginGate->verify(
                    $login_post['username'],
                    $login_post['password'],
                    25,
                    fn (string $submitted, string $stored): bool => $submitted === $stored,
                    $branch25UserLoginFallback
                );
                if ($gateResult['source'] === 'legacy') {
                    log_message('error', '[RealUserLoginGate] AMBIGUOUS_OR_STALE_SCHOOL_SAAS_MATCH tenant_id=25 identifier=' . $login_post['username']);
                }
                if ($gateResult['success']) {
                    $found_group = 'branch_25';
                }
            } catch (\Throwable $e) {
                log_message('error', '[RealUserLoginGate] EXCEPTION ' . $e->getMessage());
            }
            // --- END REAL USER LOGIN GATE ---

            if ($found_group === 'branch_25') {
                $CI =& get_instance();
                $new_db = $CI->load->database($found_group, TRUE);
                $CI->db->close();
                $CI->db = $new_db;
                $this->db = $new_db;
                $this->setting_model->db = $new_db;
                $this->user_model->db = $new_db;
                $this->student_model->db = $new_db;
                $this->customlib->db = $new_db;
                $this->config->set_item('active_db_group', $found_group);
            } else {
            // --- MULTI BRANCH STUDENT LOGIN FIX START ---
            include(APPPATH . 'config/database.php');
            if (isset($db) && is_array($db) && count($db) > 1) {
                $found_group = 'default';
                foreach ($db as $group_name => $config_item) {
                    if ($group_name === 'default') continue;
                    $test_db = @$this->load->database($group_name, TRUE);
                    if ($test_db && $test_db->conn_id) {
                        $test_db->select('users.id');
                        $test_db->from('users');
                        $test_db->join('students', 'students.id = users.user_id OR students.parent_id = users.id', 'left');
                        $test_db->where('users.password', $login_post['password']);
                        $test_db->group_start();
                        $test_db->where('users.username', $login_post['username']);
                        $test_db->or_where('students.admission_no', $login_post['username']);
                        $test_db->or_where('students.mobileno', $login_post['username']);
                        $test_db->or_where('students.email', $login_post['username']);
                        $test_db->or_where('students.guardian_phone', $login_post['username']);
                        $test_db->or_where('students.guardian_email', $login_post['username']);
                        $test_db->group_end();
                        $test_db->limit(1);
                        $query = $test_db->get();
                        if ($query && $query->num_rows() > 0) {
                            $found_group = $group_name;
                            $test_db->close();
                            break;
                        }
                        $test_db->close();
                    }
                }
                
                if ($found_group !== 'default') {
                    $CI =& get_instance();
                    $new_db = $CI->load->database($found_group, TRUE);
                    $CI->db->close();
                    $CI->db = $new_db;
                    $this->db = $new_db;
                    $this->setting_model->db = $new_db;
                    $this->user_model->db = $new_db;
                    $this->student_model->db = $new_db;
                    $this->customlib->db = $new_db;
                    $this->config->set_item('active_db_group', $found_group);
                }
            }
            // --- MULTI BRANCH STUDENT LOGIN FIX END ---
            }

            $login_details         = $this->user_model->checkLogin($login_post);

            if (isset($login_details) && !empty($login_details)) {
                $user = $login_details[0];

                if ($user->is_active == "yes") {
                    if ($user->role == "student" && $student_login_status==1) {
                        $result = $this->user_model->read_user_information($user->id);

                    } else if ($user->role == "parent" && $parent_login_status==1) {
                        if ($school[0]['parent_panel_login']) {
                            $result = $this->user_model->checkLoginParent($login_post);
                        } else {
                            $result = false;
                        }
                    }else{
                         $data['error_message'] = $this->lang->line('account_suspended');
                         $result = false;
                    } 

                    if ($result != false) {
                        $setting_result = $this->setting_model->get();
                        if ($result[0]->lang_id == 0) {
                            $language = array('lang_id' => $setting_result[0]['lang_id'], 'language' => $setting_result[0]['language']);
                            if ($setting_result[0]['is_rtl'] == 1) {
                                $is_rtl = "enabled";
                            } else {
                                $is_rtl = "disabled";
                            }
                        } else {
                            $language = array('lang_id' => $result[0]->lang_id, 'language' => $result[0]->language);
                            if ($setting_result[0]['is_rtl'] == 1) {
                                $is_rtl = "enabled";
                            } else {
                                $is_rtl = "disabled";
                            }
                        }
                        $image = '';
                        if ($result[0]->role == "parent") {
                            $username = $result[0]->guardian_name;
                            if ($result[0]->guardian_is == "father") {
                                $image = $result[0]->father_pic;
                            } else if ($result[0]->guardian_is == "mother") {
                                $image = $result[0]->mother_pic;
                            } else if ($result[0]->guardian_is == "other") {
                                $image = $result[0]->guardian_pic;
                            }
                        } elseif ($result[0]->role == "student") {
                            $image        = $result[0]->image;
                            $username     = $this->customlib->getFullName($result[0]->firstname, $result[0]->middlename, $result[0]->lastname, $this->sch_setting->middlename, $this->sch_setting->lastname);
                            $defaultclass = $this->user_model->get_studentdefaultClass($result[0]->user_id);
                            $this->customlib->setUserLog($result[0]->username, $result[0]->role, $defaultclass['id']);
                        }

                        $session_data = array(
                            'id'                     => $result[0]->id,
                            'login_username'         => $result[0]->username,
                            'student_id'             => $result[0]->user_id,
                            'role'                   => $result[0]->role,
                            'username'               => $username,
                            'currency'               => ( $result[0]->currency == 0) ? $setting_result[0]['currency_id']:  $result[0]->currency,
                            'currency_base_price'    => ( $result[0]->base_price == 0) ? $setting_result[0]['base_price']:  $result[0]->base_price,
                            'currency_format'        => $setting_result[0]['currency_format'],
                            'currency_symbol'        => ($result[0]->symbol == "0") ? $setting_result[0]['currency_symbol'] : $result[0]->symbol,
                            'currency_name'          => ($result[0]->currency_name == "0") ? $setting_result[0]['currency'] : $result[0]->currency_name,
                            'currency_place'         => $setting_result[0]['currency_place'],
                            'date_format'            => $setting_result[0]['date_format'],
                            'start_week'             => date("w", strtotime($setting_result[0]['start_week'])),
                            'timezone'               => $setting_result[0]['timezone'],
                            'sch_name'               => $setting_result[0]['name'],
                            'language'               => $language,
                            'is_rtl'                 => $is_rtl,
                            'theme'                  => $setting_result[0]['theme'],
                            'image'                  => $image,
                            'gender'                 => $result[0]->gender,
                            'db_array'               => ['base_url'           => $setting_result[0]['base_url'],
                                                     'folder_path'            => $setting_result[0]['folder_path'],
                                                     'db_group'               => (isset($found_group) ? $found_group : 'default')
                                                    ],
                            'superadmin_restriction' => $setting_result[0]['superadmin_restriction'],
							'admin_panel_whatsapp'   		=> $setting_result[0]['admin_panel_whatsapp'],
							'admin_panel_whatsapp_mobile'   => $setting_result[0]['admin_panel_whatsapp_mobile'],
							'admin_panel_whatsapp_from'   	=> $setting_result[0]['admin_panel_whatsapp_from'],
							'admin_panel_whatsapp_to'  		=> $setting_result[0]['admin_panel_whatsapp_to'],	

                        );

                        $this->session->set_userdata('student', $session_data);
                        if ($result[0]->role == "parent") {
                            $this->customlib->setUserLog($result[0]->username, $result[0]->role);
                        }
                        redirect('user/user/choose');
                    } else {
                        $data['error_message'] = $this->lang->line('account_suspended');
                        $this->load->view('userlogin', $data);
                    }
                } else {
                    $data['error_message'] = $this->lang->line('your_account_is_disabled_please_contact_to_administrator');
                    $this->load->view('userlogin', $data);
                }
            } else {
                $data['error_message'] = $this->lang->line('invalid_username_or_password');
                $this->load->view('userlogin', $data);
            }
        }
    }

    public function savemulticlass()
    {
        $student_id = '';
        $this->form_validation->set_rules('student_id', $this->lang->line('student'), 'trim|required|xss_clean');

        if ($this->form_validation->run() == false) {

            $msg = array(
                'student_id' => form_error('student_id'),
            );

            $array = array('status' => '0', 'error' => $msg, 'message' => '');
        } else {

            $data = array(
                'student_id' => date('Y-m-d', strtotime($this->input->post('student_id'))),
            );

            $array = array('status' => 'success', 'error' => '', 'message' => $this->lang->line('success_message'));
        }
        echo json_encode($array);
    }

    public function check_captcha($captcha)
    {
        if ($captcha != $this->session->userdata('captchaCode')):
            $this->form_validation->set_message('check_captcha', $this->lang->line('incorrect_captcha'));
            return false;
        else:
            return true;
        endif;
    }

    public function refreshCaptcha()
    {
        $captcha = $this->captchalib->generate_captcha();
        echo $captcha['image'];
    }

}
