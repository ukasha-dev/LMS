<!DOCTYPE html>
<html>
<head><title>Tenant Marksdivision Edit</title></head>
<body>
<?php if ($updated): ?>
<p>Mark division <?php echo (int) $marksdivision['id']; ?> updated.</p>
<?php endif; ?>
<h1>Edit Mark Division #<?php echo (int) $marksdivision['id']; ?></h1>
<form method="post">
    <input type="text" name="name" value="<?php echo htmlspecialchars((string) $marksdivision['name']); ?>">
    <input type="text" name="percentage_from" value="<?php echo htmlspecialchars((string) $marksdivision['percentage_from']); ?>">
    <input type="text" name="percentage_to" value="<?php echo htmlspecialchars((string) $marksdivision['percentage_to']); ?>">
    <button type="submit">Update</button>
</form>
</body>
</html>
