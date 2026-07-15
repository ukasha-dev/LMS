<!DOCTYPE html>
<html>
<head><title>Tenant Generalcall Edit</title></head>
<body>
<?php if ($updated): ?>
<p>General call <?php echo (int) $generalcall['id']; ?> updated.</p>
<?php endif; ?>
<h1>Edit General Call #<?php echo (int) $generalcall['id']; ?></h1>
<form method="post">
    <input type="text" name="name" value="<?php echo htmlspecialchars((string) $generalcall['name']); ?>">
    <input type="text" name="contact" value="<?php echo htmlspecialchars((string) $generalcall['contact']); ?>">
    <input type="text" name="date" value="<?php echo htmlspecialchars((string) $generalcall['date']); ?>">
    <input type="text" name="description" value="<?php echo htmlspecialchars((string) $generalcall['description']); ?>">
    <input type="text" name="follow_up_date" value="<?php echo htmlspecialchars((string) $generalcall['follow_up_date']); ?>">
    <input type="text" name="call_duration" value="<?php echo htmlspecialchars((string) $generalcall['call_duration']); ?>">
    <input type="text" name="note" value="<?php echo htmlspecialchars((string) $generalcall['note']); ?>">
    <input type="text" name="call_type" value="<?php echo htmlspecialchars((string) $generalcall['call_type']); ?>">
    <button type="submit">Update</button>
</form>
</body>
</html>
