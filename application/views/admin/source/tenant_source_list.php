<!DOCTYPE html>
<html>
<head><title>Tenant Source List</title></head>
<body>
<h1>Sources (<?php echo count($sourceList); ?> real, tenant-scoped rows)</h1>
<ul>
<?php foreach ($sourceList as $row): ?>
    <li>Source #<?php echo (int) $row['id']; ?> — <?php echo htmlspecialchars((string) $row['source']); ?></li>
<?php endforeach; ?>
</ul>
</body>
</html>
