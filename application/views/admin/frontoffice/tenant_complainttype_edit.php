<!DOCTYPE html>
<html>
<head><title>Tenant Complainttype Edit</title></head>
<body>
<?php if ($updated): ?>
<p>Complaint type <?php echo (int) $complainttype['id']; ?> updated.</p>
<?php endif; ?>
<h1>Edit Complaint Type #<?php echo (int) $complainttype['id']; ?></h1>
<form method="post">
    <input type="text" name="complaint_type" value="<?php echo htmlspecialchars((string) $complainttype['complaint_type']); ?>">
    <input type="text" name="description" value="<?php echo htmlspecialchars((string) $complainttype['description']); ?>">
    <button type="submit">Update</button>
</form>
</body>
</html>
