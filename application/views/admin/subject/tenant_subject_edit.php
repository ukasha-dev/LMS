<!DOCTYPE html>
<html>
<head><title>Tenant Subject Edit</title></head>
<body>
<?php if ($updated): ?>
<p>Subject <?php echo (int) $subject['id']; ?> updated.</p>
<?php endif; ?>
<h1>Edit Subject #<?php echo (int) $subject['id']; ?></h1>
<form method="post">
    <input type="text" name="name" value="<?php echo htmlspecialchars((string) $subject['name']); ?>">
    <input type="text" name="code" value="<?php echo htmlspecialchars((string) $subject['code']); ?>">
    <input type="text" name="type" value="<?php echo htmlspecialchars((string) $subject['type']); ?>">
    <button type="submit">Update</button>
</form>
</body>
</html>
