<style>
    .roll-slip-container {
        padding: 20px;
        font-family: Arial, sans-serif;
    }
    
    .header {
        text-align: center;
        margin-bottom: 20px;
        border-bottom: 2px solid #000;
        padding-bottom: 10px;
    }
    
    .school-name {
        font-size: 24px;
        font-weight: bold;
        margin-bottom: 5px;
    }
    
    .exam-title {
        font-size: 18px;
        font-weight: bold;
        margin-bottom: 10px;
    }
    
    .student-info {
        margin-bottom: 20px;
    }
    
    .info-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }
    
    .info-table td {
        padding: 5px;
        border: 1px solid #ddd;
    }
    
    .info-table .label {
        font-weight: bold;
        width: 30%;
        background-color: #f5f5f5;
    }
    
    .schedule-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }
    
    .schedule-table th, .schedule-table td {
        border: 1px solid #000;
        padding: 8px;
        text-align: center;
    }
    
    .schedule-table th {
        background-color: #f2f2f2;
        font-weight: bold;
    }
    
    .footer {
        margin-top: 30px;
        text-align: center;
        font-size: 12px;
    }
    
    .signature {
        margin-top: 40px;
        border-top: 1px solid #000;
        padding-top: 10px;
        width: 300px;
    }
</style>

<div class="roll-slip-container">
    <?php
    $student_gender = strtolower(trim((string) ($student->gender ?? '')));
    $guardian_prefix = ($student_gender === 'female' || $student_gender === 'f') ? 'D/O' : 'S/O';
    ?>
    <div class="header">
        <div class="school-name">[SCHOOL NAME]</div>
        <div class="exam-title">Roll Number Slip - <?php echo $exam_details->exam; ?></div>
        <div>Academic Year: <?php echo date('Y'); ?></div>
    </div>
    
    <div class="student-info">
        <table class="info-table">
            <tr>
                <td class="label">Roll Number:</td>
                <td><?php echo $student->admission_no; ?></td>
                <td class="label">Student Name:</td>
                <td><?php echo $student->firstname . ' ' . $student->lastname; ?></td>
            </tr>
            <tr>
                <td class="label">Gender:</td>
                <td><?php echo ucfirst($student->gender); ?></td>
                <td class="label">Class:</td>
                <td><?php echo $class_details->class . ' - ' . ($section_details ? $section_details->section : 'All Sections'); ?></td>
            </tr>
            <tr>
                <td class="label"><?php echo $guardian_prefix; ?>:</td>
                <td><?php echo $student->father_name; ?></td>
                <td class="label"></td>
                <td></td>
            </tr>
        </table>
    </div>
    
    <div class="exam-schedule">
        <h4>Exam Schedule</h4>
        <table class="schedule-table">
            <thead>
                <tr>
                    <th>Subject</th>
                    <th>Date From</th>
                    <th>Start Time</th>
                    <th>Duration</th>
                    <th>Room No.</th>
                    <th>Max Marks</th>
                    <th>Min Marks</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($exam_schedule)): ?>
                    <?php foreach ($exam_schedule as $subject): ?>
                        <tr>
                            <td><?php echo $subject->subject_name . ($subject->subject_code ? ' (' . $subject->subject_code . ')' : ''); ?></td>
                            <td><?php echo date('d-m-Y', strtotime($subject->date_from)); ?></td>
                            <td><?php echo $subject->time_from; ?></td>
                            <td><?php echo $subject->duration; ?></td>
                            <td><?php echo $subject->room_no; ?></td>
                            <td><?php echo $subject->max_marks; ?></td>
                            <td><?php echo $subject->min_marks; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align: center;">No schedule available</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <div class="footer">
        <p><strong>Instructions:</strong></p>
        <p>1. Bring this slip to all exam sessions</p>
        <p>2. Arrive 30 minutes before exam time</p>
        <p>3. Bring necessary stationery</p>
        
        <div class="signature" style="margin: 40px auto 0;">
            <p>Controller of Examinations</p>
        </div>
    </div>
</div>
