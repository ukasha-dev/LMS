<!DOCTYPE html>
<html>
<head><title>Tenant Leave Type Create</title></head>
<body>
<?php if ($created): ?>
<p>Leave type created with id <?php echo (int) $id; ?>.</p>
<?php else: ?>
<h1>Create Leave Type</h1>
<form method="post">
    <input type="text" name="type" placeholder="Leave Type">
    <button type="submit">Create</button>
</form>
<?php endif; ?>
</body>
</html>
