<!DOCTYPE html>
<html>
<head><title>Tenant Feetype List</title></head>
<body>
<h1>Fee Types (<?php echo count($feetypeList); ?> real, tenant-scoped rows)</h1>
<ul>
<?php foreach ($feetypeList as $row): ?>
    <li>Fee Type #<?php echo (int) $row['id']; ?> — <?php echo htmlspecialchars((string) $row['type']); ?> (<?php echo htmlspecialchars((string) $row['code']); ?>)</li>
<?php endforeach; ?>
</ul>
</body>
</html>
