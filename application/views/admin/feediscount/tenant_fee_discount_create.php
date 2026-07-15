<!DOCTYPE html>
<html>
<head><title>Tenant Fee Discount Create</title></head>
<body>
<?php if ($created): ?>
<p>Fee discount created with id <?php echo (int) $id; ?>.</p>
<?php else: ?>
<h1>Create Fee Discount</h1>
<form method="post">
    <input type="text" name="name" placeholder="Name">
    <input type="text" name="code" placeholder="Code">
    <input type="text" name="type" placeholder="Type">
    <input type="text" name="amount" placeholder="Amount">
    <input type="text" name="percentage" placeholder="Percentage">
    <input type="text" name="discount_limit" placeholder="Discount Limit">
    <input type="text" name="expire_date" placeholder="Expire Date (YYYY-MM-DD)">
    <input type="text" name="description" placeholder="Description">
    <button type="submit">Create</button>
</form>
<?php endif; ?>
</body>
</html>
