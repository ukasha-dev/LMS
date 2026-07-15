<!DOCTYPE html>
<html>
<head><title>Tenant Session Edit</title></head>
<body>
<?php if ($updated): ?>
<p>Session <?php echo (int) $sessionRow['id']; ?> updated.</p>
<?php endif; ?>
<h1>Edit Session #<?php echo (int) $sessionRow['id']; ?></h1>
<form method="post">
    <input type="text" name="session" value="<?php echo htmlspecialchars((string) $sessionRow['session']); ?>">
    <button type="submit">Update</button>
</form>
</body>
</html>
