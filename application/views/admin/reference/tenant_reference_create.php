<!DOCTYPE html>
<html>
<head><title>Tenant Reference Create</title></head>
<body>
<?php if ($created): ?>
<p>Reference created with id <?php echo (int) $id; ?>.</p>
<?php else: ?>
<h1>Create Reference</h1>
<form method="post">
    <input type="text" name="reference" placeholder="Reference">
    <input type="text" name="description" placeholder="Description">
    <button type="submit">Create</button>
</form>
<?php endif; ?>
</body>
</html>
