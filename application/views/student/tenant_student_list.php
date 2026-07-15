<!DOCTYPE html>
<html>
<head><title>Tenant Student List</title></head>
<body>
<h1>Students (<?php echo count($studentList); ?> real, tenant-scoped rows)</h1>
<ul>
<?php foreach ($studentList as $row): ?>
    <li>Student #<?php echo (int) $row['id']; ?> — <?php echo htmlspecialchars(trim($row['firstname'] . ' ' . $row['lastname'])); ?> (<?php echo htmlspecialchars((string) $row['admission_no']); ?>)</li>
<?php endforeach; ?>
</ul>
</body>
</html>
