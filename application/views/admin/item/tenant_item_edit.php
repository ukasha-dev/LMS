<!DOCTYPE html>
<html>
<head><title>Tenant Item Edit</title></head>
<body>
<?php if ($updated): ?>
<p>Item <?php echo (int) $item['id']; ?> updated.</p>
<?php endif; ?>
<h1>Edit Item #<?php echo (int) $item['id']; ?></h1>
<p>image: <?php echo htmlspecialchars((string) ($item['item_photo'] ?? '')); ?></p>
<form method="post" enctype="multipart/form-data">
    <input type="text" name="name" value="<?php echo htmlspecialchars((string) $item['name']); ?>">
    <input type="text" name="unit" value="<?php echo htmlspecialchars((string) $item['unit']); ?>">
    <input type="text" name="description" value="<?php echo htmlspecialchars((string) $item['description']); ?>">
    <input type="text" name="item_category_id" value="<?php echo htmlspecialchars((string) $item['item_category_id']); ?>">
    <input type="file" name="item_photo">
    <button type="submit">Update</button>
</form>
</body>
</html>
