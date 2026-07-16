<!DOCTYPE html>
<html>
<head><title>Tenant Page Create</title></head>
<body>
<?php if ($created): ?>
<p>Page created with id <?php echo (int) $id; ?>.</p>
<?php else: ?>
<h1>Create Page</h1>
<form method="post">
    <input type="text" name="title" placeholder="Title">
    <textarea name="description" placeholder="Description"></textarea>
    <button type="submit">Create</button>
</form>
<?php endif; ?>
</body>
</html>
