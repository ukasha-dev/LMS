<!DOCTYPE html>
<html>
<head><title>Tenant Schoolhouse List</title></head>
<body>
<h1>School Houses (<?php echo count($schoolhouseList); ?> real, tenant-scoped rows)</h1>
<ul>
<?php foreach ($schoolhouseList as $row): ?>
    <li>School House #<?php echo (int) $row['id']; ?> — <?php echo htmlspecialchars((string) $row['house_name']); ?></li>
<?php endforeach; ?>
</ul>
</body>
</html>
