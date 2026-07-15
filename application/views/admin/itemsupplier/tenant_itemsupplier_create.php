<!DOCTYPE html>
<html>
<head><title>Tenant Itemsupplier Create</title></head>
<body>
<?php if ($created): ?>
<p>Item supplier created with id <?php echo (int) $id; ?>.</p>
<?php else: ?>
<h1>Create Item Supplier</h1>
<form method="post">
    <input type="text" name="name" placeholder="Name">
    <input type="text" name="phone" placeholder="Phone">
    <input type="text" name="email" placeholder="Email">
    <input type="text" name="address" placeholder="Address">
    <input type="text" name="contact_person_name" placeholder="Contact Person Name">
    <input type="text" name="contact_person_phone" placeholder="Contact Person Phone">
    <input type="text" name="contact_person_email" placeholder="Contact Person Email">
    <input type="text" name="description" placeholder="Description">
    <button type="submit">Create</button>
</form>
<?php endif; ?>
</body>
</html>
