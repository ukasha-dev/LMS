<!DOCTYPE html>
<html>
<head><title>Tenant Roomtype Create</title></head>
<body>
<?php if ($created): ?>
<p>Room type created with id <?php echo (int) $id; ?>.</p>
<?php else: ?>
<h1>Create Room Type</h1>
<form method="post">
    <input type="text" name="room_type" placeholder="Room Type">
    <input type="text" name="description" placeholder="Description">
    <button type="submit">Create</button>
</form>
<?php endif; ?>
</body>
</html>
