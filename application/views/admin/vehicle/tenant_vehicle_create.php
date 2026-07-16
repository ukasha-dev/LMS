<!DOCTYPE html>
<html>
<head><title>Tenant Vehicle Create</title></head>
<body>
<?php if ($created): ?>
<p>Vehicle created with id <?php echo (int) $id; ?>.</p>
<?php if (!empty($image)): ?>
<p>image: <?php echo htmlspecialchars((string) $image); ?></p>
<?php endif; ?>
<?php else: ?>
<h1>Create Vehicle</h1>
<form method="post" enctype="multipart/form-data">
    <input type="text" name="vehicle_no" placeholder="Vehicle No">
    <input type="text" name="vehicle_model" placeholder="Vehicle Model">
    <input type="text" name="driver_name" placeholder="Driver Name">
    <input type="text" name="driver_licence" placeholder="Driver Licence">
    <input type="text" name="driver_contact" placeholder="Driver Contact">
    <input type="text" name="note" placeholder="Note">
    <input type="text" name="registration_number" placeholder="Registration Number">
    <input type="text" name="chasis_number" placeholder="Chasis Number">
    <input type="text" name="max_seating_capacity" placeholder="Max Seating Capacity">
    <input type="text" name="manufacture_year" placeholder="Manufacture Year">
    <input type="file" name="photo">
    <button type="submit">Create</button>
</form>
<?php endif; ?>
</body>
</html>
