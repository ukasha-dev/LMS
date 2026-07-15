<!DOCTYPE html>
<html>
<head><title>Tenant Section Create</title></head>
<body>
<?php if ($created): ?>
<p>Section created with id <?php echo (int) $id; ?>.</p>
<?php else: ?>
<h1>Create Section</h1>
<form method="post">
    <input type="text" name="section" placeholder="Section">
    <button type="submit">Create</button>
</form>
<?php endif; ?>
</body>
</html>
