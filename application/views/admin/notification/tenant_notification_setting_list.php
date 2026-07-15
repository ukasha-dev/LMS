<!DOCTYPE html>
<html>
<head><title>Tenant Notification Setting List</title></head>
<body>
<h1>Notification Settings (<?php echo count($notificationSettingList); ?> real, global reference rows)</h1>
<ul>
<?php foreach ($notificationSettingList as $row): ?>
    <li>Notification #<?php echo (int) $row['id']; ?> — <?php echo htmlspecialchars((string) $row['type']); ?></li>
<?php endforeach; ?>
</ul>
</body>
</html>
