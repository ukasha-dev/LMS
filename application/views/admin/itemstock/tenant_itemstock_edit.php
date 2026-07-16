<!DOCTYPE html>
<html>
<head><title>Tenant Item Stock Edit</title></head>
<body>
<h1>Edit Item Stock</h1>
<p>Description: <?php echo htmlspecialchars((string) $stock['description']); ?></p>
<form method="post">
    <input type="text" name="quantity" value="<?php echo (int) abs($stock['quantity']); ?>">
    <input type="text" name="purchase_price" value="<?php echo htmlspecialchars((string) $stock['purchase_price']); ?>">
    <textarea name="description"><?php echo htmlspecialchars((string) $stock['description']); ?></textarea>
    <button type="submit">Save</button>
</form>
</body>
</html>
