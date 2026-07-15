<!DOCTYPE html>
<html>
<head><title>Tenant Category Edit</title></head>
<body>
<?php if ($updated): ?>
<p>Category <?php echo (int) $category['id']; ?> updated.</p>
<?php endif; ?>
<h1>Edit Category #<?php echo (int) $category['id']; ?></h1>
<form method="post">
    <input type="text" name="category" value="<?php echo htmlspecialchars((string) $category['category']); ?>">
    <button type="submit">Update</button>
</form>
</body>
</html>
