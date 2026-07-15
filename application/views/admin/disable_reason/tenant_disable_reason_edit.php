<!DOCTYPE html>
<html>
<head><title>Tenant Disable Reason Edit</title></head>
<body>
<?php if ($updated): ?>
<p>Disable reason <?php echo (int) $disableReason['id']; ?> updated.</p>
<?php endif; ?>
<h1>Edit Disable Reason #<?php echo (int) $disableReason['id']; ?></h1>
<form method="post">
    <input type="text" name="reason" value="<?php echo htmlspecialchars((string) $disableReason['reason']); ?>">
    <button type="submit">Update</button>
</form>
</body>
</html>
