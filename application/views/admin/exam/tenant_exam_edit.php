<!DOCTYPE html>
<html>
<head><title>Tenant Exam Edit</title></head>
<body>
<?php if ($updated): ?>
<p>Exam <?php echo (int) $exam['id']; ?> updated.</p>
<?php endif; ?>
<h1>Edit Exam #<?php echo (int) $exam['id']; ?></h1>
<form method="post">
    <input type="text" name="name" value="<?php echo htmlspecialchars((string) $exam['name']); ?>">
    <input type="text" name="note" value="<?php echo htmlspecialchars((string) $exam['note']); ?>">
    <input type="text" name="session_id" value="<?php echo (int) $exam['sesion_id']; ?>">
    <button type="submit">Update</button>
</form>
</body>
</html>
