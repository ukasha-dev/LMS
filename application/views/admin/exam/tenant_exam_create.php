<!DOCTYPE html>
<html>
<head><title>Tenant Exam Create</title></head>
<body>
<?php if ($created): ?>
<p>Exam created with id <?php echo (int) $id; ?>.</p>
<?php else: ?>
<h1>Create Exam</h1>
<form method="post">
    <input type="text" name="name" placeholder="Name">
    <input type="text" name="note" placeholder="Note">
    <input type="text" name="session_id" placeholder="Session Id">
    <button type="submit">Create</button>
</form>
<?php endif; ?>
</body>
</html>
