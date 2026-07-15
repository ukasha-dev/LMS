<!DOCTYPE html>
<html>
<head><title>Tenant Visitors Purpose Create</title></head>
<body>
<?php if ($created): ?>
<p>Visitors purpose created with id <?php echo (int) $id; ?>.</p>
<?php else: ?>
<h1>Create Visitors Purpose</h1>
<form method="post">
    <input type="text" name="visitors_purpose" placeholder="Visitors Purpose">
    <input type="text" name="description" placeholder="Description">
    <button type="submit">Create</button>
</form>
<?php endif; ?>
</body>
</html>
