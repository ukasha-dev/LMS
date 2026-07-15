<!DOCTYPE html>
<html>
<head><title>Tenant Holiday Type List</title></head>
<body>
<h1>Holiday Types (<?php echo count($holidayTypeList); ?> real, tenant-scoped rows)</h1>
<ul>
<?php foreach ($holidayTypeList as $row): ?>
    <li>Holiday Type #<?php echo (int) $row['id']; ?> — <?php echo htmlspecialchars((string) $row['type']); ?></li>
<?php endforeach; ?>
</ul>
</body>
</html>
