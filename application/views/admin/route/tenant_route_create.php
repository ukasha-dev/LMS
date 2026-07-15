<!DOCTYPE html>
<html>
<head><title>Tenant Route Create</title></head>
<body>
<?php if ($created): ?>
<p>Route created with id <?php echo (int) $id; ?>.</p>
<?php else: ?>
<h1>Create Route</h1>
<form method="post">
    <input type="text" name="route_title" placeholder="Route Title">
    <input type="text" name="no_of_vehicle" placeholder="No. of Vehicle">
    <button type="submit">Create</button>
</form>
<?php endif; ?>
</body>
</html>
