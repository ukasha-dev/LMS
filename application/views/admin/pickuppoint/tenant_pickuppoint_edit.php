<!DOCTYPE html>
<html>
<head><title>Tenant Pickuppoint Edit</title></head>
<body>
<?php if ($updated): ?>
<p>Pickup point <?php echo (int) $pickuppoint['id']; ?> updated.</p>
<?php endif; ?>
<h1>Edit Pickup Point #<?php echo (int) $pickuppoint['id']; ?></h1>
<form method="post">
    <input type="text" name="name" value="<?php echo htmlspecialchars((string) $pickuppoint['name']); ?>">
    <input type="text" name="latitude" value="<?php echo htmlspecialchars((string) $pickuppoint['latitude']); ?>">
    <input type="text" name="longitude" value="<?php echo htmlspecialchars((string) $pickuppoint['longitude']); ?>">
    <button type="submit">Update</button>
</form>
</body>
</html>
