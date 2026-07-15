<!DOCTYPE html>
<html>
<head><title>Tenant Fee Discount List</title></head>
<body>
<h1>Fee Discounts (<?php echo count($feeDiscountList); ?> real, tenant-scoped rows)</h1>
<ul>
<?php foreach ($feeDiscountList as $row): ?>
    <li>Fee Discount #<?php echo (int) $row['id']; ?> — <?php echo htmlspecialchars((string) $row['name']); ?> (<?php echo htmlspecialchars((string) $row['type']); ?>)</li>
<?php endforeach; ?>
</ul>
</body>
</html>
