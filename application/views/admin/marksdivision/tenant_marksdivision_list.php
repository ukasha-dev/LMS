<!DOCTYPE html>
<html>
<head><title>Tenant Marksdivision List</title></head>
<body>
<h1>Mark Divisions (<?php echo count($marksdivisionList); ?> real, tenant-scoped rows)</h1>
<ul>
<?php foreach ($marksdivisionList as $row): ?>
    <li>Mark Division #<?php echo (int) $row['id']; ?> — <?php echo htmlspecialchars((string) $row['name']); ?></li>
<?php endforeach; ?>
</ul>
</body>
</html>
