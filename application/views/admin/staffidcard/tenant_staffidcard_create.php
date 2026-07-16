<!DOCTYPE html>
<html>
<head><title>Tenant Staffidcard Create</title></head>
<body>
<?php if ($created): ?>
<p>Staffidcard created with id <?php echo (int) $id; ?>.</p>
<?php else: ?>
<h1>Create Staffidcard</h1>
<form method="post" enctype="multipart/form-data">
    <input type="text" name="title" placeholder="Title">
    <input type="text" name="school_name" placeholder="School Name">
    <input type="text" name="address" placeholder="Address">
    <input type="text" name="header_color" placeholder="Header Color">
    <input type="file" name="background_image">
    <input type="file" name="logo_img">
    <input type="file" name="sign_image">
    <button type="submit">Create</button>
</form>
<?php endif; ?>
</body>
</html>
