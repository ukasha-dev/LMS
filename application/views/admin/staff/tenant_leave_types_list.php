<!DOCTYPE html>
<html>
<head><title>Tenant Leave Types List</title></head>
<body>
<h1>Leave Types (<?php echo count($leaveTypesList); ?> real, tenant-scoped rows)</h1>
<ul>
<?php foreach ($leaveTypesList as $row): ?>
    <li>Leave Type #<?php echo (int) $row['id']; ?> — <?php echo htmlspecialchars((string) $row['type']); ?></li>
<?php endforeach; ?>
</ul>
</body>
</html>
