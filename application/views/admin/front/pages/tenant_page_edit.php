<!DOCTYPE html>
<html>
<head><title>Tenant Page Edit</title></head>
<body>
<h1>Edit Page</h1>
<form method="post">
    <input type="text" name="title" value="<?php echo htmlspecialchars($page['title']); ?>">
    <textarea name="description"><?php echo htmlspecialchars($page['description']); ?></textarea>
    <button type="submit">Save</button>
</form>
</body>
</html>
