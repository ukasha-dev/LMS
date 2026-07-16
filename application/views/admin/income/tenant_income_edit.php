<!DOCTYPE html>
<html>
<head><title>Tenant Income Edit</title></head>
<body>
<?php if ($updated): ?>
<p>Income <?php echo (int) $income['id']; ?> updated.</p>
<?php endif; ?>
<h1>Edit Income #<?php echo (int) $income['id']; ?></h1>
<p>documents: <?php echo htmlspecialchars((string) ($income['documents'] ?? '')); ?></p>
<form method="post" enctype="multipart/form-data">
    <input type="text" name="inc_head_id" value="<?php echo htmlspecialchars((string) $income['income_head_id']); ?>">
    <input type="text" name="name" value="<?php echo htmlspecialchars((string) $income['name']); ?>">
    <input type="text" name="date" value="<?php echo htmlspecialchars((string) $income['date']); ?>">
    <input type="text" name="amount" value="<?php echo htmlspecialchars((string) $income['amount']); ?>">
    <input type="text" name="invoice_no" value="<?php echo htmlspecialchars((string) $income['invoice_no']); ?>">
    <input type="text" name="description" value="<?php echo htmlspecialchars((string) $income['note']); ?>">
    <input type="file" name="documents">
    <button type="submit">Update</button>
</form>
</body>
</html>
