<!DOCTYPE html>
<html>
<head><title>Tenant Roles List</title></head>
<body>
<h1>Roles (<?php echo count($rolesList); ?> real, tenant-scoped rows)</h1>
<ul>
<?php foreach ($rolesList as $row): ?>
    <li>Role #<?php echo (int) $row['id']; ?> — <?php echo htmlspecialchars((string) $row['name']); ?></li>
<?php endforeach; ?>
</ul>
</body>
</html>
