<!DOCTYPE html>
<html>
<head><title>Tenant Admitcard Edit</title></head>
<body>
<?php if ($updated): ?>
<p>Admitcard <?php echo (int) $admitcard['id']; ?> updated.</p>
<?php endif; ?>
<h1>Edit Admitcard #<?php echo (int) $admitcard['id']; ?></h1>
<form method="post" enctype="multipart/form-data">
    <input type="text" name="template" value="<?php echo htmlspecialchars((string) $admitcard['template']); ?>">
    <input type="text" name="heading" value="<?php echo htmlspecialchars((string) $admitcard['heading']); ?>">
    <input type="text" name="title" value="<?php echo htmlspecialchars((string) $admitcard['title']); ?>">
    <input type="file" name="left_logo">
    <input type="file" name="right_logo">
    <input type="file" name="sign">
    <input type="file" name="background_img">
    <button type="submit">Update</button>
</form>
</body>
</html>
