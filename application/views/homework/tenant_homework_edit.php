<!DOCTYPE html>
<html>
<head><title>Tenant Homework Edit</title></head>
<body>
<h1>Edit Homework</h1>
<p>Description: <?php echo htmlspecialchars($homework['description']); ?></p>
<form method="post">
    <input type="text" name="class_id" value="<?php echo (int) $homework['class_id']; ?>">
    <input type="text" name="section_id" value="<?php echo (int) $homework['section_id']; ?>">
    <input type="text" name="homework_date" value="<?php echo htmlspecialchars($homework['homework_date']); ?>">
    <input type="text" name="submit_date" value="<?php echo htmlspecialchars($homework['submit_date']); ?>">
    <textarea name="description"><?php echo htmlspecialchars($homework['description']); ?></textarea>
    <button type="submit">Save</button>
</form>
</body>
</html>
