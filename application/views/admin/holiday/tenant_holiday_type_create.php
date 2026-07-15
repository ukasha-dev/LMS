<!DOCTYPE html>
<html>
<head><title>Tenant Holiday Type Create</title></head>
<body>
<?php if ($created): ?>
<p>Holiday type created with id <?php echo (int) $id; ?>.</p>
<?php else: ?>
<h1>Create Holiday Type</h1>
<form method="post">
    <input type="text" name="type" placeholder="Type">
    <button type="submit">Create</button>
</form>
<?php endif; ?>
</body>
</html>
