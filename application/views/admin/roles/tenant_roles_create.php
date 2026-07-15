<!DOCTYPE html>
<html>
<head><title>Tenant Role Create</title></head>
<body>
<?php if ($created): ?>
<p>Role created with id <?php echo (int) $id; ?>.</p>
<?php else: ?>
<h1>Create Role</h1>
<form method="post">
    <input type="text" name="name" placeholder="Name">
    <button type="submit">Create</button>
</form>
<?php endif; ?>
</body>
</html>
