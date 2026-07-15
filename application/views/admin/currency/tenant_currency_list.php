<!DOCTYPE html>
<html>
<head><title>Tenant Currency List</title></head>
<body>
<h1>Currencies (<?php echo count($currencyList); ?> real, global rows)</h1>
<ul>
<?php foreach ($currencyList as $row): ?>
    <li>Currency #<?php echo (int) $row['id']; ?> — <?php echo htmlspecialchars((string) $row['name']); ?> (<?php echo htmlspecialchars((string) $row['symbol']); ?>)</li>
<?php endforeach; ?>
</ul>
</body>
</html>
