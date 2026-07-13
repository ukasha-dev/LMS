<!DOCTYPE html>
<html>
<head><title>Pilot Fees</title></head>
<body>
<h1>Student Fee Deposits (<?php echo count($rows); ?> results)</h1>
<ul>
<?php foreach ($rows as $row): ?>
    <li><?php echo htmlspecialchars($row['student']); ?> — <?php echo htmlspecialchars($row['fee_group']); ?> / <?php echo htmlspecialchars($row['fee_type']); ?> (<?php echo (int) $row['discount_count']; ?> discount(s) applied)</li>
<?php endforeach; ?>
</ul>
</body>
</html>
