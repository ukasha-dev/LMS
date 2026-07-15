<!DOCTYPE html>
<html>
<head><title>Tenant Incomehead List</title></head>
<body>
<h1>Income Heads (<?php echo count($incomeheadList); ?> real, tenant-scoped rows)</h1>
<ul>
<?php foreach ($incomeheadList as $row): ?>
    <li>Income Head #<?php echo (int) $row['id']; ?> — <?php echo htmlspecialchars((string) $row['income_category']); ?></li>
<?php endforeach; ?>
</ul>
</body>
</html>
