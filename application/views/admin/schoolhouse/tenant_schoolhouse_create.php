<!DOCTYPE html>
<html>
<head><title>Tenant Schoolhouse Create</title></head>
<body>
<?php if ($created): ?>
<p>School house created with id <?php echo (int) $id; ?>.</p>
<?php else: ?>
<h1>Create School House</h1>
<form method="post">
    <input type="text" name="house_name" placeholder="House Name">
    <input type="text" name="description" placeholder="Description">
    <button type="submit">Create</button>
</form>
<?php endif; ?>
</body>
</html>
