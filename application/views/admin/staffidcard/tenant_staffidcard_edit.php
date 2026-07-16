<!DOCTYPE html>
<html>
<head><title>Tenant Staffidcard Edit</title></head>
<body>
<?php if ($updated): ?>
<p>Staffidcard <?php echo (int) $idcard['id']; ?> updated.</p>
<?php endif; ?>
<h1>Edit Staffidcard #<?php echo (int) $idcard['id']; ?></h1>
<form method="post" enctype="multipart/form-data">
    <input type="text" name="title" value="<?php echo htmlspecialchars((string) $idcard['title']); ?>">
    <input type="text" name="school_name" value="<?php echo htmlspecialchars((string) $idcard['school_name']); ?>">
    <input type="text" name="address" value="<?php echo htmlspecialchars((string) $idcard['school_address']); ?>">
    <input type="file" name="background_image">
    <input type="file" name="logo_img">
    <input type="file" name="sign_image">
    <button type="submit">Update</button>
</form>
</body>
</html>
