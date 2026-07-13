<?php

defined('BASEPATH') or exit('No direct script access allowed');

class PilotHr extends CI_Controller
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
        $leaveDetails = $this->tenant_model->tenantGetAll('staff_leave_details');
        $staff = $this->tenant_model->tenantGetAll('staff');
        $leaveTypes = $this->tenant_model->tenantGetAll('leave_types');
        $departments = $this->tenant_model->tenantGetAll('department');
        $designations = $this->tenant_model->tenantGetAll('staff_designation');

        $staffNameById = [];
        foreach ($staff as $member) {
            $staffNameById[$member['id']] = trim($member['name'] . ' ' . ($member['surname'] ?? ''));
        }

        $leaveTypeNameById = [];
        foreach ($leaveTypes as $leaveType) {
            $leaveTypeNameById[$leaveType['id']] = $leaveType['type'];
        }

        $rows = [];
        foreach ($leaveDetails as $detail) {
            $rows[] = [
                'staff' => $staffNameById[$detail['staff_id']] ?? 'Unknown',
                'leave_type' => $leaveTypeNameById[$detail['leave_type_id']] ?? 'Unknown',
                'alloted_leave' => $detail['alloted_leave'],
            ];
        }

        $this->load->view('pilot_hr', [
            'rows' => $rows,
            'department_count' => count($departments),
            'designation_count' => count($designations),
        ]);
    }
}
