<!DOCTYPE html>
<html>
<head><title>Tenant Roomtype List</title></head>
<body>
<h1>Room Types (<?php echo count($roomtypeList); ?> real, tenant-scoped rows)</h1>
<ul>
<?php foreach ($roomtypeList as $row): ?>
    <li>Room Type #<?php echo (int) $row['id']; ?> — <?php echo htmlspecialchars((string) $row['room_type']); ?></li>
<?php endforeach; ?>
</ul>
</body>
</html>
