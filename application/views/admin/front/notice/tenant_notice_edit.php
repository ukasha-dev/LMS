<!DOCTYPE html>
<html>
<head><title>Tenant Notice Edit</title></head>
<body>
<h1>Edit Notice</h1>
<form method="post">
    <input type="text" name="title" value="<?php echo htmlspecialchars($notice['title']); ?>">
    <input type="text" name="date" value="<?php echo htmlspecialchars($notice['date']); ?>">
    <textarea name="description"><?php echo htmlspecialchars($notice['description']); ?></textarea>
    <button type="submit">Save</button>
</form>
</body>
</html>
