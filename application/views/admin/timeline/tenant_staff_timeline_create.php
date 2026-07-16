<!DOCTYPE html>
<html>
<head><title>Tenant Staff Timeline Create</title></head>
<body>
<?php if ($created): ?>
<p>Staff timeline created with id <?php echo (int) $id; ?>.</p>
<?php else: ?>
<h1>Create Staff Timeline</h1>
<form method="post" enctype="multipart/form-data">
    <input type="text" name="timeline_title" placeholder="Title">
    <input type="text" name="timeline_date" placeholder="Date">
    <input type="text" name="timeline_desc" placeholder="Description">
    <input type="text" name="staff_id" placeholder="Staff Id">
    <input type="checkbox" name="visible_check" value="1"> Visible
    <input type="file" name="timeline_doc">
    <button type="submit">Create</button>
</form>
<?php endif; ?>
</body>
</html>
