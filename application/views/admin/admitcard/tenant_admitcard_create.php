<!DOCTYPE html>
<html>
<head><title>Tenant Admitcard Create</title></head>
<body>
<?php if ($created): ?>
<p>Admitcard created with id <?php echo (int) $id; ?>.</p>
<p>left_logo: <?php echo htmlspecialchars((string) ($images['left_logo'] ?? '')); ?></p>
<p>right_logo: <?php echo htmlspecialchars((string) ($images['right_logo'] ?? '')); ?></p>
<p>sign: <?php echo htmlspecialchars((string) ($images['sign'] ?? '')); ?></p>
<p>background_img: <?php echo htmlspecialchars((string) ($images['background_img'] ?? '')); ?></p>
<?php else: ?>
<h1>Create Admitcard</h1>
<form method="post" enctype="multipart/form-data">
    <input type="text" name="template" placeholder="Template">
    <input type="text" name="heading" placeholder="Heading">
    <input type="text" name="title" placeholder="Title">
    <input type="text" name="exam_name" placeholder="Exam Name">
    <input type="text" name="school_name" placeholder="School Name">
    <input type="text" name="exam_center" placeholder="Exam Center">
    <input type="text" name="content_footer" placeholder="Content Footer">
    <input type="file" name="left_logo">
    <input type="file" name="right_logo">
    <input type="file" name="sign">
    <input type="file" name="background_img">
    <button type="submit">Create</button>
</form>
<?php endif; ?>
</body>
</html>
