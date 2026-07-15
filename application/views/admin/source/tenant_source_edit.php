<!DOCTYPE html>
<html>
<head><title>Tenant Source Edit</title></head>
<body>
<?php if ($updated): ?>
<p>Source <?php echo (int) $source['id']; ?> updated.</p>
<?php endif; ?>
<h1>Edit Source #<?php echo (int) $source['id']; ?></h1>
<form method="post">
    <input type="text" name="source" value="<?php echo htmlspecialchars((string) $source['source']); ?>">
    <input type="text" name="description" value="<?php echo htmlspecialchars((string) $source['description']); ?>">
    <button type="submit">Update</button>
</form>
</body>
</html>
