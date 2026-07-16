<!DOCTYPE html>
<html>
<head><title>Tenant Notice Create</title></head>
<body>
<?php if ($created): ?>
<p>Notice created with id <?php echo (int) $id; ?>.</p>
<?php else: ?>
<h1>Create Notice</h1>
<form method="post">
    <input type="text" name="title" placeholder="Title">
    <input type="text" name="date" placeholder="Date">
    <textarea name="description" placeholder="Description"></textarea>
    <button type="submit">Create</button>
</form>
<?php endif; ?>
</body>
</html>
