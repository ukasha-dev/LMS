<!DOCTYPE html>
<html>
<head><title>Tenant Hostel Create</title></head>
<body>
<?php if ($created): ?>
<p>Hostel created with id <?php echo (int) $id; ?>.</p>
<?php else: ?>
<h1>Create Hostel</h1>
<form method="post">
    <input type="text" name="hostel_name" placeholder="Hostel Name">
    <input type="text" name="type" placeholder="Type">
    <input type="text" name="address" placeholder="Address">
    <input type="text" name="intake" placeholder="Intake">
    <input type="text" name="description" placeholder="Description">
    <button type="submit">Create</button>
</form>
<?php endif; ?>
</body>
</html>
