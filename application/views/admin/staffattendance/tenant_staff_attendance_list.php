<!DOCTYPE html>
<html>
<head><title>Tenant Staff Attendance List</title></head>
<body>
<h1>Staff Attendance (<?php echo count($attendanceList); ?> real, tenant-scoped rows)</h1>
<ul>
<?php foreach ($attendanceList as $row): ?>
    <li>Staff #<?php echo (int) $row['staff_id']; ?> — <?php echo htmlspecialchars((string) $row['date']); ?> — type #<?php echo (int) $row['staff_attendance_type_id']; ?></li>
<?php endforeach; ?>
</ul>
</body>
</html>
