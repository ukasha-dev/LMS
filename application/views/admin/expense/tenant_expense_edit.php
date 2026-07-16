<!DOCTYPE html>
<html>
<head><title>Tenant Expense Edit</title></head>
<body>
<?php if ($updated): ?>
<p>Expense <?php echo (int) $expense['id']; ?> updated.</p>
<?php endif; ?>
<h1>Edit Expense #<?php echo (int) $expense['id']; ?></h1>
<p>documents: <?php echo htmlspecialchars((string) ($expense['documents'] ?? '')); ?></p>
<form method="post" enctype="multipart/form-data">
    <input type="text" name="exp_head_id" value="<?php echo htmlspecialchars((string) $expense['exp_head_id']); ?>">
    <input type="text" name="name" value="<?php echo htmlspecialchars((string) $expense['name']); ?>">
    <input type="text" name="date" value="<?php echo htmlspecialchars((string) $expense['date']); ?>">
    <input type="text" name="amount" value="<?php echo htmlspecialchars((string) $expense['amount']); ?>">
    <input type="text" name="invoice_no" value="<?php echo htmlspecialchars((string) $expense['invoice_no']); ?>">
    <input type="text" name="description" value="<?php echo htmlspecialchars((string) $expense['note']); ?>">
    <input type="file" name="documents">
    <button type="submit">Update</button>
</form>
</body>
</html>
