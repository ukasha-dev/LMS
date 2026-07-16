<!DOCTYPE html>
<html>
<head><title>Tenant Syllabus Edit</title></head>
<body>
<h1>Edit Syllabus</h1>
<p>Presentation: <?php echo htmlspecialchars($syllabus['presentation']); ?></p>
<form method="post">
    <input type="text" name="date" value="<?php echo htmlspecialchars($syllabus['date']); ?>">
    <input type="text" name="time_from" value="<?php echo htmlspecialchars($syllabus['time_from']); ?>">
    <input type="text" name="time_to" value="<?php echo htmlspecialchars($syllabus['time_to']); ?>">
    <textarea name="presentation"><?php echo htmlspecialchars($syllabus['presentation']); ?></textarea>
    <button type="submit">Save</button>
</form>
</body>
</html>
