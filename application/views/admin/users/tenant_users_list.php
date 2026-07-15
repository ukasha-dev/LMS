<!DOCTYPE html>
<html>
<head><title>Tenant Users List</title></head>
<body>
<h1>Users (<?php echo count($usersList); ?> real, tenant-scoped rows)</h1>
<ul>
<?php foreach ($usersList as $row): ?>
    <li>User #<?php echo (int) $row['id']; ?> — <?php echo htmlspecialchars((string) $row['username']); ?> (<?php echo htmlspecialchars((string) $row['role']); ?>)</li>
<?php endforeach; ?>
</ul>
</body>
</html>
