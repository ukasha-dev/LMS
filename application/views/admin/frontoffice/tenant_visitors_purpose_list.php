<!DOCTYPE html>
<html>
<head><title>Tenant Visitors Purpose List</title></head>
<body>
<h1>Visitors Purposes (<?php echo count($visitorsPurposeList); ?> real, tenant-scoped rows)</h1>
<ul>
<?php foreach ($visitorsPurposeList as $row): ?>
    <li>Visitors Purpose #<?php echo (int) $row['id']; ?> — <?php echo htmlspecialchars((string) $row['visitors_purpose']); ?></li>
<?php endforeach; ?>
</ul>
</body>
</html>
