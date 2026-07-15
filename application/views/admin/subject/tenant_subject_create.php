<!DOCTYPE html>
<html>
<head><title>Tenant Subject Create</title></head>
<body>
<?php if ($created): ?>
<p>Subject created with id <?php echo (int) $id; ?>.</p>
<?php else: ?>
<h1>Create Subject</h1>
<form method="post">
    <input type="text" name="name" placeholder="Name">
    <input type="text" name="code" placeholder="Code">
    <input type="text" name="type" placeholder="Type">
    <button type="submit">Create</button>
</form>
<?php endif; ?>
</body>
</html>
