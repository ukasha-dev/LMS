<!DOCTYPE html>
<html>
<head><title>Tenant Hostelroom Create</title></head>
<body>
<?php if ($created): ?>
<p>Hostel room created with id <?php echo (int) $id; ?>.</p>
<?php else: ?>
<h1>Create Hostel Room</h1>
<form method="post">
    <input type="text" name="hostel_id" placeholder="Hostel Id">
    <input type="text" name="room_type_id" placeholder="Room Type Id">
    <input type="text" name="room_no" placeholder="Room No">
    <input type="text" name="no_of_bed" placeholder="No Of Bed">
    <input type="text" name="cost_per_bed" placeholder="Cost Per Bed">
    <input type="text" name="description" placeholder="Description">
    <button type="submit">Create</button>
</form>
<?php endif; ?>
</body>
</html>
