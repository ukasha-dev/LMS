<!DOCTYPE html>
<html>
<head><title>Tenant Section List</title></head>
<body>
<h1>Sections (<?php echo count($sectionList); ?> real, tenant-scoped rows)</h1>
<ul>
<?php foreach ($sectionList as $row): ?>
    <li>Section #<?php echo (int) $row['id']; ?> — <?php echo htmlspecialchars((string) $row['section']); ?></li>
<?php endforeach; ?>
</ul>
</body>
</html>
