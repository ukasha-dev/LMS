<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Stdtransfer extends Admin_Controller
{
    protected $balance_group;
    protected $balance_type;

    public function __construct()
    {
        parent::__construct();
        $this->load->model("classteacher_model");
        $this->load->model("studentfeemaster_model");
        $this->load->config('ci-blog');
        $this->balance_group = $this->config->item('ci_balance_group');
        $this->balance_type = $this->config->item('ci_balance_type');
        $this->sch_setting_detail = $this->setting_model->getSetting();

        // Naya Multibranch Model Load Karein (YE ZAROORI HAI)
        $this->load->model("multibranch/multibranch_model");
    }

    public function index()
    {
        if (!$this->rbac->hasPrivilege('promote_student', 'can_view')) {
            access_denied();
        }
        $this->session->set_userdata('top_menu', 'Academics');
        $this->session->set_userdata('sub_menu', 'stdtransfer/index');
        $data['title'] = 'Exam Schedule';
        $class = $this->class_model->get('', $classteacher = 'yes');
        $data['classlist'] = $class;
        $userdata = $this->customlib->getUserData();
        $data['sch_setting'] = $this->sch_setting_detail;
        $session_result = $this->session_model->get();
        $data['sessionlist'] = $session_result;
        $this->form_validation->set_rules('class_id', $this->lang->line('class'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('class_promote_id', $this->lang->line('class'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('section_id', $this->lang->line('section'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('section_promote_id', $this->lang->line('section'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('session_id', $this->lang->line('promote_in_session'), 'trim|required|xss_clean');
        if ($this->form_validation->run() == true) {
            $class = $this->input->post('class_id');
            $section = $this->input->post('section_id');
            $session = $this->input->post('session_id');
            $class_promote = $this->input->post('class_promote_id');
            $section_promote = $this->input->post('section_promote_id');
            $data['class_post'] = $class;
            $data['section_post'] = $section;
            $data['class_promoted_post'] = $class_promote;
            $data['section_promoted_post'] = $section_promote;
            $data['session_promoted_post'] = $session;

            $resultlist = $this->student_model->searchNonPromotedStudents($class, $section, $session, $class_promote, $section_promote);

            $data['resultlist'] = $resultlist;
        }

        $this->load->view('layout/header', $data);
        $this->load->view('admin/stdtransfer/stdtransfer', $data);
        $this->load->view('layout/footer', $data);
    }

    public function promote()
    {
        $this->form_validation->set_rules('session_id', $this->lang->line('session'), 'required|trim|xss_clean');
        $this->form_validation->set_rules('class_promote_id', $this->lang->line('class'), 'required|trim|xss_clean');
        $this->form_validation->set_rules('section_promote_id', $this->lang->line('section'), 'required|trim|xss_clean');
        $this->form_validation->set_rules('student_list[]', $this->lang->line('student'), 'required|trim|xss_clean');
        if ($this->form_validation->run() == false) {
            $errors = array(
                'session_id' => form_error('session_id'),
                'class_promote_id' => form_error('class_promote_id'),
                'section_promote_id' => form_error('section_promote_id'),
                'student_list' => form_error('student_list[]'),
            );
            echo json_encode(array('status' => 'fail', 'msg' => $errors));
        } else {
            $student_list = $this->input->post('student_list');
            $current_session = $this->setting_model->getCurrentSession();

            $class_post = $this->input->post('class_post');
            $section_post = $this->input->post('section_post');

            if (!empty($student_list) && isset($student_list)) {

                foreach ($student_list as $key => $value) {
                    $student_id = $value;
                    $result = $this->input->post('result_' . $value);
                    $session_status = $this->input->post('next_working_' . $value);
                    if ($result == "pass" && $session_status == "countinue") {
                        $promoted_class = $this->input->post('class_promote_id');
                        $promoted_section = $this->input->post('section_promote_id');
                        $promoted_session = $this->input->post('session_id');
                        $data_new = array(
                            'student_id' => $student_id,
                            'class_id' => $promoted_class,
                            'section_id' => $promoted_section,
                            'session_id' => $promoted_session,
                            'transport_fees' => 0,
                            'fees_discount' => 0,
                        );
                        $this->student_model->add_student_session($data_new);
                    } elseif ($result == "fail" && $session_status == "countinue") {
                        $promoted_session = $this->input->post('session_id');
                        $class_post = $this->input->post('class_post');
                        $section_post = $this->input->post('section_post');
                        $data_new = array(
                            'student_id' => $student_id,
                            'class_id' => $class_post,
                            'section_id' => $section_post,
                            'session_id' => $promoted_session,
                            'transport_fees' => 0,
                            'fees_discount' => 0,
                        );
                        $this->student_model->add_student_session($data_new);
                    } elseif ($session_status == "leave") {

                        $leave_student = array(
                            'is_leave' => 1,
                            'session_id' => $current_session,
                            'student_id' => $student_id,
                            'class_id' => $class_post,
                            'section_id' => $section_post,
                        );

                        $this->studentsession_model->updatePromote($leave_student);

                        $alumni_data = array(
                            'student_id' => $student_id,
                            'is_alumni' => 1,
                        );

                        $this->student_model->alumni_student_status($alumni_data);
                    }
                }
            }
            echo json_encode(array('status' => 'success', 'msg' => ""));
        }
    }

    public function campus_transfer_search()
    {
        if (!$this->rbac->hasPrivilege('student', 'can_view')) {
            access_denied();
        }

        $this->session->set_userdata('top_menu', 'Academics');
        $this->session->set_userdata('sub_menu', 'admin/stdtransfer/campus_transfer_search');

        $this->load->model('multibranch/multibranch_model');

        /* =========================================
           1️⃣ Active Branch Detection (From Session)
        ==========================================*/
        $db_group = $this->session->userdata['admin']['db_array']['db_group'];
        $active_branch_id = 0;
        if ($db_group != 'default' && !empty($db_group)) {
            $active_branch_id = (int) filter_var($db_group, FILTER_SANITIZE_NUMBER_INT);
        }

        /* =========================================
           2️⃣ Branch List Construction with Real Names
        ==========================================*/
        $branches = $this->multibranch_model->get();
        $data['branchlist'] = [];

        echo "<script>console.group('CAMPUS TRANSFER DEBUG');</script>";
        echo "<script>console.log('Detected Active Branch ID:', " . $active_branch_id . ");</script>";

        // Agar hum Sub-Campus mein hain (ID > 0), to Main Campus load karein
        if ($active_branch_id > 0) {
            // Main Database manually load karein taake Real Name mil sake
            $main_db = $this->load->database('default', TRUE);
            $main_setting = $main_db->select('name')->get('sch_settings')->row();
            $main_campus_real_name = ($main_setting) ? $main_setting->name : "Main Campus";

            $data['branchlist'][] = [
                'id' => 0,
                'branch_name' => $main_campus_real_name . " (Main)"
            ];
            echo "<script>console.log('Log: Sub-Campus login detected. Added Main Campus: " . $main_campus_real_name . "');</script>";
        } else {
            echo "<script>console.log('Log: Main Campus login detected. Skipping Main Campus from list.');</script>";
        }

        // Baaki sub-branches loop karein
        if (!empty($branches)) {
            foreach ($branches as $branch) {
                // Current login branch ko skip karein
                if ((int) $branch->id === $active_branch_id) {
                    continue;
                }
                $data['branchlist'][] = [
                    'id' => (int) $branch->id,
                    'branch_name' => $branch->branch_name
                ];
            }
        }

        // Final JSON List log karein
        $json_list = json_encode($data['branchlist']);
        echo "<script>
        console.log('Final Branch List Array:', $json_list);
        console.groupEnd();
    </script>";

        /* =========================================
           3️⃣ Student & Class Data (Current DB)
        ==========================================*/
        $data['classlist'] = $this->class_model->get();
        $data['resultlist'] = [];

        if ($this->input->method() === 'post') {
            $class_id = (int) $this->input->post('class_id');
            $section_id = (int) $this->input->post('section_id');
            if ($class_id && $section_id) {
                $data['resultlist'] = $this->student_model->searchByClassSection($class_id, $section_id);
            }
        }

        $this->load->view('layout/header');
        $this->load->view('admin/stdtransfer/campus_move', $data);
        $this->load->view('layout/footer');
    }
    // public function getClassesByBranch() {
//     $branch_id = $this->input->post('branch_id');

    //     // 1. Check karein ke branch mil rahi hai ya nahi
//     $branch_details = $this->db->get_where('multi_branch', array('id' => $branch_id))->row_array();

    //     if (!$branch_details) {
//         // Agar 'multi_branch' table nahi hai to 'branches' table check karein
//         $branch_details = $this->db->get_where('branches', array('id' => $branch_id))->row_array();
//     }

    //     if ($branch_details) {
//         // --- COLUMN NAME CHECK ---
//         // Agar aapke table mein column names 'db_username' ki jagah kuch aur hain, 
//         // to niche diye gaye names ko unse badal dein.
//         $config_db = array(
//             'hostname' => isset($branch_details['hostname']) ? $branch_details['hostname'] : 'localhost',
//             'username' => isset($branch_details['db_username']) ? $branch_details['db_username'] : $branch_details['username'], 
//             'password' => isset($branch_details['db_password']) ? $branch_details['db_password'] : $branch_details['password'],
//             'database' => isset($branch_details['db_name']) ? $branch_details['db_name'] : $branch_details['database_name'],
//             'dbdriver' => 'mysqli',
//             'pconnect' => FALSE,
//             'db_debug' => FALSE
//         );

    //         // Dusra DB load karne ki koshish
//         $target_db = $this->load->database($config_db, TRUE);

    //         if ($target_db->conn_id) {
//             $classes = $target_db->get('classes')->result_array();
//             echo json_encode($classes);
//         } else {
//             // Agar connection nahi ho raha
//             echo json_encode(array(array('id' => '', 'class' => 'DB Connection Error')));
//         }
//     } else {
//         echo json_encode(array(array('id' => '', 'class' => 'Branch Not Found')));
//     }
// }

    public function getClassesByBranch()
    {
        $branch_id = $this->input->post('branch_id');

        // FORCE SWITCH: Agar Branch ID 0 hai to default connection ko reload karein
        if ($branch_id == '0' || empty($branch_id)) {
            // 'default' wo connection hai jo aapki main database.php mein define hai
            $db_main = $this->load->database('default', TRUE);

            $query = $db_main->get('classes');
            $result = $query->result_array();

            $db_main->close(); // Connection fauran band karein
            echo json_encode($result);
            return;
        }

        // CASE: Sub-Branch logic
        $branch = $this->multibranch_model->get($branch_id);
        if ($branch) {
            $config_app = array(
                'dsn' => '',
                'hostname' => $branch->hostname,
                'username' => $branch->username,
                'password' => $branch->password,
                'database' => $branch->database_name,
                'dbdriver' => 'mysqli',
                'pconnect' => FALSE,
                'db_debug' => FALSE
            );

            $db_sub = $this->load->database($config_app, TRUE);

            if ($db_sub->conn_id) {
                $query = $db_sub->get('classes');
                $result = $query->result_array();
                $db_sub->close();
                echo json_encode($result);
            } else {
                echo json_encode(array());
            }
        }
    }

    // public function getSectionsByBranch()
    // {
    //     $branch_id = $this->input->post('branch_id');
    //     $class_id = $this->input->post('class_id');

    //     // Branch details fetch karein
    //     $branch_details = $this->db->get_where('multi_branch', array('id' => $branch_id))->row_array();
    //     if (!$branch_details) {
    //         $branch_details = $this->db->get_where('branches', array('id' => $branch_id))->row_array();
    //     }

    //     if ($branch_details) {
    //         $config_db = array(
    //             'hostname' => isset($branch_details['hostname']) ? $branch_details['hostname'] : 'localhost',
    //             'username' => isset($branch_details['db_username']) ? $branch_details['db_username'] : $branch_details['username'],
    //             'password' => isset($branch_details['db_password']) ? $branch_details['db_password'] : $branch_details['password'],
    //             'database' => isset($branch_details['db_name']) ? $branch_details['db_name'] : $branch_details['database_name'],
    //             'dbdriver' => 'mysqli',
    //             'pconnect' => FALSE,
    //             'db_debug' => FALSE
    //         );

    //         $target_db = $this->load->database($config_db, TRUE);

    //         if ($target_db->conn_id) {
    //             // SMART SCHOOL LOGIC: Class Sections Table se data uthana
    //             // Hum directly sections aur class_sections ko join karenge
    //             $target_db->select('sections.id, sections.section');
    //             $target_db->from('class_sections');
    //             $target_db->join('sections', 'sections.id = class_sections.section_id');
    //             $target_db->where('class_sections.class_id', $class_id);
    //             $sections = $target_db->get()->result_array();

    //             echo json_encode($sections);
    //         } else {
    //             echo json_encode(array());
    //         }
    //     } else {
    //         echo json_encode(array());
    //     }
    // }


    public function getSectionsByBranch()
    {
        $branch_id = $this->input->post('branch_id');
        $class_id = $this->input->post('class_id');

        // CASE 1: Agar Main Campus hai (ID 0)
        if ($branch_id == '0' || empty($branch_id)) {
            $db_main = $this->load->database('default', TRUE);

            $db_main->select('sections.id, sections.section');
            $db_main->from('class_sections');
            $db_main->join('sections', 'sections.id = class_sections.section_id');
            $db_main->where('class_sections.class_id', $class_id);
            $sections = $db_main->get()->result_array();

            $db_main->close();
            echo json_encode($sections);
            return;
        }

        // CASE 2: Agar Sub-Branch hai
        $branch = $this->multibranch_model->get($branch_id);

        if ($branch) {
            $config_db = array(
                'hostname' => $branch->hostname,
                'username' => $branch->username,
                'password' => $branch->password,
                'database' => $branch->database_name,
                'dbdriver' => 'mysqli',
                'pconnect' => FALSE,
                'db_debug' => FALSE
            );

            $target_db = $this->load->database($config_db, TRUE);

            if ($target_db->conn_id) {
                $target_db->select('sections.id, sections.section');
                $target_db->from('class_sections');
                $target_db->join('sections', 'sections.id = class_sections.section_id');
                $target_db->where('class_sections.class_id', $class_id);
                $sections = $target_db->get()->result_array();

                $target_db->close();
                echo json_encode($sections);
            } else {
                echo json_encode(array());
            }
        } else {
            echo json_encode(array());
        }
    }

    // public function move_to_campus() {
//     $target_branch_id = $this->input->post('target_branch_id');
//     $target_class_id = $this->input->post('target_class_id');
//     $target_section_id = $this->input->post('target_section_id');
//     $student_ids = $this->input->post('student_list');

    //     if (empty($student_ids)) {
//         echo json_encode(array("status" => "fail", "msg" => "No students selected."));
//         return;
//     }

    //     $branch = $this->db->get_where('multi_branch', array('id' => $target_branch_id))->row_array();

    //     if (!$branch) {
//         echo json_encode(array("status" => "fail", "msg" => "Branch not found."));
//         return;
//     }

    //     // --- COLUMN NAME AUTO-DETECTION ---
//     // Agar db_username nahi milta to sirf username use karega
//     $db_host = isset($branch['hostname']) ? $branch['hostname'] : 'localhost';
//     $db_user = isset($branch['db_username']) ? $branch['db_username'] : (isset($branch['username']) ? $branch['username'] : '');
//     $db_pass = isset($branch['db_password']) ? $branch['db_password'] : (isset($branch['password']) ? $branch['password'] : '');
//     $db_name = isset($branch['db_name']) ? $branch['db_name'] : (isset($branch['database_name']) ? $branch['database_name'] : '');

    //     $config_target = array(
//         'hostname' => $db_host,
//         'username' => $db_user,
//         'password' => $db_pass,
//         'database' => $db_name,
//         'dbdriver' => 'mysqli',
//         'pconnect' => FALSE,
//         'db_debug' => FALSE
//     );

    //     $target_db = $this->load->database($config_target, TRUE);

    //     if (!$target_db->conn_id) {
//         echo json_encode(array("status" => "fail", "msg" => "Connection failed. Check if database settings in multi_branch table are correct."));
//         return;
//     }

    //     // Baaqi logic (Transfer ka) wahi rahega jo pehle tha...
//     // [Agli lines mein wahi insert logic use karein jo pichle step mein diya tha]

    //     $target_session = $target_db->get_where('sessions', array('is_active' => 1))->row_array();
//     $success_count = 0;

    //     foreach ($student_ids as $student_id) {
//         $student_data = $this->db->get_where('students', array('id' => $student_id))->row_array();
//         if ($student_data) {
//             $old_id = $student_data['id'];
//             unset($student_data['id']);

    //             if($target_db->insert('students', $student_data)){
//                 $new_student_id = $target_db->insert_id();

    //                 $session_data = array(
//                     'student_id' => $new_student_id,
//                     'class_id'   => $target_class_id,
//                     'section_id' => $target_section_id,
//                     'session_id' => ($target_session) ? $target_session['id'] : 1,
//                     'is_active'  => 'yes'
//                 );
//                 $target_db->insert('student_session', $session_data);

    //                 // Success: Purane student ko deactivate karein
//                 $this->db->where('id', $old_id)->update('students', array('is_active' => 'no'));
//                 $success_count++;
//             }
//         }
//     }

    //     echo json_encode(array("status" => "success", "msg" => $success_count . " Students moved successfully!"));
// }

    public function move_to_campus()
    {
        $target_branch_id = $this->input->post('target_branch_id');
        $target_session_name = $this->input->post('target_session_name');
        $target_class_id = $this->input->post('target_class_id');
        $target_section_id = $this->input->post('target_section_id');
        $student_ids = $this->input->post('student_list');

        if (empty($student_ids)) {
            echo json_encode(array("status" => "fail", "msg" => "No students selected."));
            return;
        }

        if ($target_branch_id == '0') {
            $target_db = $this->load->database('default', TRUE);
        } else {
            $branch = $this->multibranch_model->get($target_branch_id);

            if (!$branch) {
                echo json_encode(array("status" => "fail", "msg" => "Branch records not found in system."));
                return;
            }

            $config_target = array(
                'hostname' => $branch->hostname,
                'username' => $branch->username,
                'password' => $branch->password,
                'database' => $branch->database_name,
                'dbdriver' => 'mysqli',
                'pconnect' => FALSE,
                'db_debug' => FALSE
            );
            $target_db = $this->load->database($config_target, TRUE);
        }

        if (!$target_db || !$target_db->conn_id) {
            echo json_encode(array("status" => "fail", "msg" => "Cannot connect to Target Database."));
            return;
        }

        $session_check = $target_db->get_where('sessions', array('session' => $target_session_name))->row_array();
        if (!$session_check) {
            $session_check = $target_db->get_where('sessions', array('is_active' => 1))->row_array();
        }
        if (!$session_check) {
            echo json_encode(array("status" => "fail", "msg" => "Target Session not found."));
            return;
        }

        $target_session_id = (int)$session_check['id'];
        $success_count = 0;
        $linked_rows_migrated = 0;

        foreach ($student_ids as $student_id) {
            $student_id = (int)$student_id;
            if ($student_id <= 0) {
                continue;
            }

            $student_data = $this->db->get_where('students', array('id' => $student_id))->row_array();
            if (!$student_data) {
                continue;
            }

            $old_student_id = (int)$student_data['id'];
            $old_student_session_id = $this->get_source_student_session_id($old_student_id);
            $source_pending_balance = $this->get_source_student_pending_balance($old_student_session_id);
            $admission_no = isset($student_data['admission_no']) ? trim((string)$student_data['admission_no']) : '';
            if ($admission_no !== '') {
                $this->remove_target_student_if_duplicate_admission_no($target_db, $admission_no);
            }
            unset($student_data['id']);

            if (!$target_db->insert('students', $student_data)) {
                continue;
            }

            $new_student_id = (int)$target_db->insert_id();

            $session_entry = array(
                'session_id' => $target_session_id,
                'student_id' => $new_student_id,
                'class_id' => $target_class_id,
                'section_id' => $target_section_id,
                'transport_fees' => 0,
                'fees_discount' => 0,
                'is_active' => 'yes'
            );

            if (!$target_db->insert('student_session', $session_entry)) {
                $target_db->where('id', $new_student_id);
                $target_db->delete('students');
                continue;
            }

            $new_student_session_id = (int)$target_db->insert_id();

            $this->create_target_carry_forward_fee(
                $target_db,
                $new_student_session_id,
                $target_session_id,
                $source_pending_balance
            );

            $linked_rows_migrated += $this->migrate_student_linked_data(
                $target_db,
                $old_student_id,
                $new_student_id,
                $old_student_session_id,
                $new_student_session_id
            );

            $disable_reason_id = $this->get_or_create_campus_transfer_disable_reason_id();
            $disable_data = array(
                'is_active' => 'no',
                'note' => 'Transferred to Branch ID: ' . $target_branch_id . ' on Session: ' . $target_session_name
            );
            if ($this->db->field_exists('dis_reason', 'students')) {
                $disable_data['dis_reason'] = $disable_reason_id;
            }
            if ($this->db->field_exists('dis_note', 'students')) {
                $disable_data['dis_note'] = 'Campus Transfer';
            }
            if ($this->db->field_exists('disable_at', 'students')) {
                $disable_data['disable_at'] = date('Y-m-d');
            }

            $this->db->where('id', $old_student_id)->update('students', $disable_data);

            $success_count++;
        }

        $target_db->close();

        if ($success_count > 0) {
            echo json_encode(array(
                "status" => "success",
                "msg" => $success_count . " Student(s) moved successfully. Linked records migrated: " . $linked_rows_migrated
            ));
        } else {
            echo json_encode(array("status" => "fail", "msg" => "Transfer failed. No records were inserted."));
        }
    }

    private function get_source_student_session_id($student_id)
    {
        $student_id = (int)$student_id;
        if ($student_id <= 0) {
            return 0;
        }

        $current_session_id = $this->setting_model->getCurrentSession();
        $session_row = $this->db->get_where('student_session', array(
            'student_id' => $student_id,
            'session_id' => $current_session_id
        ))->row_array();

        if (!$session_row) {
            $this->db->from('student_session');
            $this->db->where('student_id', $student_id);
            $this->db->order_by('id', 'DESC');
            $this->db->limit(1);
            $session_row = $this->db->get()->row_array();
        }

        return ($session_row && isset($session_row['id'])) ? (int)$session_row['id'] : 0;
    }

    private function get_or_create_campus_transfer_disable_reason_id()
    {
        if (!$this->db->table_exists('disable_reason')) {
            return 0;
        }

        $existing = $this->db->get_where('disable_reason', array('reason' => 'Campus Transfer'))->row_array();
        if ($existing && isset($existing['id'])) {
            return (int)$existing['id'];
        }

        $this->db->insert('disable_reason', array('reason' => 'Campus Transfer'));
        return (int)$this->db->insert_id();
    }

    private function remove_target_student_if_duplicate_admission_no($target_db, $admission_no)
    {
        if (empty($admission_no) || !$target_db->table_exists('students')) {
            return;
        }

        $existing_students = $target_db->get_where('students', array('admission_no' => $admission_no))->result_array();
        if (empty($existing_students)) {
            return;
        }

        foreach ($existing_students as $student_row) {
            $target_student_id = isset($student_row['id']) ? (int)$student_row['id'] : 0;
            if ($target_student_id <= 0) {
                continue;
            }

            if ($target_db->table_exists('users')) {
                $target_db->where('role', 'student');
                $target_db->where('user_id', $target_student_id);
                $target_db->delete('users');
            }

            $target_db->where('student_id', $target_student_id);
            $target_db->delete('student_session');

            $target_db->where('id', $target_student_id);
            $target_db->delete('students');
        }
    }

    private function get_source_student_pending_balance($student_session_id)
    {
        $student_session_id = (int)$student_session_id;
        if ($student_session_id <= 0) {
            return 0;
        }

        $student_total_fees = $this->studentfeemaster_model->getPreviousStudentFees($student_session_id);
        if (empty($student_total_fees)) {
            return 0;
        }

        $totalfee = 0;
        $deposit = 0;
        $discount = 0;
        $fine = 0;

        foreach ($student_total_fees as $student_total_fees_value) {
            if (empty($student_total_fees_value->fees)) {
                continue;
            }

            foreach ($student_total_fees_value->fees as $each_fee_value) {
                $totalfee += isset($each_fee_value->amount) ? (float)$each_fee_value->amount : 0;

                $fee_type = isset($each_fee_value->type) ? strtolower(trim((string)$each_fee_value->type)) : '';
                $is_discount_excluded_type = (
                    strpos($fee_type, 'previous session balance') !== false
                    || strpos($fee_type, 'annual fund') !== false
                    || strpos($fee_type, 'admission fee') !== false
                );

                if (!$is_discount_excluded_type) {
                    $discount += isset($each_fee_value->pre_discount) ? (float)$each_fee_value->pre_discount : 0;
                }

                $amount_detail = json_decode($each_fee_value->amount_detail);
                if (!empty($amount_detail)) {
                    foreach ($amount_detail as $amount_detail_value) {
                        $deposit += isset($amount_detail_value->amount) ? (float)$amount_detail_value->amount : 0;
                        $fine += isset($amount_detail_value->amount_fine) ? (float)$amount_detail_value->amount_fine : 0;
                    }
                }
            }
        }

        $balance = ($totalfee + $fine) - ($deposit + $discount);
        return ($balance > 0) ? round($balance, 2) : 0;
    }

    private function create_target_carry_forward_fee($target_db, $student_session_id, $session_id, $balance_amount)
    {
        $student_session_id = (int)$student_session_id;
        $session_id = (int)$session_id;
        $balance_amount = round((float)$balance_amount, 2);

        if ($student_session_id <= 0 || $session_id <= 0 || $balance_amount <= 0) {
            return false;
        }

        $balance_group = !empty($this->balance_group) ? $this->balance_group : 'Balance Master';
        $balance_type = !empty($this->balance_type) ? $this->balance_type : 'Previous Session Balance';
        $due_date = date('Y-m-d');

        if ($target_db->table_exists('sch_settings')) {
            $target_setting = $target_db->select('fee_due_days')->get('sch_settings')->row_array();
            if (!empty($target_setting) && isset($target_setting['fee_due_days']) && (int)$target_setting['fee_due_days'] > 0) {
                $due_date = date('Y-m-d', strtotime('+' . (int)$target_setting['fee_due_days'] . ' day'));
            }
        }

        $fee_group_id = $this->get_or_create_target_fee_group($target_db, $balance_group);
        $fee_type_id = $this->get_or_create_target_fee_type($target_db, $balance_type);
        $fee_session_group_id = $this->get_or_create_target_fee_session_group($target_db, $fee_group_id, $session_id);
        $this->get_or_create_target_fee_group_type($target_db, $fee_session_group_id, $fee_group_id, $fee_type_id, $session_id, $due_date);
        $this->upsert_target_student_fee_master($target_db, $student_session_id, $fee_session_group_id, $balance_amount);

        return true;
    }

    private function get_or_create_target_fee_group($target_db, $group_name)
    {
        $row = $target_db->select('id')->get_where('fee_groups', array('name' => $group_name))->row_array();
        if (!empty($row) && isset($row['id'])) {
            return (int)$row['id'];
        }

        $target_db->insert('fee_groups', array(
            'name' => $group_name,
            'is_system' => 1,
        ));

        return (int)$target_db->insert_id();
    }

    private function get_or_create_target_fee_type($target_db, $type_name)
    {
        $row = $target_db->select('id')->get_where('feetype', array('type' => $type_name))->row_array();
        if (!empty($row) && isset($row['id'])) {
            return (int)$row['id'];
        }

        $target_db->insert('feetype', array(
            'type' => $type_name,
            'code' => $type_name,
            'is_system' => 1,
        ));

        return (int)$target_db->insert_id();
    }

    private function get_or_create_target_fee_session_group($target_db, $fee_group_id, $session_id)
    {
        $row = $target_db->select('id')->get_where('fee_session_groups', array(
            'fee_groups_id' => $fee_group_id,
            'session_id' => $session_id,
        ))->row_array();

        if (!empty($row) && isset($row['id'])) {
            return (int)$row['id'];
        }

        $target_db->insert('fee_session_groups', array(
            'fee_groups_id' => $fee_group_id,
            'session_id' => $session_id,
        ));

        return (int)$target_db->insert_id();
    }

    private function get_or_create_target_fee_group_type($target_db, $fee_session_group_id, $fee_group_id, $fee_type_id, $session_id, $due_date)
    {
        $data = array(
            'session_id' => $session_id,
            'fee_groups_id' => $fee_group_id,
            'feetype_id' => $fee_type_id,
            'fee_session_group_id' => $fee_session_group_id,
            'due_date' => $due_date,
        );

        $row = $target_db->select('id')->get_where('fee_groups_feetype', array(
            'fee_session_group_id' => $fee_session_group_id,
            'feetype_id' => $fee_type_id,
        ))->row_array();

        if (!empty($row) && isset($row['id'])) {
            $target_db->where('id', (int)$row['id'])->update('fee_groups_feetype', $data);
            return (int)$row['id'];
        }

        $target_db->insert('fee_groups_feetype', $data);
        return (int)$target_db->insert_id();
    }

    private function upsert_target_student_fee_master($target_db, $student_session_id, $fee_session_group_id, $balance_amount)
    {
        $student_session_id = (int)$student_session_id;
        $fee_session_group_id = (int)$fee_session_group_id;
        $balance_amount = round((float)$balance_amount, 2);

        if ($student_session_id <= 0 || $fee_session_group_id <= 0 || $balance_amount <= 0) {
            return 0;
        }

        $data = array(
            'student_session_id' => $student_session_id,
            'fee_session_group_id' => $fee_session_group_id,
            'amount' => $balance_amount,
            'pre_discount' => 0,
            'is_system' => 1,
        );

        $row = $target_db->select('id')->get_where('student_fees_master', array(
            'student_session_id' => $student_session_id,
            'fee_session_group_id' => $fee_session_group_id,
        ))->row_array();

        if (!empty($row) && isset($row['id'])) {
            $target_db->where('id', (int)$row['id'])->update('student_fees_master', $data);
            return (int)$row['id'];
        }

        $target_db->insert('student_fees_master', $data);
        return (int)$target_db->insert_id();
    }

    private function migrate_student_linked_data($target_db, $old_student_id, $new_student_id, $old_student_session_id, $new_student_session_id)
    {
        $migrated_rows = 0;

        $skip_tables = array(
            'students',
            'student_session',
            'student_fees_master',
            'student_transport_fees',
            'student_fees_deposite',
            'student_fees_processing'
        );

        $migrated_rows += $this->copy_generic_student_related_tables(
            $target_db,
            $old_student_id,
            $new_student_id,
            $old_student_session_id,
            $new_student_session_id,
            $skip_tables
        );

        $migrated_rows += $this->copy_student_user_record($target_db, $old_student_id, $new_student_id);

        return $migrated_rows;
    }

    private function copy_generic_student_related_tables($target_db, $old_student_id, $new_student_id, $old_session_id, $new_session_id, $skip_tables)
    {
        $inserted = 0;
        $source_tables = $this->db->list_tables();
        $target_tables = array_flip($target_db->list_tables());

        foreach ($source_tables as $table) {
            if (!isset($target_tables[$table]) || in_array($table, $skip_tables, true)) {
                continue;
            }

            $source_fields = $this->db->list_fields($table);
            $target_fields = $target_db->list_fields($table);

            $has_student_id = in_array('student_id', $source_fields, true) && in_array('student_id', $target_fields, true);
            $has_student_session_id = in_array('student_session_id', $source_fields, true) && in_array('student_session_id', $target_fields, true);

            if (!$has_student_id && !$has_student_session_id) {
                continue;
            }

            $this->db->from($table);

            if ($has_student_id && $has_student_session_id && $old_session_id > 0) {
                $this->db->group_start();
                $this->db->where('student_id', $old_student_id);
                $this->db->or_where('student_session_id', $old_session_id);
                $this->db->group_end();
            } elseif ($has_student_id) {
                $this->db->where('student_id', $old_student_id);
            } elseif ($has_student_session_id && $old_session_id > 0) {
                $this->db->where('student_session_id', $old_session_id);
            } else {
                continue;
            }

            $rows = $this->db->get()->result_array();
            if (empty($rows)) {
                continue;
            }

            $target_field_flip = array_flip($target_fields);

            foreach ($rows as $row) {
                if (isset($row['id'])) {
                    unset($row['id']);
                }

                if ($has_student_id) {
                    $row['student_id'] = $new_student_id;
                }
                if ($has_student_session_id && $new_session_id > 0) {
                    $row['student_session_id'] = $new_session_id;
                }

                $insert_data = array_intersect_key($row, $target_field_flip);
                if (empty($insert_data)) {
                    continue;
                }

                if ($target_db->insert($table, $insert_data)) {
                    $inserted++;
                }
            }
        }

        return $inserted;
    }

    private function copy_student_user_record($target_db, $old_student_id, $new_student_id)
    {
        $table = 'users';
        if (!$this->db->table_exists($table) || !$target_db->table_exists($table)) {
            return 0;
        }

        $target_fields = $target_db->list_fields($table);
        $target_field_flip = array_flip($target_fields);

        $user_row = $this->db
            ->where('user_id', (int)$old_student_id)
            ->where('role', 'student')
            ->get($table)
            ->row_array();

        if (empty($user_row)) {
            return 0;
        }

        if (isset($user_row['id'])) {
            unset($user_row['id']);
        }
        $user_row['user_id'] = (int)$new_student_id;

        $insert_data = array_intersect_key($user_row, $target_field_flip);
        if (empty($insert_data)) {
            return 0;
        }

        if ($target_db->insert($table, $insert_data)) {
            return 1;
        }

        return 0;
    }
}
