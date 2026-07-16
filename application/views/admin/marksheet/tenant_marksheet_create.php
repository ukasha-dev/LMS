<!DOCTYPE html>
<html>
<head><title>Tenant Marksheet Create</title></head>
<body>
<?php if ($created): ?>
<p>Marksheet created with id <?php echo (int) $id; ?>.</p>
<?php else: ?>
<h1>Create Marksheet</h1>
<form method="post" enctype="multipart/form-data">
    <input type="text" name="template" placeholder="Template">
    <input type="text" name="heading" placeholder="Heading">
    <input type="text" name="title" placeholder="Title">
    <input type="text" name="exam_name" placeholder="Exam Name">
    <input type="text" name="school_name" placeholder="School Name">
    <input type="text" name="exam_center" placeholder="Exam Center">
    <input type="text" name="date" placeholder="Date">
    <input type="text" name="content" placeholder="Content">
    <input type="text" name="content_footer" placeholder="Content Footer">
    <input type="file" name="left_logo">
    <input type="file" name="right_logo">
    <input type="file" name="left_sign">
    <input type="file" name="middle_sign">
    <input type="file" name="right_sign">
    <input type="file" name="background_img">
    <input type="file" name="header_image">
    <button type="submit">Create</button>
</form>
<?php endif; ?>
</body>
</html>
