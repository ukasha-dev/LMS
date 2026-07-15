<!DOCTYPE html>
<html>
<head><title>Tenant Feegroup Edit</title></head>
<body>
<?php if ($updated): ?>
<p>Fee group <?php echo (int) $feegroup['id']; ?> updated.</p>
<?php endif; ?>
<h1>Edit Fee Group #<?php echo (int) $feegroup['id']; ?></h1>
<form method="post">
    <input type="text" name="name" value="<?php echo htmlspecialchars((string) $feegroup['name']); ?>">
    <input type="text" name="description" value="<?php echo htmlspecialchars((string) $feegroup['description']); ?>">
    <input type="text" name="nature" value="<?php echo htmlspecialchars((string) $feegroup['nature']); ?>">
    <button type="submit">Update</button>
</form>
</body>
</html>
