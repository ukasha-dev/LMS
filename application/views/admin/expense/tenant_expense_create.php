<!DOCTYPE html>
<html>
<head><title>Tenant Expense Create</title></head>
<body>
<?php if ($created): ?>
<p>Expense created with id <?php echo (int) $id; ?>.</p>
<?php else: ?>
<h1>Create Expense</h1>
<form method="post" enctype="multipart/form-data">
    <input type="text" name="exp_head_id" placeholder="Expense Head Id">
    <input type="text" name="name" placeholder="Name">
    <input type="text" name="date" placeholder="Date (YYYY-MM-DD)">
    <input type="text" name="amount" placeholder="Amount">
    <input type="text" name="invoice_no" placeholder="Invoice No">
    <input type="text" name="description" placeholder="Description">
    <input type="file" name="documents">
    <button type="submit">Create</button>
</form>
<?php endif; ?>
</body>
</html>
