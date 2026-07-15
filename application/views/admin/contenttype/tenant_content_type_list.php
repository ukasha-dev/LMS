<!DOCTYPE html>
<html>
<head><title>Tenant Content Type List</title></head>
<body>
<h1>Content Types (<?php echo count($contentTypeList); ?> real, tenant-scoped rows)</h1>
<ul>
<?php foreach ($contentTypeList as $row): ?>
    <li>Content Type #<?php echo (int) $row['id']; ?> — <?php echo htmlspecialchars((string) $row['name']); ?></li>
<?php endforeach; ?>
</ul>
</body>
</html>
