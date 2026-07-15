<!DOCTYPE html>
<html>
<head><title>Tenant Holiday Type Edit</title></head>
<body>
<?php if ($updated): ?>
<p>Holiday type <?php echo (int) $holidayType['id']; ?> updated.</p>
<?php endif; ?>
<h1>Edit Holiday Type #<?php echo (int) $holidayType['id']; ?></h1>
<form method="post">
    <input type="text" name="type" value="<?php echo htmlspecialchars((string) $holidayType['type']); ?>">
    <button type="submit">Update</button>
</form>
</body>
</html>
