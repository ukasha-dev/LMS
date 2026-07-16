<!DOCTYPE html>
<html>
<head><title>Tenant Banner Add</title></head>
<body>
<?php if ($created): ?>
<p>Banner link created with id <?php echo (int) $id; ?>.</p>
<?php else: ?>
<h1>Add Banner Image</h1>
<form method="post">
    <input type="text" name="content_id" placeholder="Media Id">
    <button type="submit">Add</button>
</form>
<?php endif; ?>
</body>
</html>
