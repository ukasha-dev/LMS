<!DOCTYPE html>
<html>
<head><title>Tenant Pickuppoint Create</title></head>
<body>
<?php if ($created): ?>
<p>Pickup point created with id <?php echo (int) $id; ?>.</p>
<?php else: ?>
<h1>Create Pickup Point</h1>
<form method="post">
    <input type="text" name="name" placeholder="Name">
    <input type="text" name="latitude" placeholder="Latitude">
    <input type="text" name="longitude" placeholder="Longitude">
    <button type="submit">Create</button>
</form>
<?php endif; ?>
</body>
</html>
