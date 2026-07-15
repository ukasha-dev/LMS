<!DOCTYPE html>
<html>
<head><title>Tenant Role Edit</title></head>
<body>
<?php if ($updated): ?>
<p>Role <?php echo (int) $role['id']; ?> updated.</p>
<?php endif; ?>
<h1>Edit Role #<?php echo (int) $role['id']; ?></h1>
<form method="post">
    <input type="text" name="name" value="<?php echo htmlspecialchars((string) $role['name']); ?>">
    <button type="submit">Update</button>
</form>
</body>
</html>
