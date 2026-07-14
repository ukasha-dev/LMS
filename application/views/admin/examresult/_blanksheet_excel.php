<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Blank Sheet Print</title>
    <style>
        @page {
            size: A4;
            margin: 12mm;
        }
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #000;
        }
        .sheet-section {
            width: 100%;
            margin: 0;
            padding: 0;
        }
        .sheet-section + .sheet-section {
            page-break-before: always;
            break-before: page;
        }
        table {
            border-collapse: collapse;
            width: 100%;
        }
        td, th {
            border: 1px solid #000;
            padding: 6px;
            vertical-align: middle;
        }
        .meta-row {
            font-weight: 700;
        }
        .head-row td {
            font-weight: 700;
        }
        .marks-col {
            width: 90px;
        }
        .signature-row {
            width: 100%;
            margin-top: 35px;
            border-collapse: collapse;
        }
        .signature-row td {
            border: 0;
            font-weight: 550;
            padding: 0;
        }
        .signature-left {
            text-align: left;
        }
        .signature-center {
            text-align: center;
        }
        .signature-right {
            text-align: right;
        }
    </style>
</head>
<body<?php echo (!empty($is_print_mode)) ? ' onload="window.print();"' : ''; ?>>
<?php
$subject_count = count($subjectList);
$resolved_campus_name = '';
if (!empty($campus_name)) {
    $resolved_campus_name = $campus_name;
} elseif (isset($sch_setting->name) && !empty($sch_setting->name)) {
    $resolved_campus_name = $sch_setting->name;
} elseif (is_array($sch_setting) && isset($sch_setting[0]['name']) && !empty($sch_setting[0]['name'])) {
    $resolved_campus_name = $sch_setting[0]['name'];
}
for ($subject_index = 0; $subject_index < $subject_count; $subject_index++) {
    $subject_value = $subjectList[$subject_index];

    $subject_title = $subject_value->subject_name;
    if (!empty($subject_value->subject_code)) {
        $subject_title .= " (" . $subject_value->subject_code . ")";
    }

    $subject_header = $subject_value->subject_name . " " . $this->lang->line('marks');
    if (isset($subject_value->max_marks) && $subject_value->max_marks !== '') {
        $subject_header .= " (" . $subject_value->max_marks . ")";
    }
    ?>
    <div class="sheet-section">
        <table cellpadding="6" cellspacing="0">
            <tr class="meta-row">
                <td colspan="4">
                    <?php echo "Campus: " . $resolved_campus_name . " | Class: " . $class_name . " | Section: " . $section_name . " | Session: " . $session_name . " | Exam: " . $exam_name . " | Subject: " . $subject_title; ?>
                </td>
            </tr>
            <tr class="head-row">
                <td><?php echo $this->lang->line('admission_no'); ?></td>
                <td><?php echo $this->lang->line('student_name'); ?></td>
                <td><?php echo $this->lang->line('father_name'); ?></td>
                <td class="marks-col"><?php echo $subject_header; ?></td>
            </tr>
            <?php foreach ($studentList as $student_value) { ?>
            <tr>
                <td><?php echo $student_value->admission_no; ?></td>
                <td><?php echo $this->customlib->getFullName($student_value->firstname, $student_value->middlename, $student_value->lastname, $sch_setting->middlename, $sch_setting->lastname); ?></td>
                <td><?php echo $student_value->father_name; ?></td>
                <td></td>
            </tr>
            <?php } ?>
        </table>
        <table class="signature-row">
            <tr>
                <td class="signature-left" width="33.33%">Class Incharge</td>
                <td class="signature-center" width="33.33%">Coordinator</td>
                <td class="signature-right" width="33.33%">Controller Examination</td>
            </tr>
        </table>
    </div>
<?php } ?>
</body>
</html>
