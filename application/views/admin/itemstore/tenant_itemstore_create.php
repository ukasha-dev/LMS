<!DOCTYPE html>
<html>
<head><title>Tenant Itemstore Create</title></head>
<body>
<?php if ($created): ?>
<p>Item store created with id <?php echo (int) $id; ?>.</p>
<?php else: ?>
<h1>Create Item Store</h1>
<form method="post">
    <input type="text" name="name" placeholder="Name">
    <input type="text" name="code" placeholder="Code">
    <input type="text" name="description" placeholder="Description">
    <button type="submit">Create</button>
</form>
<?php endif; ?>
</body>
</html>
