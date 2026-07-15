<!DOCTYPE html>
<html>
<head><title>Tenant Reference List</title></head>
<body>
<h1>References (<?php echo count($referenceList); ?> real, tenant-scoped rows)</h1>
<ul>
<?php foreach ($referenceList as $row): ?>
    <li>Reference #<?php echo (int) $row['id']; ?> — <?php echo htmlspecialchars((string) $row['reference']); ?></li>
<?php endforeach; ?>
</ul>
</body>
</html>
