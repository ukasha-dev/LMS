<!DOCTYPE html>
<html>
<head><title>Tenant Menu Create</title></head>
<body>
<?php if ($created): ?>
<p>Menu created with id <?php echo (int) $id; ?>.</p>
<?php else: ?>
<h1>Create Menu</h1>
<form method="post">
    <input type="text" name="menu" placeholder="Menu Name">
    <textarea name="description" placeholder="Description"></textarea>
    <button type="submit">Create</button>
</form>
<?php endif; ?>
</body>
</html>
