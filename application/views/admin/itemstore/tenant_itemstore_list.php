<!DOCTYPE html>
<html>
<head><title>Tenant Itemstore List</title></head>
<body>
<h1>Item Stores (<?php echo count($itemstoreList); ?> real, tenant-scoped rows)</h1>
<ul>
<?php foreach ($itemstoreList as $row): ?>
    <li>Item Store #<?php echo (int) $row['id']; ?> — <?php echo htmlspecialchars((string) $row['item_store']); ?></li>
<?php endforeach; ?>
</ul>
</body>
</html>
