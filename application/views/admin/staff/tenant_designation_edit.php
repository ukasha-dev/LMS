<!DOCTYPE html>
<html>
<head><title>Tenant Designation Edit</title></head>
<body>
<?php if ($updated): ?>
<p>Designation <?php echo (int) $designation['id']; ?> updated.</p>
<?php endif; ?>
<h1>Edit Designation #<?php echo (int) $designation['id']; ?></h1>
<form method="post">
    <input type="text" name="designation" value="<?php echo htmlspecialchars((string) $designation['designation']); ?>">
    <button type="submit">Update</button>
</form>
</body>
</html>
