<!DOCTYPE html>
<html>
<head><title>Tenant Session Create</title></head>
<body>
<?php if ($created): ?>
<p>Session created with id <?php echo (int) $id; ?>.</p>
<?php else: ?>
<h1>Create Session</h1>
<form method="post">
    <input type="text" name="session" placeholder="Session">
    <button type="submit">Create</button>
</form>
<?php endif; ?>
</body>
</html>
