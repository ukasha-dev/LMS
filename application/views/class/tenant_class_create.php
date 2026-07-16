<!DOCTYPE html>
<html>
<head><title>Tenant Class Create</title></head>
<body>
<?php if ($created): ?>
<p>Class created with id <?php echo (int) $id; ?>.</p>
<?php else: ?>
<h1>Create Class</h1>
<form method="post">
    <input type="text" name="class" placeholder="Class">
    <button type="submit">Create</button>
</form>
<?php endif; ?>
</body>
</html>
