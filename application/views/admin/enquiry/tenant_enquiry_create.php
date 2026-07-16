<!DOCTYPE html>
<html>
<head><title>Tenant Enquiry Create</title></head>
<body>
<?php if ($created): ?>
<p>Enquiry created with id <?php echo (int) $id; ?>.</p>
<?php else: ?>
<h1>Create Enquiry</h1>
<form method="post">
    <input type="text" name="name" placeholder="Name">
    <input type="text" name="contact" placeholder="Contact">
    <input type="text" name="reference" placeholder="Reference">
    <input type="text" name="source" placeholder="Source">
    <input type="text" name="date" placeholder="Date">
    <input type="text" name="follow_up_date" placeholder="Follow Up Date">
    <textarea name="description" placeholder="Description"></textarea>
    <input type="text" name="assigned" placeholder="Assigned Staff Id">
    <input type="text" name="class_id" placeholder="Class Id">
    <button type="submit">Create</button>
</form>
<?php endif; ?>
</body>
</html>
