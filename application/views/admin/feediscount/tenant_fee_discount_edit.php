<!DOCTYPE html>
<html>
<head><title>Tenant Fee Discount Edit</title></head>
<body>
<?php if ($updated): ?>
<p>Fee discount <?php echo (int) $feeDiscount['id']; ?> updated.</p>
<?php endif; ?>
<h1>Edit Fee Discount #<?php echo (int) $feeDiscount['id']; ?></h1>
<form method="post">
    <input type="text" name="name" value="<?php echo htmlspecialchars((string) $feeDiscount['name']); ?>">
    <input type="text" name="code" value="<?php echo htmlspecialchars((string) $feeDiscount['code']); ?>">
    <input type="text" name="type" value="<?php echo htmlspecialchars((string) $feeDiscount['type']); ?>">
    <input type="text" name="amount" value="<?php echo htmlspecialchars((string) $feeDiscount['amount']); ?>">
    <input type="text" name="percentage" value="<?php echo htmlspecialchars((string) $feeDiscount['percentage']); ?>">
    <input type="text" name="discount_limit" value="<?php echo htmlspecialchars((string) $feeDiscount['discount_limit']); ?>">
    <input type="text" name="expire_date" value="<?php echo htmlspecialchars((string) $feeDiscount['expire_date']); ?>">
    <input type="text" name="description" value="<?php echo htmlspecialchars((string) $feeDiscount['description']); ?>">
    <button type="submit">Update</button>
</form>
</body>
</html>
