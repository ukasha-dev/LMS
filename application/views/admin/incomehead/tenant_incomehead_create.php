<!DOCTYPE html>
<html>
<head><title>Tenant Incomehead Create</title></head>
<body>
<?php if ($created): ?>
<p>Income head created with id <?php echo (int) $id; ?>.</p>
<?php else: ?>
<h1>Create Income Head</h1>
<form method="post">
    <input type="text" name="incomehead" placeholder="Income Head">
    <input type="text" name="description" placeholder="Description">
    <button type="submit">Create</button>
</form>
<?php endif; ?>
</body>
</html>
