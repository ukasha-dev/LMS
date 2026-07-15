<!DOCTYPE html>
<html>
<head><title>Tenant Hostel List</title></head>
<body>
<h1>Hostels (<?php echo count($hostelList); ?> real, tenant-scoped rows)</h1>
<ul>
<?php foreach ($hostelList as $row): ?>
    <li>Hostel #<?php echo (int) $row['id']; ?> — <?php echo htmlspecialchars((string) $row['hostel_name']); ?></li>
<?php endforeach; ?>
</ul>
</body>
</html>
