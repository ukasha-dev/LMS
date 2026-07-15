<!DOCTYPE html>
<html>
<head><title>Tenant Category List</title></head>
<body>
<h1>Categories (<?php echo count($categoryList); ?> real, tenant-scoped rows)</h1>
<ul>
<?php foreach ($categoryList as $row): ?>
    <li>Category #<?php echo (int) $row['id']; ?> — <?php echo htmlspecialchars((string) $row['category']); ?></li>
<?php endforeach; ?>
</ul>
</body>
</html>
