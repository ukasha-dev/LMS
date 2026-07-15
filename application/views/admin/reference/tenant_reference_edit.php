<!DOCTYPE html>
<html>
<head><title>Tenant Reference Edit</title></head>
<body>
<?php if ($updated): ?>
<p>Reference <?php echo (int) $reference['id']; ?> updated.</p>
<?php endif; ?>
<h1>Edit Reference #<?php echo (int) $reference['id']; ?></h1>
<form method="post">
    <input type="text" name="reference" value="<?php echo htmlspecialchars((string) $reference['reference']); ?>">
    <input type="text" name="description" value="<?php echo htmlspecialchars((string) $reference['description']); ?>">
    <button type="submit">Update</button>
</form>
</body>
</html>
