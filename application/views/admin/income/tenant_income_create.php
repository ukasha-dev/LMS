<!DOCTYPE html>
<html>
<head><title>Tenant Income Create</title></head>
<body>
<?php if ($created): ?>
<p>Income created with id <?php echo (int) $id; ?>.</p>
<?php else: ?>
<h1>Create Income</h1>
<form method="post" enctype="multipart/form-data">
    <input type="text" name="inc_head_id" placeholder="Income Head Id">
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
