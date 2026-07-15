<!DOCTYPE html>
<html>
<head><title>Tenant Section Edit</title></head>
<body>
<?php if ($updated): ?>
<p>Section <?php echo (int) $section['id']; ?> updated.</p>
<?php endif; ?>
<h1>Edit Section #<?php echo (int) $section['id']; ?></h1>
<form method="post">
    <input type="text" name="section" value="<?php echo htmlspecialchars((string) $section['section']); ?>">
    <button type="submit">Update</button>
</form>
</body>
</html>
