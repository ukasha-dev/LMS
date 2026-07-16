<!DOCTYPE html>
<html>
<head><title>Tenant Item Stock Create</title></head>
<body>
<?php if ($created): ?>
<p>Item stock created with id <?php echo (int) $id; ?>.</p>
<?php else: ?>
<h1>Create Item Stock</h1>
<form method="post" enctype="multipart/form-data">
    <input type="text" name="item_id" placeholder="Item Id">
    <input type="text" name="supplier_id" placeholder="Supplier Id">
    <input type="text" name="store_id" placeholder="Store Id (optional)">
    <input type="text" name="symbol" placeholder="+/-">
    <input type="text" name="quantity" placeholder="Quantity">
    <input type="text" name="purchase_price" placeholder="Purchase Price">
    <input type="text" name="date" placeholder="Date">
    <textarea name="description" placeholder="Description"></textarea>
    <input type="file" name="item_photo">
    <button type="submit">Create</button>
</form>
<?php endif; ?>
</body>
</html>
