<!DOCTYPE html>
<html>
<head><title>Tenant Complainttype Create</title></head>
<body>
<?php if ($created): ?>
<p>Complaint type created with id <?php echo (int) $id; ?>.</p>
<?php else: ?>
<h1>Create Complaint Type</h1>
<form method="post">
    <input type="text" name="complaint_type" placeholder="Complaint Type">
    <input type="text" name="description" placeholder="Description">
    <button type="submit">Create</button>
</form>
<?php endif; ?>
</body>
</html>
