<!DOCTYPE html>
<html>
<head><title>Tenant Itemsupplier Edit</title></head>
<body>
<?php if ($updated): ?>
<p>Item supplier <?php echo (int) $itemsupplier['id']; ?> updated.</p>
<?php endif; ?>
<h1>Edit Item Supplier #<?php echo (int) $itemsupplier['id']; ?></h1>
<form method="post">
    <input type="text" name="name" value="<?php echo htmlspecialchars((string) $itemsupplier['item_supplier']); ?>">
    <input type="text" name="phone" value="<?php echo htmlspecialchars((string) $itemsupplier['phone']); ?>">
    <input type="text" name="email" value="<?php echo htmlspecialchars((string) $itemsupplier['email']); ?>">
    <input type="text" name="address" value="<?php echo htmlspecialchars((string) $itemsupplier['address']); ?>">
    <input type="text" name="contact_person_name" value="<?php echo htmlspecialchars((string) $itemsupplier['contact_person_name']); ?>">
    <input type="text" name="contact_person_phone" value="<?php echo htmlspecialchars((string) $itemsupplier['contact_person_phone']); ?>">
    <input type="text" name="contact_person_email" value="<?php echo htmlspecialchars((string) $itemsupplier['contact_person_email']); ?>">
    <input type="text" name="description" value="<?php echo htmlspecialchars((string) $itemsupplier['description']); ?>">
    <button type="submit">Update</button>
</form>
</body>
</html>
