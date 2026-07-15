<!DOCTYPE html>
<html>
<head><title>Tenant Expensehead Edit</title></head>
<body>
<?php if ($updated): ?>
<p>Expense head <?php echo (int) $expensehead['id']; ?> updated.</p>
<?php endif; ?>
<h1>Edit Expense Head #<?php echo (int) $expensehead['id']; ?></h1>
<form method="post">
    <input type="text" name="expensehead" value="<?php echo htmlspecialchars((string) $expensehead['exp_category']); ?>">
    <input type="text" name="description" value="<?php echo htmlspecialchars((string) $expensehead['description']); ?>">
    <button type="submit">Update</button>
</form>
</body>
</html>
