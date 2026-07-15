<!DOCTYPE html>
<html>
<head><title>Tenant Itemstore Edit</title></head>
<body>
<?php if ($updated): ?>
<p>Item store <?php echo (int) $itemstore['id']; ?> updated.</p>
<?php endif; ?>
<h1>Edit Item Store #<?php echo (int) $itemstore['id']; ?></h1>
<form method="post">
    <input type="text" name="name" value="<?php echo htmlspecialchars((string) $itemstore['item_store']); ?>">
    <input type="text" name="code" value="<?php echo htmlspecialchars((string) $itemstore['code']); ?>">
    <input type="text" name="description" value="<?php echo htmlspecialchars((string) $itemstore['description']); ?>">
    <button type="submit">Update</button>
</form>
</body>
</html>
