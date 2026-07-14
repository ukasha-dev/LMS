<!DOCTYPE html>
<html>
<head><title>Tenant Grade List</title></head>
<body>
<h1>Grades (<?php echo count($gradeList); ?> real, tenant-scoped rows)</h1>
<ul>
<?php foreach ($gradeList as $row): ?>
    <li>Grade #<?php echo (int) $row['id']; ?> — <?php echo htmlspecialchars((string) $row['name']); ?> (<?php echo htmlspecialchars((string) $row['mark_from']); ?>-<?php echo htmlspecialchars((string) $row['mark_upto']); ?>)</li>
<?php endforeach; ?>
</ul>
</body>
</html>
