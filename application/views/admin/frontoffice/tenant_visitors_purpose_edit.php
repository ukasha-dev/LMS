<!DOCTYPE html>
<html>
<head><title>Tenant Visitors Purpose Edit</title></head>
<body>
<?php if ($updated): ?>
<p>Visitors purpose <?php echo (int) $visitorsPurpose['id']; ?> updated.</p>
<?php endif; ?>
<h1>Edit Visitors Purpose #<?php echo (int) $visitorsPurpose['id']; ?></h1>
<form method="post">
    <input type="text" name="visitors_purpose" value="<?php echo htmlspecialchars((string) $visitorsPurpose['visitors_purpose']); ?>">
    <input type="text" name="description" value="<?php echo htmlspecialchars((string) $visitorsPurpose['description']); ?>">
    <button type="submit">Update</button>
</form>
</body>
</html>
