<!DOCTYPE html>
<html>
<head><title>Tenant Designation Create</title></head>
<body>
<?php if ($created): ?>
<p>Designation created with id <?php echo (int) $id; ?>.</p>
<?php else: ?>
<h1>Create Designation</h1>
<form method="post">
    <input type="text" name="designation" placeholder="Designation">
    <button type="submit">Create</button>
</form>
<?php endif; ?>
</body>
</html>
