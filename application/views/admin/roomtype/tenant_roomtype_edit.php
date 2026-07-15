<!DOCTYPE html>
<html>
<head><title>Tenant Roomtype Edit</title></head>
<body>
<?php if ($updated): ?>
<p>Room type <?php echo (int) $roomtype['id']; ?> updated.</p>
<?php endif; ?>
<h1>Edit Room Type #<?php echo (int) $roomtype['id']; ?></h1>
<form method="post">
    <input type="text" name="room_type" value="<?php echo htmlspecialchars((string) $roomtype['room_type']); ?>">
    <input type="text" name="description" value="<?php echo htmlspecialchars((string) $roomtype['description']); ?>">
    <button type="submit">Update</button>
</form>
</body>
</html>
