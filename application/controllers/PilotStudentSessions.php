<?php

defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/Pilot_Controller.php';

class PilotStudentSessions extends Pilot_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database('school_saas_pilot');
        require_once APPPATH . 'core/Tenant_Model.php';
        $this->load->model('Tenant_Model', 'tenant_model');
    }

    public function index()
    {
        $sessions = $this->tenant_model->tenantGetAll('student_session');
        $students = $this->tenant_model->tenantGetAll('students');
        $classes = $this->tenant_model->tenantGetAll('classes');
        $sections = $this->tenant_model->tenantGetAll('sections');

        $studentsById = [];
        foreach ($students as $student) {
            $studentsById[$student['id']] = trim($student['firstname'] . ' ' . ($student['lastname'] ?? ''));
        }

        $classesById = [];
        foreach ($classes as $class) {
            $classesById[$class['id']] = $class['class'];
        }

        $sectionsById = [];
        foreach ($sections as $section) {
            $sectionsById[$section['id']] = $section['section'];
        }

        $rows = [];
        foreach ($sessions as $session) {
            $rows[] = [
                'student' => $studentsById[$session['student_id']] ?? 'Unknown',
                'class' => $classesById[$session['class_id']] ?? 'Unknown',
                'section' => $sectionsById[$session['section_id']] ?? 'Unknown',
            ];
        }

        $this->load->view('pilot_student_sessions', ['rows' => $rows]);
    }
}
