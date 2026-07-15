<!DOCTYPE html>
<html>
<head><title>Tenant Disable Reason Create</title></head>
<body>
<?php if ($created): ?>
<p>Disable reason created with id <?php echo (int) $id; ?>.</p>
<?php else: ?>
<h1>Create Disable Reason</h1>
<form method="post">
    <input type="text" name="reason" placeholder="Reason">
    <button type="submit">Create</button>
</form>
<?php endif; ?>
</body>
</html>
