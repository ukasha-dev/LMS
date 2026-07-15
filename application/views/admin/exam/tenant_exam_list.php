<!DOCTYPE html>
<html>
<head><title>Tenant Exam List</title></head>
<body>
<h1>Exams (<?php echo count($examList); ?> real, tenant-scoped rows)</h1>
<ul>
<?php foreach ($examList as $row): ?>
    <li>Exam #<?php echo (int) $row['id']; ?> — <?php echo htmlspecialchars((string) $row['name']); ?></li>
<?php endforeach; ?>
</ul>
</body>
</html>
