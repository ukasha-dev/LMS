<!DOCTYPE html>
<html>
<head><title>Tenant Content Type Create</title></head>
<body>
<?php if ($created): ?>
<p>Content type created with id <?php echo (int) $id; ?>.</p>
<?php else: ?>
<h1>Create Content Type</h1>
<form method="post">
    <input type="text" name="name" placeholder="Name">
    <input type="text" name="description" placeholder="Description">
    <button type="submit">Create</button>
</form>
<?php endif; ?>
</body>
</html>
