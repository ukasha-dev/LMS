<!DOCTYPE html>
<html>
<head><title>Tenant Grade Edit</title></head>
<body>
<?php if ($updated): ?>
<p>Grade <?php echo (int) $grade['id']; ?> updated.</p>
<?php endif; ?>
<h1>Edit Grade #<?php echo (int) $grade['id']; ?></h1>
<form method="post">
    <input type="text" name="exam_type" value="<?php echo htmlspecialchars((string) $grade['exam_type']); ?>">
    <input type="text" name="name" value="<?php echo htmlspecialchars((string) $grade['name']); ?>">
    <input type="text" name="mark_from" value="<?php echo htmlspecialchars((string) $grade['mark_from']); ?>">
    <input type="text" name="mark_upto" value="<?php echo htmlspecialchars((string) $grade['mark_upto']); ?>">
    <input type="text" name="grade_point" value="<?php echo htmlspecialchars((string) $grade['point']); ?>">
    <input type="text" name="description" value="<?php echo htmlspecialchars((string) $grade['description']); ?>">
    <button type="submit">Update</button>
</form>
</body>
</html>
