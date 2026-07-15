<!DOCTYPE html>
<html>
<head><title>Tenant Language List</title></head>
<body>
<h1>Languages (<?php echo count($languageList); ?> real, global rows)</h1>
<ul>
<?php foreach ($languageList as $row): ?>
    <li>Language #<?php echo (int) $row['id']; ?> — <?php echo htmlspecialchars((string) $row['language']); ?> (<?php echo htmlspecialchars((string) $row['short_code']); ?>)</li>
<?php endforeach; ?>
</ul>
</body>
</html>
