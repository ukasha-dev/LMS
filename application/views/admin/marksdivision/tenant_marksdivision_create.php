<!DOCTYPE html>
<html>
<head><title>Tenant Marksdivision Create</title></head>
<body>
<?php if ($created): ?>
<p>Mark division created with id <?php echo (int) $id; ?>.</p>
<?php else: ?>
<h1>Create Mark Division</h1>
<form method="post">
    <input type="text" name="name" placeholder="Name">
    <input type="text" name="percentage_from" placeholder="Percentage From">
    <input type="text" name="percentage_to" placeholder="Percentage To">
    <button type="submit">Create</button>
</form>
<?php endif; ?>
</body>
</html>
