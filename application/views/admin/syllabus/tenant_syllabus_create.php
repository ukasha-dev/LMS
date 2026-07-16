<!DOCTYPE html>
<html>
<head><title>Tenant Syllabus Create</title></head>
<body>
<?php if ($created): ?>
<p>Syllabus created with id <?php echo (int) $id; ?>.</p>
<?php else: ?>
<h1>Create Syllabus</h1>
<form method="post" enctype="multipart/form-data">
    <input type="text" name="topic_id" placeholder="Topic Id">
    <input type="text" name="session_id" placeholder="Session Id">
    <input type="text" name="created_for" placeholder="Created For Staff Id">
    <input type="text" name="date" placeholder="Date">
    <input type="text" name="time_from" placeholder="Time From">
    <input type="text" name="time_to" placeholder="Time To">
    <textarea name="presentation" placeholder="Presentation"></textarea>
    <input type="file" name="file">
    <input type="file" name="lacture_video">
    <button type="submit">Create</button>
</form>
<?php endif; ?>
</body>
</html>
