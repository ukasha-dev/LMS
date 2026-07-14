<!DOCTYPE html>
<html>
<head><title>Tenant Designation List</title></head>
<body>
<h1>Designations (<?php echo count($designationList); ?> real, tenant-scoped rows)</h1>
<ul>
<?php foreach ($designationList as $row): ?>
    <li>Designation #<?php echo (int) $row['id']; ?> — <?php echo htmlspecialchars((string) $row['designation']); ?></li>
<?php endforeach; ?>
</ul>
</body>
</html>
