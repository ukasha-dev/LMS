<!DOCTYPE html>
<html>
<head><title>Tenant Exam Group Batch Exam Subjects List</title></head>
<body>
<h1>Batch Exam Subjects (<?php echo count($batchExamSubjectsList); ?> real, tenant-scoped rows)</h1>
<ul>
<?php foreach ($batchExamSubjectsList as $row): ?>
    <li>Batch Exam Subject #<?php echo (int) $row['id']; ?> — subject #<?php echo (int) $row['subject_id']; ?>, room <?php echo htmlspecialchars((string) $row['room_no']); ?></li>
<?php endforeach; ?>
</ul>
</body>
</html>
