<!DOCTYPE html>
<html>
<head><title>Tenant Gallery Edit</title></head>
<body>
<h1>Edit Gallery</h1>
<form method="post">
    <input type="text" name="title" value="<?php echo htmlspecialchars($gallery['title']); ?>">
    <textarea name="description"><?php echo htmlspecialchars($gallery['description']); ?></textarea>
    <button type="submit">Save</button>
</form>
</body>
</html>
