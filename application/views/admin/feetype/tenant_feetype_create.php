<!DOCTYPE html>
<html>
<head><title>Tenant Feetype Create</title></head>
<body>
<?php if ($created): ?>
<p>Fee type created with id <?php echo (int) $id; ?>.</p>
<?php else: ?>
<h1>Create Fee Type</h1>
<form method="post">
    <input type="text" name="name" placeholder="Name">
    <input type="text" name="code" placeholder="Code">
    <input type="text" name="description" placeholder="Description">
    <input type="text" name="nature" placeholder="Nature">
    <button type="submit">Create</button>
</form>
<?php endif; ?>
</body>
</html>
