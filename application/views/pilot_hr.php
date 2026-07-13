<!DOCTYPE html>
<html>
<head><title>Pilot HR</title></head>
<body>
<h1>Staff Leave Allotments (<?php echo count($rows); ?> results, <?php echo $department_count; ?> departments, <?php echo $designation_count; ?> designations)</h1>
<ul>
<?php foreach ($rows as $row): ?>
    <li><?php echo htmlspecialchars($row['staff']); ?> — <?php echo htmlspecialchars($row['leave_type']); ?>: <?php echo htmlspecialchars((string) $row['alloted_leave']); ?></li>
<?php endforeach; ?>
</ul>
</body>
</html>
