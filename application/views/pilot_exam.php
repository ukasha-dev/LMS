<!DOCTYPE html>
<html>
<head><title>Pilot Exam Results</title></head>
<body>
<h1>Exam Results (<?php echo count($rows); ?> results, <?php echo $exam_group_count; ?> exam groups)</h1>
<ul>
<?php foreach ($rows as $row): ?>
    <li><?php echo htmlspecialchars($row['student']); ?> — <?php echo htmlspecialchars($row['subject']); ?>: <?php echo htmlspecialchars((string) $row['marks']); ?> (<?php echo htmlspecialchars((string) $row['attendence']); ?>)</li>
<?php endforeach; ?>
</ul>
</body>
</html>
