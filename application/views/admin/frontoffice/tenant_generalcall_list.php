<!DOCTYPE html>
<html>
<head><title>Tenant Generalcall List</title></head>
<body>
<h1>General Calls (<?php echo count($generalcallList); ?> real, tenant-scoped rows)</h1>
<ul>
<?php foreach ($generalcallList as $row): ?>
    <li>General Call #<?php echo (int) $row['id']; ?> — <?php echo htmlspecialchars((string) $row['name']); ?></li>
<?php endforeach; ?>
</ul>
</body>
</html>
