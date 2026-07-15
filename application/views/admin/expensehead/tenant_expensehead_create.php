<!DOCTYPE html>
<html>
<head><title>Tenant Expensehead Create</title></head>
<body>
<?php if ($created): ?>
<p>Expense head created with id <?php echo (int) $id; ?>.</p>
<?php else: ?>
<h1>Create Expense Head</h1>
<form method="post">
    <input type="text" name="expensehead" placeholder="Expense Head">
    <input type="text" name="description" placeholder="Description">
    <button type="submit">Create</button>
</form>
<?php endif; ?>
</body>
</html>
