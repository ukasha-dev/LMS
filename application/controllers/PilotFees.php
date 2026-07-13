<?php

defined('BASEPATH') or exit('No direct script access allowed');

class PilotFees extends CI_Controller
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
        $deposites = $this->tenant_model->tenantGetAll('student_fees_deposite');
        $masters = $this->tenant_model->tenantGetAll('student_fees_master');
        $studentSessions = $this->tenant_model->tenantGetAll('student_session');
        $students = $this->tenant_model->tenantGetAll('students');
        $fgfRows = $this->tenant_model->tenantGetAll('fee_groups_feetype');
        $feetypes = $this->tenant_model->tenantGetAll('feetype');
        $feeGroups = $this->tenant_model->tenantGetAll('fee_groups');
        $appliedDiscounts = $this->tenant_model->tenantGetAll('student_applied_discounts');

        $studentSessionIdByMasterId = [];
        foreach ($masters as $master) {
            $studentSessionIdByMasterId[$master['id']] = $master['student_session_id'];
        }

        $studentIdBySessionId = [];
        foreach ($studentSessions as $session) {
            $studentIdBySessionId[$session['id']] = $session['student_id'];
        }

        $studentNameById = [];
        foreach ($students as $student) {
            $studentNameById[$student['id']] = trim($student['firstname'] . ' ' . ($student['lastname'] ?? ''));
        }

        $feetypeIdByFgfId = [];
        $feeGroupIdByFgfId = [];
        foreach ($fgfRows as $fgf) {
            $feetypeIdByFgfId[$fgf['id']] = $fgf['feetype_id'];
            $feeGroupIdByFgfId[$fgf['id']] = $fgf['fee_groups_id'];
        }

        $feetypeNameById = [];
        foreach ($feetypes as $feetype) {
            $feetypeNameById[$feetype['id']] = $feetype['type'];
        }

        $feeGroupNameById = [];
        foreach ($feeGroups as $feeGroup) {
            $feeGroupNameById[$feeGroup['id']] = $feeGroup['name'];
        }

        $discountCountByDepositeId = [];
        foreach ($appliedDiscounts as $discount) {
            $depositeId = $discount['student_fees_deposite_id'];
            $discountCountByDepositeId[$depositeId] = ($discountCountByDepositeId[$depositeId] ?? 0) + 1;
        }

        $rows = [];
        foreach ($deposites as $deposite) {
            $masterId = $deposite['student_fees_master_id'];
            $sessionId = $studentSessionIdByMasterId[$masterId] ?? null;
            $studentId = $sessionId !== null ? ($studentIdBySessionId[$sessionId] ?? null) : null;

            $fgfId = $deposite['fee_groups_feetype_id'];
            $feetypeId = $feetypeIdByFgfId[$fgfId] ?? null;
            $feeGroupId = $feeGroupIdByFgfId[$fgfId] ?? null;

            $rows[] = [
                'student' => $studentId !== null ? ($studentNameById[$studentId] ?? 'Unknown') : 'Unknown',
                'fee_type' => $feetypeId !== null ? ($feetypeNameById[$feetypeId] ?? 'Unknown') : 'Unknown',
                'fee_group' => $feeGroupId !== null ? ($feeGroupNameById[$feeGroupId] ?? 'Unknown') : 'Unknown',
                'discount_count' => $discountCountByDepositeId[$deposite['id']] ?? 0,
            ];
        }

        $this->load->view('pilot_fees', ['rows' => $rows]);
    }
}
