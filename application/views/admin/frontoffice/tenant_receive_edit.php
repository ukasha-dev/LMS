<!DOCTYPE html>
<html>
<head><title>Tenant Receive Edit</title></head>
<body>
<?php if ($updated): ?>
<p>Receive <?php echo (int) $receive['id']; ?> updated.</p>
<?php endif; ?>
<h1>Edit Receive #<?php echo (int) $receive['id']; ?></h1>
<p>image: <?php echo htmlspecialchars((string) ($receive['image'] ?? '')); ?></p>
<form method="post" enctype="multipart/form-data">
    <input type="text" name="ref_no" value="<?php echo htmlspecialchars((string) $receive['reference_no']); ?>">
    <input type="text" name="to_title" value="<?php echo htmlspecialchars((string) $receive['to_title']); ?>">
    <input type="text" name="address" value="<?php echo htmlspecialchars((string) $receive['address']); ?>">
    <input type="text" name="note" value="<?php echo htmlspecialchars((string) $receive['note']); ?>">
    <input type="text" name="from_title" value="<?php echo htmlspecialchars((string) $receive['from_title']); ?>">
    <input type="text" name="date" value="<?php echo htmlspecialchars((string) $receive['date']); ?>">
    <input type="file" name="photo">
    <button type="submit">Update</button>
</form>
</body>
</html>
