<?php

defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/Pilot_Controller.php';

class PilotStudents extends Pilot_Controller
{
    public function __construct()
    {
        parent::__construct();
        // No `true` return flag here on purpose: that would return the
        // connection object without assigning it to $this->db, and
        // Tenant_Model reads $this->db->hostname/username/password/database
        // to build its own PDO connection. This form assigns the
        // school_saas_pilot connection to the shared $this->db that both
        // this controller and Tenant_Model reference.
        $this->load->database('school_saas_pilot');
        // Deviation from the brief (documented, not silent): Tenant_Model.php
        // lives in application/core/ per Task 6 Step 1. CI3's Loader::model()
        // only searches application/models/ (system/core/Loader.php ~L328-350)
        // and never application/core/, so $this->load->model() alone cannot
        // find this class and throws "Unable to locate the model you have
        // specified: Tenant_Model". Requiring the file first makes
        // class_exists('Tenant_Model', false) true, which makes the loader
        // skip its own file search and just instantiate the already-declared
        // class (it still validates it's a CI_Model subclass).
        require_once APPPATH . 'core/Tenant_Model.php';
        $this->load->model('Tenant_Model', 'tenant_model');
    }

    public function index()
    {
        $students = $this->tenant_model->tenantGetAll('students');
        $this->load->view('pilot_students', ['students' => $students]);
    }

    public function add($firstname)
    {
        $newId = $this->tenant_model->tenantInsert('students', [
            'firstname' => $firstname,
            'parent_id' => 0,
        ]);
        echo "Inserted student id={$newId}\n";
    }
}
