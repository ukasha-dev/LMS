<!DOCTYPE html>
<html>
<head><title>Tenant Certificate Create</title></head>
<body>
<?php if ($created): ?>
<p>Certificate created with id <?php echo (int) $id; ?>.</p>
<?php else: ?>
<h1>Create Certificate</h1>
<form method="post" enctype="multipart/form-data">
    <input type="text" name="certificate_name" placeholder="Certificate Name">
    <input type="text" name="certificate_text" placeholder="Certificate Text">
    <input type="text" name="left_header" placeholder="Left Header">
    <input type="text" name="center_header" placeholder="Center Header">
    <input type="text" name="right_header" placeholder="Right Header">
    <input type="text" name="left_footer" placeholder="Left Footer">
    <input type="text" name="right_footer" placeholder="Right Footer">
    <input type="text" name="center_footer" placeholder="Center Footer">
    <input type="file" name="background_image">
    <button type="submit">Create</button>
</form>
<?php endif; ?>
</body>
</html>
