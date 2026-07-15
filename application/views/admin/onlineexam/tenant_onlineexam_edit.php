<!DOCTYPE html>
<html>
<head><title>Tenant Onlineexam Edit</title></head>
<body>
<?php if ($updated): ?>
<p>Online exam <?php echo (int) $exam['id']; ?> updated.</p>
<?php endif; ?>
<h1>Edit Online Exam #<?php echo (int) $exam['id']; ?></h1>
<form method="post">
    <input type="text" name="exam" value="<?php echo htmlspecialchars((string) $exam['exam']); ?>">
    <input type="text" name="attempt" value="<?php echo htmlspecialchars((string) $exam['attempt']); ?>">
    <input type="text" name="exam_from" value="<?php echo htmlspecialchars((string) $exam['exam_from']); ?>">
    <input type="text" name="exam_to" value="<?php echo htmlspecialchars((string) $exam['exam_to']); ?>">
    <input type="text" name="duration" value="<?php echo htmlspecialchars((string) $exam['duration']); ?>">
    <input type="text" name="description" value="<?php echo htmlspecialchars((string) $exam['description']); ?>">
    <input type="text" name="passing_percentage" value="<?php echo htmlspecialchars((string) $exam['passing_percentage']); ?>">
    <input type="text" name="session_id" value="<?php echo htmlspecialchars((string) $exam['session_id']); ?>">
    <button type="submit">Update</button>
</form>
</body>
</html>
