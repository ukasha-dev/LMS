<!DOCTYPE html>
<html>
<head><title>Tenant Alumni Edit</title></head>
<body>
<h1>Edit Alumni Record</h1>
<p>Current phone: <?php echo htmlspecialchars($alumni['current_phone']); ?></p>
<form method="post" enctype="multipart/form-data">
    <input type="text" name="current_email" value="<?php echo htmlspecialchars((string) $alumni['current_email']); ?>">
    <input type="text" name="current_phone" value="<?php echo htmlspecialchars($alumni['current_phone']); ?>">
    <input type="text" name="occupation" value="<?php echo htmlspecialchars((string) $alumni['occupation']); ?>">
    <input type="text" name="address" value="<?php echo htmlspecialchars((string) $alumni['address']); ?>">
    <input type="file" name="documents">
    <button type="submit">Save</button>
</form>
</body>
</html>
