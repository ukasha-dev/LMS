<!DOCTYPE html>
<html>
<head><title>Tenant Category Create</title></head>
<body>
<?php if ($created): ?>
<p>Category created with id <?php echo (int) $id; ?>.</p>
<?php else: ?>
<h1>Create Category</h1>
<form method="post">
    <input type="text" name="category" placeholder="Category">
    <button type="submit">Create</button>
</form>
<?php endif; ?>
</body>
</html>
