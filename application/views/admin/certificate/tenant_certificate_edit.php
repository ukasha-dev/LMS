<!DOCTYPE html>
<html>
<head><title>Tenant Certificate Edit</title></head>
<body>
<?php if ($updated): ?>
<p>Certificate <?php echo (int) $certificate['id']; ?> updated.</p>
<?php endif; ?>
<h1>Edit Certificate #<?php echo (int) $certificate['id']; ?></h1>
<p>image: <?php echo htmlspecialchars((string) ($certificate['background_image'] ?? '')); ?></p>
<form method="post" enctype="multipart/form-data">
    <input type="text" name="certificate_name" value="<?php echo htmlspecialchars((string) $certificate['certificate_name']); ?>">
    <input type="text" name="certificate_text" value="<?php echo htmlspecialchars((string) $certificate['certificate_text']); ?>">
    <input type="file" name="background_image">
    <button type="submit">Update</button>
</form>
</body>
</html>
