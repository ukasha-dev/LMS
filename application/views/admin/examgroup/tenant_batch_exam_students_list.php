<!DOCTYPE html>
<html>
<head><title>Tenant Exam Group Batch Exam Students List</title></head>
<body>
<h1>Batch Exam Students (<?php echo count($batchExamStudentsList); ?> real, tenant-scoped rows)</h1>
<ul>
<?php foreach ($batchExamStudentsList as $row): ?>
    <li>Batch Exam Student #<?php echo (int) $row['id']; ?> — student #<?php echo (int) $row['student_id']; ?>, roll no <?php echo htmlspecialchars((string) $row['roll_no']); ?></li>
<?php endforeach; ?>
</ul>
</body>
</html>
