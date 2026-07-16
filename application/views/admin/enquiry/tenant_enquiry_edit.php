<!DOCTYPE html>
<html>
<head><title>Tenant Enquiry Edit</title></head>
<body>
<h1>Edit Enquiry</h1>
<form method="post">
    <input type="text" name="name" value="<?php echo htmlspecialchars($enquiry['name']); ?>">
    <input type="text" name="contact" value="<?php echo htmlspecialchars($enquiry['contact']); ?>">
    <textarea name="description"><?php echo htmlspecialchars($enquiry['description']); ?></textarea>
    <button type="submit">Save</button>
</form>
</body>
</html>
