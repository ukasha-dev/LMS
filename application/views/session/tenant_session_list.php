<!DOCTYPE html>
<html>
<head><title>Tenant Session List</title></head>
<body>
<h1>Sessions (<?php echo count($sessionList); ?> real, tenant-scoped rows)</h1>
<ul>
<?php foreach ($sessionList as $row): ?>
    <li>Session #<?php echo (int) $row['id']; ?> — <?php echo htmlspecialchars((string) $row['session']); ?></li>
<?php endforeach; ?>
</ul>
</body>
</html>
