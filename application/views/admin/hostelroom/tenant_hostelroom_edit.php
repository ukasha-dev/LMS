<!DOCTYPE html>
<html>
<head><title>Tenant Hostelroom Edit</title></head>
<body>
<?php if ($updated): ?>
<p>Hostel room <?php echo (int) $hostelroom['id']; ?> updated.</p>
<?php endif; ?>
<h1>Edit Hostel Room #<?php echo (int) $hostelroom['id']; ?></h1>
<form method="post">
    <input type="text" name="hostel_id" value="<?php echo (int) $hostelroom['hostel_id']; ?>">
    <input type="text" name="room_type_id" value="<?php echo (int) $hostelroom['room_type_id']; ?>">
    <input type="text" name="room_no" value="<?php echo htmlspecialchars((string) $hostelroom['room_no']); ?>">
    <input type="text" name="no_of_bed" value="<?php echo htmlspecialchars((string) $hostelroom['no_of_bed']); ?>">
    <input type="text" name="cost_per_bed" value="<?php echo htmlspecialchars((string) $hostelroom['cost_per_bed']); ?>">
    <input type="text" name="description" value="<?php echo htmlspecialchars((string) $hostelroom['description']); ?>">
    <button type="submit">Update</button>
</form>
</body>
</html>
