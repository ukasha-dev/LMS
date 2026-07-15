<!DOCTYPE html>
<html>
<head><title>Tenant Department Edit</title></head>
<body>
<?php if ($updated): ?>
<p>Department <?php echo (int) $department['id']; ?> updated.</p>
<?php endif; ?>
<h1>Edit Department #<?php echo (int) $department['id']; ?></h1>
<form method="post">
    <input type="text" name="department_name" value="<?php echo htmlspecialchars((string) $department['department_name']); ?>">
    <button type="submit">Update</button>
</form>
</body>
</html>
