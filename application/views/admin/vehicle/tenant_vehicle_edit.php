<!DOCTYPE html>
<html>
<head><title>Tenant Vehicle Edit</title></head>
<body>
<?php if ($updated): ?>
<p>Vehicle <?php echo (int) $vehicle['id']; ?> updated.</p>
<?php endif; ?>
<h1>Edit Vehicle #<?php echo (int) $vehicle['id']; ?></h1>
<p>image: <?php echo htmlspecialchars((string) ($vehicle['vehicle_photo'] ?? '')); ?></p>
<form method="post" enctype="multipart/form-data">
    <input type="text" name="vehicle_no" value="<?php echo htmlspecialchars((string) $vehicle['vehicle_no']); ?>">
    <input type="text" name="vehicle_model" value="<?php echo htmlspecialchars((string) $vehicle['vehicle_model']); ?>">
    <input type="text" name="driver_name" value="<?php echo htmlspecialchars((string) $vehicle['driver_name']); ?>">
    <input type="text" name="driver_licence" value="<?php echo htmlspecialchars((string) $vehicle['driver_licence']); ?>">
    <input type="text" name="driver_contact" value="<?php echo htmlspecialchars((string) $vehicle['driver_contact']); ?>">
    <input type="text" name="note" value="<?php echo htmlspecialchars((string) $vehicle['note']); ?>">
    <input type="text" name="registration_number" value="<?php echo htmlspecialchars((string) $vehicle['registration_number']); ?>">
    <input type="text" name="chasis_number" value="<?php echo htmlspecialchars((string) $vehicle['chasis_number']); ?>">
    <input type="text" name="max_seating_capacity" value="<?php echo htmlspecialchars((string) $vehicle['max_seating_capacity']); ?>">
    <input type="text" name="manufacture_year" value="<?php echo htmlspecialchars((string) $vehicle['manufacture_year']); ?>">
    <input type="file" name="photo">
    <button type="submit">Update</button>
</form>
</body>
</html>
