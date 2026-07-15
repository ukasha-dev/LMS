<!DOCTYPE html>
<html>
<head><title>Tenant Leave Type Edit</title></head>
<body>
<?php if ($updated): ?>
<p>Leave type <?php echo (int) $leaveType['id']; ?> updated.</p>
<?php endif; ?>
<h1>Edit Leave Type #<?php echo (int) $leaveType['id']; ?></h1>
<form method="post">
    <input type="text" name="type" value="<?php echo htmlspecialchars((string) $leaveType['type']); ?>">
    <button type="submit">Update</button>
</form>
</body>
</html>
