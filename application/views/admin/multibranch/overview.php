<?php
$currency_symbol = $this->customlib->getSchoolCurrencyFormat();
?>
<style type="text/css">
    @media print {
        .displaynone {
            display: block !important;
        }
    }
</style>
<link rel="stylesheet" href="<?php echo base_url(); ?>backend/dist/css/multi_branch.css">

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Main content -->
    <section class="content">
        <div class="row">
            <div class="col-md-12">
                <!-- general form elements -->
                <div class="box box-primary">
                    <div class="box-header ptbnull">
                        <h3 class="box-title titlefix"><?php echo $this->lang->line('overview'); ?> </h3>

                        <div class="box-tools pull-right">
                            <button class="btn btn-primary btn-xs print_page" title="<?php echo $this->lang->line('print'); ?>">
                                <i class="fa fa-print"></i>
                            </button>
                        </div><!-- /.box-tools -->
                    </div><!-- /.box-header -->

                    <!-- ===== FEES DETAILS ===== -->
                    <div class="box-table-card mb25">
                        <h4 class="pagetitleh-box"><?php echo $this->lang->line('fees_details'); ?></h4>
                        <div class="table-responsive mt5">
                            <table class="table table-hover mb5 table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th width="18%"><?php echo $this->lang->line('branch'); ?> </th>
                                        <th width="12%"><?php echo $this->lang->line('current_session'); ?></th>
                                        <th class="text-center" width="10%"><?php echo $this->lang->line('total_students'); ?></th>
                                        <th class="text-center" width="8%"><?php echo $this->lang->line('male'); ?></th>
                                        <th class="text-center" width="8%"><?php echo $this->lang->line('female'); ?></th>
                                        <!-- <th class="text-center" width="8%"><?php echo $this->lang->line('gender_ratio'); ?></th> -->
                                        <th class="text text-right" width="12%"><?php echo $this->lang->line('total_fees'); ?></th>
                                        <th class="text text-right" width="12%"><?php echo $this->lang->line('total_paid_fees'); ?></th>
                                        <th class="text text-right" width="12%"><?php echo $this->lang->line('total_balance_fees'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // init totals
                                    $total_students_all = 0;
                                    $total_male_all = 0;
                                    $total_female_all = 0;
                                    $total_fees_all = 0;
                                    $total_paid_all = 0;
                                    $total_balance_all = 0;

                                    if (!empty($school_students)) {
                                        foreach ($school_students as $school_key => $school_value) {
                                            // make sure fields exist and are numeric
                                            $students = isset($school_value['total_student']) ? (int)$school_value['total_student'] : 0;
                                            $male_count = isset($school_value['male_count']) ? (int)$school_value['male_count'] : 0;
                                            $female_count = isset($school_value['female_count']) ? (int)$school_value['female_count'] : 0;
                                            $fees = isset($school_value['total_fees']) ? (float)$school_value['total_fees'] : 0;
                                            $paid = isset($school_value['total_paid']) ? (float)$school_value['total_paid'] : 0;
                                            $balance = isset($school_value['total_balance']) ? (float)$school_value['total_balance'] : 0;

                                            // Calculate percentages
                                            $male_percentage = ($students > 0) ? round(($male_count / $students) * 100, 1) : 0;
                                            $female_percentage = ($students > 0) ? round(($female_count / $students) * 100, 1) : 0;
                                            $gender_ratio = ($female_count > 0) ? round($male_count / $female_count, 2) : 'âˆž';

                                            $total_students_all += $students;
                                            $total_male_all += $male_count;
                                            $total_female_all += $female_count;
                                            $total_fees_all += $fees;
                                            $total_paid_all += $paid;
                                            $total_balance_all += $balance;
                                    ?>
                                            <tr>
                                                <td width="18%"><?php echo $school_value['name']; ?></td>
                                                <td width="12%"><?php echo $school_value['session']; ?></td>
                                                <td class="text-center" width="10%"><?php echo $students; ?></td>
                                                <td class="text-center" width="8%">
                                                    <?php echo $male_count; ?>
                                                    <?php if ($students > 0): ?>
                                                        <br><small class="text-muted">(<?php echo $male_percentage; ?>%)</small>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center" width="8%">
                                                    <?php echo $female_count; ?>
                                                    <?php if ($students > 0): ?>
                                                        <br><small class="text-muted">(<?php echo $female_percentage; ?>%)</small>
                                                    <?php endif; ?>
                                                </td>
                                                <!-- <td class="text-center" width="8%">
                                <small>M:F <?php echo $gender_ratio; ?>:1</small>
                            </td> -->
                                                <td class="text text-right" width="12%"><?php echo $currency_symbol . amountFormat($fees); ?></td>
                                                <td class="text text-right" width="12%"><?php echo $currency_symbol . amountFormat($paid); ?></td>
                                                <td class="text text-right" width="12%"><?php echo $currency_symbol . amountFormat($balance); ?></td>
                                            </tr>
                                    <?php
                                        }
                                    } else {
                                        echo '<tr><td colspan="9" class="text-center">No records found</td></tr>';
                                    }

                                    // Calculate overall percentages
                                    $overall_male_percentage = ($total_students_all > 0) ? round(($total_male_all / $total_students_all) * 100, 1) : 0;
                                    $overall_female_percentage = ($total_students_all > 0) ? round(($total_female_all / $total_students_all) * 100, 1) : 0;
                                    $overall_gender_ratio = ($total_female_all > 0) ? round($total_male_all / $total_female_all, 2) : 'âˆž';
                                    ?>

                                    <!-- GRAND TOTAL ROW -->
                                    <tr style="font-weight: bold; background:#f5f5f5;">
                                        <td colspan="2"><?php echo $this->lang->line('overall_total'); ?></td>
                                        <td class="text-center"><?php echo $total_students_all; ?></td>
                                        <td class="text-center">
                                            <?php echo $total_male_all; ?>
                                            <?php if ($total_students_all > 0): ?>
                                                <br><small class="text-muted">(<?php echo $overall_male_percentage; ?>%)</small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php echo $total_female_all; ?>
                                            <?php if ($total_students_all > 0): ?>
                                                <br><small class="text-muted">(<?php echo $overall_female_percentage; ?>%)</small>
                                            <?php endif; ?>
                                        </td>
                                        <!-- <td class="text-center">
                        <small>M:F <?php echo $overall_gender_ratio; ?>:1</small>
                    </td> -->
                                        <td class="text text-right"><?php echo $currency_symbol . amountFormat($total_fees_all); ?></td>
                                        <td class="text text-right"><?php echo $currency_symbol . amountFormat($total_paid_all); ?></td>
                                        <td class="text text-right"><?php echo $currency_symbol . amountFormat($total_balance_all); ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    

                    <!-- ===== TRANSPORT FEES DETAILS ===== -->
                    <div class="box-table-card mb25">
                        <h4 class="pagetitleh-box"><?php echo $this->lang->line('transport_fees_details'); ?></h4>

                        <div class="table-responsive mt5">
                            <table class="table table-hover mb5 table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th width="26%"><?php echo $this->lang->line('branch'); ?></th>
                                        <th><?php echo $this->lang->line('current_session'); ?></th>
                                        <th class="text text-right"><?php echo $this->lang->line('total_fees'); ?></th>
                                        <th class="text text-right"><?php echo $this->lang->line('total_paid_fees'); ?></th>
                                        <th class="text text-right"><?php echo $this->lang->line('total_balance_fees'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>

                                    <?php
                                    $total_transport_fees_all = 0;
                                    $total_transport_paid_all = 0;
                                    $total_transport_balance_all = 0;

                                    if (!empty($school_transport_fees)) {
                                        foreach ($school_transport_fees as $school_key => $school_value) {
                                            $t_fees = isset($school_value['total_fees']) ? (float)$school_value['total_fees'] : 0;
                                            $t_paid = isset($school_value['total_paid']) ? (float)$school_value['total_paid'] : 0;
                                            $t_balance = isset($school_value['total_balance']) ? (float)$school_value['total_balance'] : 0;

                                            $total_transport_fees_all += $t_fees;
                                            $total_transport_paid_all += $t_paid;
                                            $total_transport_balance_all += $t_balance;
                                    ?>
                                            <tr>
                                                <td width="26%"><?php echo $school_value['name']; ?></td>
                                                <td><?php echo $school_value['session']; ?></td>
                                                <td class="text text-right"><?php echo $currency_symbol . amountFormat($t_fees); ?></td>
                                                <td class="text text-right"><?php echo $currency_symbol . amountFormat($t_paid); ?></td>
                                                <td class="text text-right"><?php echo $currency_symbol . amountFormat($t_balance); ?></td>
                                            </tr>
                                    <?php

                                        }
                                    } else {
                                        echo '<tr><td colspan="5" class="text-center">No transport fee records found</td></tr>';
                                    }

                                    ?>

                                    <tr style="font-weight:bold; background:#f5f5f5;">
                                        <td colspan="2"><?php echo $this->lang->line('overall_total'); ?><!-- Overall Total -->Overall Total</td>
                                        <td class="text text-right"><?php echo $currency_symbol . amountFormat($total_transport_fees_all); ?></td>
                                        <td class="text text-right"><?php echo $currency_symbol . amountFormat($total_transport_paid_all); ?></td>
                                        <td class="text text-right"><?php echo $currency_symbol . amountFormat($total_transport_balance_all); ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- ===== STUDENT ADMISSION ===== -->
                    <div class="box-table-card mb25">
                        <h4 class="pagetitleh-box"><?php echo $this->lang->line('student_admission'); ?></h4>
                        <div class="table-responsive mt5">
                            <table class="table table-hover mb5 table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th><?php echo $this->lang->line('branch'); ?></th>
                                        <th><?php echo $this->lang->line('current_session'); ?></th>
                                        <th><?php echo $this->lang->line('offline_admission'); ?></th>
                                        <th><?php echo $this->lang->line('online_admission'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $total_offline = 0;
                                    $total_online = 0;

                                    if (!empty($student_admission_list)) {
                                        foreach ($student_admission_list as $student_key => $student_value) {
                                            $offline = isset($student_value['offline_admission']) ? (int)$student_value['offline_admission'] : 0;
                                            $online = isset($student_value['online_admission']) ? (int)$student_value['online_admission'] : 0;

                                            $total_offline += $offline;
                                            $total_online += $online;
                                    ?>
                                            <tr>
                                                <td width="26%"><?php echo $student_value['name']; ?></td>
                                                <td><?php echo $student_value['session']; ?></td>
                                                <td><?php echo $offline; ?></td>
                                                <td><?php echo $online; ?></td>

                                            </tr>
                                    <?php

                                        }
                                    } else {
                                        echo '<tr><td colspan="4" class="text-center">No admission records found</td></tr>';
                                    }

                                    ?>

                                    <tr style="font-weight:bold; background:#f5f5f5;">
                                        <td colspan="2">Overall Total</td>
                                        <td><?php echo $total_offline; ?></td>
                                        <td><?php echo $total_online; ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- ===== LIBRARY DETAILS ===== -->
                    <div class="box-table-card mb25">
                        <h4 class="pagetitleh-box"><?php echo $this->lang->line('library_details'); ?></h4>
                        <div class="table-responsive mt5">
                            <table class="table table-hover mb5 table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th><?php echo $this->lang->line('branch'); ?></th>
                                        <th><?php echo $this->lang->line('total_books'); ?></th>
                                        <th><?php echo $this->lang->line('members'); ?></th>
                                        <th><?php echo $this->lang->line('book_issued'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $total_books_all = 0;
                                    $total_members_all = 0;
                                    $total_issued_all = 0;

                                    if (!empty($student_books_list)) {
                                        foreach ($student_books_list as $books_key => $books_value) {
                                            $b_books = isset($books_value['total_books']) ? (int)$books_value['total_books'] : 0;
                                            $b_members = isset($books_value['libarary_members']) ? (int)$books_value['libarary_members'] : 0;
                                            $b_issued = isset($books_value['book_issued']) ? (int)$books_value['book_issued'] : 0;

                                            $total_books_all += $b_books;
                                            $total_members_all += $b_members;
                                            $total_issued_all += $b_issued;
                                    ?>
                                            <tr>
                                                <td width="26%"><?php echo $books_value['name']; ?></td>
                                                <td><?php echo $b_books; ?></td>
                                                <td><?php echo $b_members; ?></td>
                                                <td><?php echo $b_issued; ?></td>

                                            </tr>
                                    <?php

                                        }
                                    } else {
                                        echo '<tr><td colspan="4" class="text-center">No library records found</td></tr>';
                                    }

                                    ?>

                                    <tr style="font-weight:bold; background:#f5f5f5;">
                                        <td>Overall Total</td>
                                        <td><?php echo $total_books_all; ?></td>
                                        <td><?php echo $total_members_all; ?></td>
                                        <td><?php echo $total_issued_all; ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- ===== ALUMNI STUDENTS ===== -->
                    <div class="box-table-card mb25">
                        <h4 class="pagetitleh-box"><?php echo $this->lang->line('alumni_students'); ?></h4>
                        <div class="table-responsive mt5">
                            <table class="table table-hover mb5 table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th><?php echo $this->lang->line('branch'); ?></th>
                                        <th><?php echo $this->lang->line('alumni_students'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $total_alumni = 0;
                                    if (!empty($alumni_student_list)) {
                                        foreach ($alumni_student_list as $alumini_student_key => $alumini_student_value) {
                                            $a_total = isset($alumini_student_value['total_alumni_student']) ? (int)$alumini_student_value['total_alumni_student'] : 0;
                                            $total_alumni += $a_total;
                                    ?>
                                            <tr>
                                                <td width="26%"><?php echo $alumini_student_value['name']; ?></td>
                                                <td><?php echo $a_total; ?></td>

                                            </tr>
                                    <?php

                                        }
                                    } else {
                                        echo '<tr><td colspan="2" class="text-center">No alumni records found</td></tr>';
                                    }

                                    ?>

                                    <tr style="font-weight:bold; background:#f5f5f5;">
                                        <td>Overall Total</td>
                                        <td><?php echo $total_alumni; ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- ===== STAFF PAYROLL ===== -->
                    <div class="box-table-card mb25">
                        <h4 class="pagetitleh-box"><?php echo $this->lang->line('staff_payroll_of'); ?> <?php echo $this->lang->line(strtolower($month)); ?> </h4>
                        <div class="table-responsive mt5">
                            <table class="table table-hover mb5 table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th width="26%"><?php echo $this->lang->line('branch'); ?></th>
                                        <th width="150"><?php echo $this->lang->line('total_staff'); ?></th>
                                        <th width="140"><?php echo $this->lang->line('payroll_generated'); ?> </th>
                                        <th><?php echo $this->lang->line('payroll_not_generated'); ?></th>
                                        <th><?php echo $this->lang->line('payroll_paid'); ?></th>
                                        <th class="text-right"><?php echo $this->lang->line('net_amount'); ?> </th>
                                        <th class="text-right"><?php echo $this->lang->line('paid_amount'); ?></th>

                                    </tr>
                                </thead>
                                <tbody>

                                    <?php
                                    $total_staff_all = 0;
                                    $total_generated_all = 0;
                                    $total_paid_staff_all = 0;
                                    $total_net_amount = 0;
                                    $total_paid_amount = 0;

                                    if (!empty($staff_payslip)) {
                                        foreach ($staff_payslip as $staff_payslip_key => $staff_payslip_value) {
                                            $staff_count = isset($staff_payslip_value['staff']) ? (int)$staff_payslip_value['staff'] : 0;
                                            $generated = isset($staff_payslip_value['staff_status_generated']) ? (int)$staff_payslip_value['staff_status_generated'] : 0;
                                            $paid_staff = isset($staff_payslip_value['staff_status_paid']) ? (int)$staff_payslip_value['staff_status_paid'] : 0;
                                            $net = isset($staff_payslip_value['payroll_amount']) ? (float)$staff_payslip_value['payroll_amount'] : 0;
                                            $paid_amt = isset($staff_payslip_value['payroll_amount_paid']) ? (float)$staff_payslip_value['payroll_amount_paid'] : 0;

                                            $not_generated = $staff_count - $generated - $paid_staff;

                                            $total_staff_all += $staff_count;
                                            $total_generated_all += $generated;
                                            $total_paid_staff_all += $paid_staff;
                                            $total_net_amount += $net;
                                            $total_paid_amount += $paid_amt;
                                    ?>
                                            <tr>
                                                <td width="26%"><?php echo $staff_payslip_value['name']; ?></td>
                                                <td><?php echo $staff_count; ?></td>
                                                <td><?php echo $generated; ?></td>
                                                <td><?php echo $not_generated; ?></td>
                                                <td><?php echo $paid_staff; ?></td>
                                                <td class="text text-right"><?php echo $currency_symbol . amountFormat($net); ?></td>
                                                <td class="text text-right"><?php echo $currency_symbol . amountFormat($paid_amt); ?></td>

                                            </tr>
                                    <?php

                                        }
                                    } else {
                                        echo '<tr><td colspan="7" class="text-center">No payroll records found</td></tr>';
                                    }

                                    ?>

                                    <tr style="font-weight:bold; background:#f5f5f5;">
                                        <td>Total</td>
                                        <td><?php echo $total_staff_all; ?></td>
                                        <td><?php echo $total_generated_all; ?></td>
                                        <td><?php echo $total_staff_all - $total_generated_all - $total_paid_staff_all; ?></td>
                                        <td><?php echo $total_paid_staff_all; ?></td>
                                        <td class="text text-right"><?php echo $currency_symbol . amountFormat($total_net_amount); ?></td>
                                        <td class="text text-right"><?php echo $currency_symbol . amountFormat($total_paid_amount); ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- ===== STAFF ATTENDANCE ===== -->
                    <div class="box-table-card mb25">
                        <h4 class="pagetitleh-box"><?php echo $this->lang->line('staff_attendance_details'); ?> <?php echo $this->customlib->dateformat(date("Y/m/d")) ?> </h4>

                        <div class="table-responsive mt5">
                            <table class="table table-hover mb5 table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th><?php echo $this->lang->line('branch'); ?></th>
                                        <th><?php echo $this->lang->line('total_staff'); ?></th>
                                        <th><?php echo $this->lang->line('present'); ?></th>
                                        <th><?php echo $this->lang->line('absent'); ?></th>

                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $attendance_total_staff = 0;
                                    $attendance_present = 0;
                                    $attendance_absent = 0;

                                    if (!empty($staff_list)) {
                                        foreach ($staff_list as $staff_key => $staff_value) {
                                            $t_staff = isset($staff_value['total_staff']) ? (int)$staff_value['total_staff'] : 0;
                                            $present = (isset($staff_value['staff_present']) && $staff_value['staff_present']) ? (int)$staff_value['staff_present'] : 0;
                                            $absent = (isset($staff_value['staff_absent']) && $staff_value['staff_absent']) ? (int)$staff_value['staff_absent'] : 0;

                                            $attendance_total_staff += $t_staff;
                                            $attendance_present += $present;
                                            $attendance_absent += $absent;
                                    ?>
                                            <tr>
                                                <td width="26%"><?php echo $staff_value['name']; ?></td>
                                                <td><?php echo $t_staff; ?></td>
                                                <td><?php echo ($present || $absent) ? $present : "-"; ?></td>
                                                <td><?php echo ($present || $absent) ? $absent : "-"; ?> </td>

                                            </tr>
                                    <?php

                                        }
                                    } else {
                                        echo '<tr><td colspan="4" class="text-center">No attendance records found</td></tr>';
                                    }

                                    ?>

                                    <tr style="font-weight:bold; background:#f5f5f5;">
                                        <td>Total</td>
                                        <td><?php echo $attendance_total_staff; ?></td>
                                        <td><?php echo $attendance_present; ?></td>
                                        <td><?php echo $attendance_absent; ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- ===== USER LOG DETAILS ===== -->
                    <div class="box-table-card mb25">
                        <h4 class="pagetitleh-box"><?php echo $this->lang->line('user_log_details'); ?></h4>
                        <div class="table-responsive mt5">
                            <table class="table table-hover mb5 table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th><?php echo $this->lang->line('branch'); ?></th>
                                        <th><?php echo $this->lang->line('total_user_log'); ?></th>

                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $total_user_log_all = 0;
                                    if (!empty($user_log_list)) {
                                        foreach ($user_log_list as $user_log_key => $user_log_value) {
                                            $ulog = isset($user_log_value['total_userlog']) ? (int)$user_log_value['total_userlog'] : 0;
                                            $total_user_log_all += $ulog;
                                    ?>
                                            <tr>
                                                <td width="26%"><?php echo $user_log_value['name']; ?></td>
                                                <td><?php echo $ulog; ?></td>

                                            </tr>
                                    <?php

                                        }
                                    } else {
                                        echo '<tr><td colspan="2" class="text-center">No user log records found</td></tr>';
                                    }

                                    ?>

                                    <tr style="font-weight:bold; background:#f5f5f5;">
                                        <td>Total</td>
                                        <td><?php echo $total_user_log_all; ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div><!-- /.box-body -->
            </div><!-- /.box-body -->
        </div>
</div><!--/.col (left) --> <!-- right column -->

</div>

</section><!-- /.content -->
</div><!-- /.content-wrapper -->


<script type="text/javascript">
    $(document).on('click', '.print_page', function() {

        var page_content = document.getElementById("print_div").innerHTML;
        Popup(page_content, false);

    });

    function Popup(data, winload = false) {
        var frame1 = $('<iframe />').attr("id", "printDiv");
        frame1[0].name = "frame1";
        frame1.css({
            "position": "absolute",
            "top": "-1000000px"
        });
        $("body").append(frame1);
        var frameDoc = frame1[0].contentWindow ? frame1[0].contentWindow : frame1[0].contentDocument.document ? frame1[0].contentDocument.document : frame1[0].contentDocument;
        frameDoc.document.open();
        //Create a new HTML document.
        frameDoc.document.write('<html>');
        frameDoc.document.write('<head>');
        frameDoc.document.write('<title></title>');
        frameDoc.document.write('<link rel="stylesheet" href="' + base_url + 'backend/bootstrap/css/bootstrap.min.css">');
        frameDoc.document.write('<link rel="stylesheet" href="' + base_url + 'backend/dist/css/font-awesome.min.css">');
        frameDoc.document.write('<link rel="stylesheet" href="' + base_url + 'backend/dist/css/ionicons.min.css">');
        frameDoc.document.write('<link rel="stylesheet" href="' + base_url + 'backend/dist/css/AdminLTE.min.css">');
        frameDoc.document.write('<link rel="stylesheet" href="' + base_url + 'backend/dist/css/skins/_all-skins.min.css">');
        frameDoc.document.write('<link rel="stylesheet" href="' + base_url + 'backend/plugins/iCheck/flat/blue.css">');
        frameDoc.document.write('<link rel="stylesheet" href="' + base_url + 'backend/plugins/morris/morris.css">');
        frameDoc.document.write('<link rel="stylesheet" href="' + base_url + 'backend/plugins/jvectormap/jquery-jvectormap-1.2.2.css">');
        frameDoc.document.write('<link rel="stylesheet" href="' + base_url + 'backend/plugins/datepicker/datepicker3.css">');
        frameDoc.document.write('<link rel="stylesheet" href="' + base_url + 'backend/plugins/daterangepicker/daterangepicker-bs3.css">');
        frameDoc.document.write('</head>');
        frameDoc.document.write('<body>');
        frameDoc.document.write(data);
        frameDoc.document.write('</body>');
        frameDoc.document.write('</html>');
        frameDoc.document.close();
        setTimeout(function() {
            document.getElementById('printDiv').contentWindow.focus();
            document.getElementById('printDiv').contentWindow.print();
            $("#printDiv", top.document).remove();
            if (winload) {
                window.location.reload(true);
            }
        }, 500);

        return true;
    }
</script>