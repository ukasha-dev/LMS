<!DOCTYPE html>
<html>
<head><title>Tenant Feegroup List</title></head>
<body>
<h1>Fee Groups (<?php echo count($feegroupList); ?> real, tenant-scoped rows)</h1>
<ul>
<?php foreach ($feegroupList as $row): ?>
    <li>Fee Group #<?php echo (int) $row['id']; ?> — <?php echo htmlspecialchars((string) $row['name']); ?></li>
<?php endforeach; ?>
</ul>
</body>
</html>
