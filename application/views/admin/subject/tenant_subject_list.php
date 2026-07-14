<!DOCTYPE html>
<html>
<head><title>Tenant Subject List</title></head>
<body>
<h1>Subjects (<?php echo count($subjectList); ?> real, tenant-scoped rows)</h1>
<ul>
<?php foreach ($subjectList as $row): ?>
    <li>Subject #<?php echo (int) $row['id']; ?> — <?php echo htmlspecialchars((string) $row['name']); ?> (<?php echo htmlspecialchars((string) $row['code']); ?>)</li>
<?php endforeach; ?>
</ul>
</body>
</html>
