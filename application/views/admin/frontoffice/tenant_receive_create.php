<!DOCTYPE html>
<html>
<head><title>Tenant Receive Create</title></head>
<body>
<?php if ($created): ?>
<p>Receive created with id <?php echo (int) $id; ?>.</p>
<?php else: ?>
<h1>Create Receive</h1>
<form method="post" enctype="multipart/form-data">
    <input type="text" name="ref_no" placeholder="Reference No">
    <input type="text" name="to_title" placeholder="To Title">
    <input type="text" name="address" placeholder="Address">
    <input type="text" name="note" placeholder="Note">
    <input type="text" name="from_title" placeholder="From Title">
    <input type="text" name="date" placeholder="Date (YYYY-MM-DD)">
    <input type="file" name="photo">
    <button type="submit">Create</button>
</form>
<?php endif; ?>
</body>
</html>
