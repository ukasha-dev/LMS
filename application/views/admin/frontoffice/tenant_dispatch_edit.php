<!DOCTYPE html>
<html>
<head><title>Tenant Dispatch Edit</title></head>
<body>
<?php if ($updated): ?>
<p>Dispatch <?php echo (int) $dispatch['id']; ?> updated.</p>
<?php endif; ?>
<h1>Edit Dispatch #<?php echo (int) $dispatch['id']; ?></h1>
<p>image: <?php echo htmlspecialchars((string) ($dispatch['image'] ?? '')); ?></p>
<form method="post" enctype="multipart/form-data">
    <input type="text" name="ref_no" value="<?php echo htmlspecialchars((string) $dispatch['reference_no']); ?>">
    <input type="text" name="to_title" value="<?php echo htmlspecialchars((string) $dispatch['to_title']); ?>">
    <input type="text" name="address" value="<?php echo htmlspecialchars((string) $dispatch['address']); ?>">
    <input type="text" name="note" value="<?php echo htmlspecialchars((string) $dispatch['note']); ?>">
    <input type="text" name="from" value="<?php echo htmlspecialchars((string) $dispatch['from_title']); ?>">
    <input type="text" name="date" value="<?php echo htmlspecialchars((string) $dispatch['date']); ?>">
    <input type="file" name="photo">
    <button type="submit">Update</button>
</form>
</body>
</html>
