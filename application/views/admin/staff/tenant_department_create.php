<!DOCTYPE html>
<html>
<head><title>Tenant Department Create</title></head>
<body>
<?php if ($created): ?>
<p>Department created with id <?php echo (int) $id; ?>.</p>
<?php else: ?>
<h1>Create Department</h1>
<form method="post">
    <input type="text" name="department_name" placeholder="Department Name">
    <button type="submit">Create</button>
</form>
<?php endif; ?>
</body>
</html>
