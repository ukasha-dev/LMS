<!DOCTYPE html>
<html>
<head><title>Tenant Subject Attendance List</title></head>
<body>
<h1>Subject Attendance (<?php echo count($attendanceList); ?> real, tenant-scoped rows)</h1>
<ul>
<?php foreach ($attendanceList as $row): ?>
    <li>session #<?php echo (int) $row['student_session_id']; ?> — <?php echo htmlspecialchars((string) $row['date']); ?> — type #<?php echo (int) $row['attendence_type_id']; ?></li>
<?php endforeach; ?>
</ul>
</body>
</html>
