<!DOCTYPE html>
<html>
<head><title>Tenant Alumni Create</title></head>
<body>
<?php if ($created): ?>
<p>Alumni record created with id <?php echo (int) $id; ?>.</p>
<?php else: ?>
<h1>Create Alumni Record</h1>
<form method="post" enctype="multipart/form-data">
    <input type="text" name="student_id" placeholder="Student Id">
    <input type="text" name="current_email" placeholder="Current Email">
    <input type="text" name="current_phone" placeholder="Current Phone">
    <input type="text" name="occupation" placeholder="Occupation">
    <input type="text" name="address" placeholder="Address">
    <input type="file" name="documents">
    <button type="submit">Create</button>
</form>
<?php endif; ?>
</body>
</html>
