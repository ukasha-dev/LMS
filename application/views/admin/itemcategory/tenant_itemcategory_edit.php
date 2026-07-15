<!DOCTYPE html>
<html>
<head><title>Tenant Itemcategory Edit</title></head>
<body>
<?php if ($updated): ?>
<p>Item category <?php echo (int) $itemcategory['id']; ?> updated.</p>
<?php endif; ?>
<h1>Edit Item Category #<?php echo (int) $itemcategory['id']; ?></h1>
<form method="post">
    <input type="text" name="item_category" value="<?php echo htmlspecialchars((string) $itemcategory['item_category']); ?>">
    <input type="text" name="description" value="<?php echo htmlspecialchars((string) $itemcategory['description']); ?>">
    <button type="submit">Update</button>
</form>
</body>
</html>
