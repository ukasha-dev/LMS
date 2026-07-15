<!DOCTYPE html>
<html>
<head><title>Tenant Content Type Edit</title></head>
<body>
<?php if ($updated): ?>
<p>Content type <?php echo (int) $contentType['id']; ?> updated.</p>
<?php endif; ?>
<h1>Edit Content Type #<?php echo (int) $contentType['id']; ?></h1>
<form method="post">
    <input type="text" name="name" value="<?php echo htmlspecialchars((string) $contentType['name']); ?>">
    <input type="text" name="description" value="<?php echo htmlspecialchars((string) $contentType['description']); ?>">
    <button type="submit">Update</button>
</form>
</body>
</html>
