<!DOCTYPE html>
<html>
<head><title>Tenant Fee Group Feetype List</title></head>
<body>
<h1>Fee Group Feetypes (<?php echo count($feeGroupFeetypeList); ?> real, tenant-scoped rows)</h1>
<ul>
<?php foreach ($feeGroupFeetypeList as $row): ?>
    <li>Fee Group Feetype #<?php echo (int) $row['id']; ?> — fee group #<?php echo (int) $row['fee_groups_id']; ?>, feetype #<?php echo (int) $row['feetype_id']; ?>, amount <?php echo htmlspecialchars((string) $row['amount']); ?></li>
<?php endforeach; ?>
</ul>
</body>
</html>
