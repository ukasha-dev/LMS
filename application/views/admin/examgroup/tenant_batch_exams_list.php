<!DOCTYPE html>
<html>
<head><title>Tenant Exam Group Batch Exams List</title></head>
<body>
<h1>Batch Exams (<?php echo count($batchExamsList); ?> real, tenant-scoped rows)</h1>
<ul>
<?php foreach ($batchExamsList as $row): ?>
    <li>Batch Exam #<?php echo (int) $row['id']; ?> — <?php echo htmlspecialchars((string) $row['exam']); ?></li>
<?php endforeach; ?>
</ul>
</body>
</html>
