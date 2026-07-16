<!DOCTYPE html>
<html>
<head><title>Tenant Complaint Create</title></head>
<body>
<?php if ($created): ?>
<p>Complaint created with id <?php echo (int) $id; ?>.</p>
<?php if (!empty($image)): ?>
<p>image: <?php echo htmlspecialchars((string) $image); ?></p>
<?php endif; ?>
<?php else: ?>
<h1>Create Complaint</h1>
<form method="post" enctype="multipart/form-data">
    <input type="text" name="complaint_type" placeholder="Complaint Type">
    <input type="text" name="source" placeholder="Source">
    <input type="text" name="name" placeholder="Complain By">
    <input type="text" name="contact" placeholder="Contact">
    <input type="text" name="email" placeholder="Email">
    <input type="text" name="date" placeholder="Date (YYYY-MM-DD)">
    <input type="text" name="description" placeholder="Description">
    <input type="text" name="action_taken" placeholder="Action Taken">
    <input type="text" name="assigned" placeholder="Assigned">
    <input type="text" name="note" placeholder="Note">
    <input type="file" name="photo">
    <button type="submit">Create</button>
</form>
<?php endif; ?>
</body>
</html>
