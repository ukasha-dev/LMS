<!DOCTYPE html>
<html>
<head><title>Tenant Class List</title></head>
<body>
<h1>Classes (<?php echo count($classList); ?> real, tenant-scoped rows)</h1>
<ul>
<?php foreach ($classList as $row): ?>
    <li>Class #<?php echo (int) $row['id']; ?> — <?php echo htmlspecialchars((string) $row['class']); ?></li>
<?php endforeach; ?>
</ul>
</body>
</html>
