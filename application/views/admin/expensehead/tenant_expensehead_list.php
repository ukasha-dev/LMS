<!DOCTYPE html>
<html>
<head><title>Tenant Expensehead List</title></head>
<body>
<h1>Expense Heads (<?php echo count($expenseheadList); ?> real, tenant-scoped rows)</h1>
<ul>
<?php foreach ($expenseheadList as $row): ?>
    <li>Expense Head #<?php echo (int) $row['id']; ?> — <?php echo htmlspecialchars((string) $row['exp_category']); ?></li>
<?php endforeach; ?>
</ul>
</body>
</html>
