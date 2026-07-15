<!DOCTYPE html>
<html>
<head><title>Tenant Route Edit</title></head>
<body>
<?php if ($updated): ?>
<p>Route <?php echo (int) $route['id']; ?> updated.</p>
<?php endif; ?>
<h1>Edit Route #<?php echo (int) $route['id']; ?></h1>
<form method="post">
    <input type="text" name="route_title" value="<?php echo htmlspecialchars((string) $route['route_title']); ?>">
    <input type="text" name="no_of_vehicle" value="<?php echo htmlspecialchars((string) $route['no_of_vehicle']); ?>">
    <button type="submit">Update</button>
</form>
</body>
</html>
