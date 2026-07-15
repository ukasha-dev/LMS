<!DOCTYPE html>
<html>
<head><title>Tenant Pickuppoint List</title></head>
<body>
<h1>Pickup Points (<?php echo count($pickuppointList); ?> real, tenant-scoped rows)</h1>
<ul>
<?php foreach ($pickuppointList as $row): ?>
    <li>Pickup Point #<?php echo (int) $row['id']; ?> — <?php echo htmlspecialchars((string) $row['name']); ?></li>
<?php endforeach; ?>
</ul>
</body>
</html>
