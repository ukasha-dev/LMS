<!DOCTYPE html>
<html>
<head><title>Tenant Grade Create</title></head>
<body>
<?php if ($created): ?>
<p>Grade created with id <?php echo (int) $id; ?>.</p>
<?php else: ?>
<h1>Create Grade</h1>
<form method="post">
    <input type="text" name="exam_type" placeholder="Exam Type">
    <input type="text" name="name" placeholder="Grade Name">
    <input type="text" name="mark_from" placeholder="Mark From">
    <input type="text" name="mark_upto" placeholder="Mark Upto">
    <input type="text" name="grade_point" placeholder="Grade Point">
    <input type="text" name="description" placeholder="Description">
    <button type="submit">Create</button>
</form>
<?php endif; ?>
</body>
</html>
