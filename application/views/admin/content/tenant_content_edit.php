<!DOCTYPE html>
<html>
<head><title>Tenant Content Edit</title></head>
<body>
<h1>Edit Content</h1>
<p>Title: <?php echo htmlspecialchars($content['title']); ?></p>
<form method="post">
    <input type="text" name="content_title" value="<?php echo htmlspecialchars($content['title']); ?>">
    <textarea name="note"><?php echo htmlspecialchars((string) $content['note']); ?></textarea>
    <button type="submit">Save</button>
</form>
</body>
</html>
