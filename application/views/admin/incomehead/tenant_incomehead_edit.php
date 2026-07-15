<!DOCTYPE html>
<html>
<head><title>Tenant Incomehead Edit</title></head>
<body>
<?php if ($updated): ?>
<p>Income head <?php echo (int) $incomehead['id']; ?> updated.</p>
<?php endif; ?>
<h1>Edit Income Head #<?php echo (int) $incomehead['id']; ?></h1>
<form method="post">
    <input type="text" name="incomehead" value="<?php echo htmlspecialchars((string) $incomehead['income_category']); ?>">
    <input type="text" name="description" value="<?php echo htmlspecialchars((string) $incomehead['description']); ?>">
    <button type="submit">Update</button>
</form>
</body>
</html>
