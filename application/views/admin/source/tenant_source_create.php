<!DOCTYPE html>
<html>
<head><title>Tenant Source Create</title></head>
<body>
<?php if ($created): ?>
<p>Source created with id <?php echo (int) $id; ?>.</p>
<?php else: ?>
<h1>Create Source</h1>
<form method="post">
    <input type="text" name="source" placeholder="Source">
    <input type="text" name="description" placeholder="Description">
    <button type="submit">Create</button>
</form>
<?php endif; ?>
</body>
</html>
