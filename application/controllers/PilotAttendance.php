<?php

defined('BASEPATH') or exit('No direct script access allowed');

class PilotAttendance extends CI_Controller
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
        $attendances = $this->tenant_model->tenantGetAll('student_attendences');
        $sessions = $this->tenant_model->tenantGetAll('student_session');
        $students = $this->tenant_model->tenantGetAll('students');
        $types = $this->tenant_model->tenantGetAll('attendence_type');

        $studentIdBySessionId = [];
        foreach ($sessions as $session) {
            $studentIdBySessionId[$session['id']] = $session['student_id'];
        }

        $studentNameById = [];
        foreach ($students as $student) {
            $studentNameById[$student['id']] = trim($student['firstname'] . ' ' . ($student['lastname'] ?? ''));
        }

        $typeNameById = [];
        foreach ($types as $type) {
            $typeNameById[$type['id']] = $type['type'];
        }

        $rows = [];
        foreach ($attendances as $attendance) {
            $studentId = $studentIdBySessionId[$attendance['student_session_id']] ?? null;
            $rows[] = [
                'student' => $studentId !== null ? ($studentNameById[$studentId] ?? 'Unknown') : 'Unknown',
                'date' => $attendance['date'],
                'type' => $typeNameById[$attendance['attendence_type_id']] ?? 'Unknown',
            ];
        }

        $this->load->view('pilot_attendance', ['rows' => $rows]);
    }
}
