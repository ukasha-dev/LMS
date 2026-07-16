<!DOCTYPE html>
<html>
<head><title>Tenant Content Create</title></head>
<body>
<?php if ($created): ?>
<p>Content created with id <?php echo (int) $id; ?>.</p>
<?php else: ?>
<h1>Create Content</h1>
<form method="post" enctype="multipart/form-data">
    <input type="text" name="content_title" placeholder="Title">
    <input type="text" name="content_type" placeholder="Type">
    <textarea name="note" placeholder="Note"></textarea>
    <input type="text" name="class_id" placeholder="Class Id">
    <input type="text" name="cls_sec_id" placeholder="Class Section Id">
    <input type="file" name="file">
    <button type="submit">Create</button>
</form>
<?php endif; ?>
</body>
</html>
