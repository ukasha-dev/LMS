<!DOCTYPE html>
<html>
<head><title>Tenant Itemcategory List</title></head>
<body>
<h1>Item Categories (<?php echo count($itemcategoryList); ?> real, tenant-scoped rows)</h1>
<ul>
<?php foreach ($itemcategoryList as $row): ?>
    <li>Item Category #<?php echo (int) $row['id']; ?> — <?php echo htmlspecialchars((string) $row['item_category']); ?></li>
<?php endforeach; ?>
</ul>
</body>
</html>
