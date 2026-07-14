<!DOCTYPE html>
<html>
<head><title>Tenant Attendance List</title></head>
<body>
<h1>Student Attendance (<?php echo count($attendanceList); ?> real, tenant-scoped rows)</h1>
<ul>
<?php foreach ($attendanceList as $row): ?>
    <li>Attendance #<?php echo (int) $row['id']; ?> — student session <?php echo (int) $row['student_session_id']; ?>, date <?php echo htmlspecialchars((string) $row['date']); ?>, type <?php echo (int) $row['attendence_type_id']; ?></li>
<?php endforeach; ?>
</ul>
</body>
</html>
