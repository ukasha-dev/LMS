<!DOCTYPE html>
<html>
<head><title>Tenant Feetype Edit</title></head>
<body>
<?php if ($updated): ?>
<p>Fee type <?php echo (int) $feetype['id']; ?> updated.</p>
<?php endif; ?>
<h1>Edit Fee Type #<?php echo (int) $feetype['id']; ?></h1>
<form method="post">
    <input type="text" name="name" value="<?php echo htmlspecialchars((string) $feetype['type']); ?>">
    <input type="text" name="code" value="<?php echo htmlspecialchars((string) $feetype['code']); ?>">
    <input type="text" name="description" value="<?php echo htmlspecialchars((string) $feetype['description']); ?>">
    <input type="text" name="nature" value="<?php echo htmlspecialchars((string) $feetype['nature']); ?>">
    <button type="submit">Update</button>
</form>
</body>
</html>
