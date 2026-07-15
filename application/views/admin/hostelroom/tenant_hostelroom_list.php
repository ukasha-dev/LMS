<!DOCTYPE html>
<html>
<head><title>Tenant Hostelroom List</title></head>
<body>
<h1>Hostel Rooms (<?php echo count($hostelroomList); ?> real, tenant-scoped rows)</h1>
<ul>
<?php foreach ($hostelroomList as $row): ?>
    <li>Hostel Room #<?php echo (int) $row['id']; ?> — <?php echo htmlspecialchars((string) $row['room_no']); ?></li>
<?php endforeach; ?>
</ul>
</body>
</html>
