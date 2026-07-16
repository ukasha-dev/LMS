<!DOCTYPE html>
<html>
<head><title>Tenant School Settings Update</title></head>
<body>
<?php if ($updated): ?>
<p>Setting <?php echo htmlspecialchars($field); ?> updated.</p>
<?php else: ?>
<h1>Update School Setting</h1>
<form method="post">
    <input type="text" name="field" placeholder="Field name">
    <input type="text" name="value" placeholder="Value">
    <button type="submit">Save</button>
</form>
<?php endif; ?>
</body>
</html>
