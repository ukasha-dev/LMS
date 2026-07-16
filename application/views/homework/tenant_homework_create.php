<!DOCTYPE html>
<html>
<head><title>Tenant Homework Create</title></head>
<body>
<?php if ($created): ?>
<p>Homework created with id <?php echo (int) $id; ?>.</p>
<?php else: ?>
<h1>Create Homework</h1>
<form method="post" enctype="multipart/form-data">
    <input type="text" name="class_id" placeholder="Class Id">
    <input type="text" name="section_id" placeholder="Section Id">
    <input type="text" name="subject_group_subject_id" placeholder="Subject Id">
    <input type="text" name="session_id" placeholder="Session Id">
    <input type="text" name="homework_date" placeholder="Homework Date">
    <input type="text" name="submit_date" placeholder="Submit Date">
    <textarea name="description" placeholder="Description"></textarea>
    <input type="file" name="userfile">
    <button type="submit">Create</button>
</form>
<?php endif; ?>
</body>
</html>
