<!DOCTYPE html>
<html>
<head><title>Tenant Generalcall Create</title></head>
<body>
<?php if ($created): ?>
<p>General call created with id <?php echo (int) $id; ?>.</p>
<?php else: ?>
<h1>Create General Call</h1>
<form method="post">
    <input type="text" name="name" placeholder="Name">
    <input type="text" name="contact" placeholder="Contact">
    <input type="text" name="date" placeholder="Date (YYYY-MM-DD)">
    <input type="text" name="description" placeholder="Description">
    <input type="text" name="follow_up_date" placeholder="Follow Up Date (YYYY-MM-DD)">
    <input type="text" name="call_duration" placeholder="Call Duration">
    <input type="text" name="note" placeholder="Note">
    <input type="text" name="call_type" placeholder="Call Type">
    <button type="submit">Create</button>
</form>
<?php endif; ?>
</body>
</html>
