<!DOCTYPE html>
<html>
<head><title>Tenant Staff List</title></head>
<body>
<h1>Staff (<?php echo count($staffList); ?> real, tenant-scoped rows)</h1>
<ul>
<?php foreach ($staffList as $staff): ?>
    <li><?php echo htmlspecialchars($staff['name'] . ' ' . ($staff['surname'] ?? '')); ?> — <?php echo htmlspecialchars($staff['email']); ?></li>
<?php endforeach; ?>
</ul>
</body>
</html>
