<!DOCTYPE html>
<html>
<head><title>Tenant Fee Session Group List</title></head>
<body>
<h1>Fee Session Groups (<?php echo count($feeSessionGroupList); ?> real, tenant-scoped rows)</h1>
<ul>
<?php foreach ($feeSessionGroupList as $row): ?>
    <li>Fee Session Group #<?php echo (int) $row['id']; ?> — fee group #<?php echo (int) $row['fee_groups_id']; ?>, session #<?php echo (int) $row['session_id']; ?></li>
<?php endforeach; ?>
</ul>
</body>
</html>
