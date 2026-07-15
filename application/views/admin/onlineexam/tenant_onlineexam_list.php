<!DOCTYPE html>
<html>
<head><title>Tenant Onlineexam List</title></head>
<body>
<h1>Online Exams (<?php echo count($onlineexamList); ?> real, tenant-scoped rows)</h1>
<ul>
<?php foreach ($onlineexamList as $row): ?>
    <li>Exam #<?php echo (int) $row['id']; ?> — <?php echo htmlspecialchars((string) $row['exam']); ?></li>
<?php endforeach; ?>
</ul>
</body>
</html>
