<!DOCTYPE html>
<html>
<head><title>Tenant Complaint Edit</title></head>
<body>
<?php if ($updated): ?>
<p>Complaint <?php echo (int) $complaint['id']; ?> updated.</p>
<?php endif; ?>
<h1>Edit Complaint #<?php echo (int) $complaint['id']; ?></h1>
<p>image: <?php echo htmlspecialchars((string) ($complaint['image'] ?? '')); ?></p>
<form method="post" enctype="multipart/form-data">
    <input type="text" name="complaint_type" value="<?php echo htmlspecialchars((string) $complaint['complaint_type']); ?>">
    <input type="text" name="source" value="<?php echo htmlspecialchars((string) $complaint['source']); ?>">
    <input type="text" name="name" value="<?php echo htmlspecialchars((string) $complaint['name']); ?>">
    <input type="text" name="contact" value="<?php echo htmlspecialchars((string) $complaint['contact']); ?>">
    <input type="text" name="email" value="<?php echo htmlspecialchars((string) $complaint['email']); ?>">
    <input type="text" name="date" value="<?php echo htmlspecialchars((string) $complaint['date']); ?>">
    <input type="text" name="description" value="<?php echo htmlspecialchars((string) $complaint['description']); ?>">
    <input type="text" name="action_taken" value="<?php echo htmlspecialchars((string) $complaint['action_taken']); ?>">
    <input type="text" name="assigned" value="<?php echo htmlspecialchars((string) $complaint['assigned']); ?>">
    <input type="text" name="note" value="<?php echo htmlspecialchars((string) $complaint['note']); ?>">
    <input type="file" name="photo">
    <button type="submit">Update</button>
</form>
</body>
</html>
