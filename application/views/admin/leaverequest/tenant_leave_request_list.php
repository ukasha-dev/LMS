<!DOCTYPE html>
<html>
<head><title>Tenant Leave Request List</title></head>
<body>
<h1>Staff Leave Details (<?php echo count($leaveRequestList); ?> real, tenant-scoped rows)</h1>
<ul>
<?php foreach ($leaveRequestList as $row): ?>
    <li>Leave #<?php echo (int) $row['id']; ?> — staff <?php echo (int) $row['staff_id']; ?>, leave type <?php echo (int) $row['leave_type_id']; ?>, alloted <?php echo htmlspecialchars((string) $row['alloted_leave']); ?></li>
<?php endforeach; ?>
</ul>
</body>
</html>
