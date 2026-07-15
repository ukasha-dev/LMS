<!DOCTYPE html>
<html>
<head><title>Tenant Hostel Edit</title></head>
<body>
<?php if ($updated): ?>
<p>Hostel <?php echo (int) $hostel['id']; ?> updated.</p>
<?php endif; ?>
<h1>Edit Hostel #<?php echo (int) $hostel['id']; ?></h1>
<form method="post">
    <input type="text" name="hostel_name" value="<?php echo htmlspecialchars((string) $hostel['hostel_name']); ?>">
    <input type="text" name="type" value="<?php echo htmlspecialchars((string) $hostel['type']); ?>">
    <input type="text" name="address" value="<?php echo htmlspecialchars((string) $hostel['address']); ?>">
    <input type="text" name="intake" value="<?php echo htmlspecialchars((string) $hostel['intake']); ?>">
    <input type="text" name="description" value="<?php echo htmlspecialchars((string) $hostel['description']); ?>">
    <button type="submit">Update</button>
</form>
</body>
</html>
