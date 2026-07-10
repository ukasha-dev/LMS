<?php

defined('BASEPATH') or exit('No direct script access allowed');

class PilotExam extends CI_Controller
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
        $results = $this->tenant_model->tenantGetAll('exam_group_exam_results');
        $examStudents = $this->tenant_model->tenantGetAll('exam_group_class_batch_exam_students');
        $students = $this->tenant_model->tenantGetAll('students');
        $subjects = $this->tenant_model->tenantGetAll('subjects');
        $examSubjectLinks = $this->tenant_model->tenantGetAll('exam_group_class_batch_exam_subjects');
        $batchExams = $this->tenant_model->tenantGetAll('exam_group_class_batch_exams');
        $examGroups = $this->tenant_model->tenantGetAll('exam_groups');

        $studentIdByExamStudentId = [];
        foreach ($examStudents as $examStudent) {
            $studentIdByExamStudentId[$examStudent['id']] = $examStudent['student_id'];
        }

        $studentNameById = [];
        foreach ($students as $student) {
            $studentNameById[$student['id']] = trim($student['firstname'] . ' ' . ($student['lastname'] ?? ''));
        }

        $subjectIdByExamSubjectLinkId = [];
        foreach ($examSubjectLinks as $link) {
            $subjectIdByExamSubjectLinkId[$link['id']] = $link['subject_id'];
        }

        $subjectNameById = [];
        foreach ($subjects as $subject) {
            $subjectNameById[$subject['id']] = $subject['name'];
        }

        $examNameById = [];
        foreach ($batchExams as $batchExam) {
            $examNameById[$batchExam['id']] = $batchExam['exam'];
        }

        $rows = [];
        foreach ($results as $result) {
            $studentId = $studentIdByExamStudentId[$result['exam_group_class_batch_exam_student_id']] ?? null;
            $subjectLinkId = $result['exam_group_class_batch_exam_subject_id'];
            $subjectId = $subjectLinkId !== null ? ($subjectIdByExamSubjectLinkId[$subjectLinkId] ?? null) : null;

            $rows[] = [
                'student' => $studentId !== null ? ($studentNameById[$studentId] ?? 'Unknown') : 'Unknown',
                'subject' => $subjectId !== null ? ($subjectNameById[$subjectId] ?? 'Unknown') : 'Unknown',
                'marks' => $result['get_marks'],
                'attendence' => $result['attendence'],
            ];
        }

        $this->load->view('pilot_exam', ['rows' => $rows, 'exam_group_count' => count($examGroups)]);
    }
}
