<!DOCTYPE html>
<html>
<head><title>Tenant Onlineexam Create</title></head>
<body>
<?php if ($created): ?>
<p>Online exam created with id <?php echo (int) $id; ?>.</p>
<?php else: ?>
<h1>Create Online Exam</h1>
<form method="post">
    <input type="text" name="exam" placeholder="Exam Title">
    <input type="text" name="attempt" placeholder="Attempt">
    <input type="text" name="exam_from" placeholder="Exam From (YYYY-MM-DD HH:MM:SS)">
    <input type="text" name="exam_to" placeholder="Exam To (YYYY-MM-DD HH:MM:SS)">
    <input type="text" name="duration" placeholder="Duration (HH:MM:SS)">
    <input type="text" name="description" placeholder="Description">
    <input type="text" name="passing_percentage" placeholder="Passing Percentage">
    <input type="text" name="session_id" placeholder="Session Id">
    <button type="submit">Create</button>
</form>
<?php endif; ?>
</body>
</html>
