<!DOCTYPE html>
<html>
<head><title>Tenant Media Create</title></head>
<body>
<?php if ($created): ?>
<p>Media created with id <?php echo (int) $id; ?>.</p>
<?php if (!empty($storedPath)): ?>
<p>path: <?php echo htmlspecialchars((string) $storedPath); ?></p>
<?php endif; ?>
<?php else: ?>
<h1>Upload Media</h1>
<form method="post" enctype="multipart/form-data">
    <input type="file" name="media_file">
    <button type="submit">Upload</button>
</form>
<?php endif; ?>
</body>
</html>
