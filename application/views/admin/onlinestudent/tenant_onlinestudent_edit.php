<!DOCTYPE html>
<html>
<head><title>Tenant Online Student Edit</title></head>
<body>
<h1>Edit Online Admission Application</h1>
<p>First name: <?php echo htmlspecialchars($application['firstname']); ?></p>
<form method="post">
    <input type="text" name="firstname" value="<?php echo htmlspecialchars($application['firstname']); ?>">
    <input type="text" name="mobileno" value="<?php echo htmlspecialchars((string) $application['mobileno']); ?>">
    <input type="text" name="email" value="<?php echo htmlspecialchars((string) $application['email']); ?>">
    <button type="submit">Save</button>
</form>
</body>
</html>
