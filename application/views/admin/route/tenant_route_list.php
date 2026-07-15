<!DOCTYPE html>
<html>
<head><title>Tenant Route List</title></head>
<body>
<h1>Routes (<?php echo count($routeList); ?> real, tenant-scoped rows)</h1>
<ul>
<?php foreach ($routeList as $row): ?>
    <li>Route #<?php echo (int) $row['id']; ?> — <?php echo htmlspecialchars((string) $row['route_title']); ?></li>
<?php endforeach; ?>
</ul>
</body>
</html>
