<!DOCTYPE html>
<html>
<head><title>Tenant Item Create</title></head>
<body>
<?php if ($created): ?>
<p>Item created with id <?php echo (int) $id; ?>.</p>
<?php else: ?>
<h1>Create Item</h1>
<form method="post">
    <input type="text" name="name" placeholder="Name">
    <input type="text" name="unit" placeholder="Unit">
    <input type="text" name="description" placeholder="Description">
    <input type="text" name="item_category_id" placeholder="Item Category Id">
    <button type="submit">Create</button>
</form>
<?php endif; ?>
</body>
</html>
