<!DOCTYPE html>
<html>
<head><title>Tenant Fees List</title></head>
<body>
<h1>Student Fee Deposits (<?php echo count($feesList); ?> real, tenant-scoped rows)</h1>
<ul>
<?php foreach ($feesList as $deposit): ?>
    <li>Deposit #<?php echo (int) $deposit['id']; ?> — student_fees_master_id <?php echo (int) $deposit['student_fees_master_id']; ?>, fee_groups_feetype_id <?php echo (int) $deposit['fee_groups_feetype_id']; ?></li>
<?php endforeach; ?>
</ul>
</body>
</html>
