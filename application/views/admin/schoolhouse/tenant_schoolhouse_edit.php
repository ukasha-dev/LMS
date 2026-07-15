<!DOCTYPE html>
<html>
<head><title>Tenant Schoolhouse Edit</title></head>
<body>
<?php if ($updated): ?>
<p>School house <?php echo (int) $schoolhouse['id']; ?> updated.</p>
<?php endif; ?>
<h1>Edit School House #<?php echo (int) $schoolhouse['id']; ?></h1>
<form method="post">
    <input type="text" name="house_name" value="<?php echo htmlspecialchars((string) $schoolhouse['house_name']); ?>">
    <input type="text" name="description" value="<?php echo htmlspecialchars((string) $schoolhouse['description']); ?>">
    <button type="submit">Update</button>
</form>
</body>
</html>
