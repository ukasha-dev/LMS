<!DOCTYPE html>
<html>
<head><title>Tenant Marksheet Edit</title></head>
<body>
<?php if ($updated): ?>
<p>Marksheet <?php echo (int) $marksheet['id']; ?> updated.</p>
<?php endif; ?>
<h1>Edit Marksheet #<?php echo (int) $marksheet['id']; ?></h1>
<form method="post" enctype="multipart/form-data">
    <input type="text" name="template" value="<?php echo htmlspecialchars((string) $marksheet['template']); ?>">
    <input type="text" name="heading" value="<?php echo htmlspecialchars((string) $marksheet['heading']); ?>">
    <input type="text" name="title" value="<?php echo htmlspecialchars((string) $marksheet['title']); ?>">
    <input type="file" name="left_logo">
    <input type="file" name="right_logo">
    <input type="file" name="left_sign">
    <input type="file" name="middle_sign">
    <input type="file" name="right_sign">
    <input type="file" name="background_img">
    <input type="file" name="header_image">
    <button type="submit">Update</button>
</form>
</body>
</html>
