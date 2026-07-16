<!DOCTYPE html>
<html>
<head><title>Tenant Menu Edit</title></head>
<body>
<h1>Edit Menu</h1>
<form method="post">
    <input type="text" name="menu" value="<?php echo htmlspecialchars($menu['menu']); ?>">
    <textarea name="description"><?php echo htmlspecialchars((string) $menu['description']); ?></textarea>
    <button type="submit">Save</button>
</form>
</body>
</html>
