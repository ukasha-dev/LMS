<!DOCTYPE html>
<html>
<head><title>Tenant Itemsupplier List</title></head>
<body>
<h1>Item Suppliers (<?php echo count($itemsupplierList); ?> real, tenant-scoped rows)</h1>
<ul>
<?php foreach ($itemsupplierList as $row): ?>
    <li>Item Supplier #<?php echo (int) $row['id']; ?> — <?php echo htmlspecialchars((string) $row['item_supplier']); ?></li>
<?php endforeach; ?>
</ul>
</body>
</html>
