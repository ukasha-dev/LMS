<!DOCTYPE html>
<html>
<head><title>Tenant Department List</title></head>
<body>
<h1>Departments (<?php echo count($departmentList); ?> real, tenant-scoped rows)</h1>
<ul>
<?php foreach ($departmentList as $row): ?>
    <li>Department #<?php echo (int) $row['id']; ?> — <?php echo htmlspecialchars((string) $row['department_name']); ?></li>
<?php endforeach; ?>
</ul>
</body>
</html>
