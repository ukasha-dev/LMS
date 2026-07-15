<!DOCTYPE html>
<html>
<head><title>Tenant Itemcategory Create</title></head>
<body>
<?php if ($created): ?>
<p>Item category created with id <?php echo (int) $id; ?>.</p>
<?php else: ?>
<h1>Create Item Category</h1>
<form method="post">
    <input type="text" name="item_category" placeholder="Item Category">
    <input type="text" name="description" placeholder="Description">
    <button type="submit">Create</button>
</form>
<?php endif; ?>
</body>
</html>
