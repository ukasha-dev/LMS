<!DOCTYPE html>
<html>
<head><title>Tenant Class Edit</title></head>
<body>
<h1>Edit Class</h1>
<form method="post">
    <input type="text" name="class" value="<?php echo htmlspecialchars($class['class']); ?>">
    <button type="submit">Save</button>
</form>
</body>
</html>
