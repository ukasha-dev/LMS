<!DOCTYPE html>
<html>
<head><title>Tenant Staff Core Edit</title></head>
<body>
<?php if ($updated): ?>
<p>Staff <?php echo (int) $staff['id']; ?> updated.</p>
<?php elseif (!empty($duplicate)): ?>
<p>Email already exists for this tenant.</p>
<?php endif; ?>
<h1>Edit Staff #<?php echo (int) $staff['id']; ?></h1>
<form method="post">
    <input type="text" name="name" value="<?php echo htmlspecialchars((string) $staff['name']); ?>">
    <input type="text" name="surname" value="<?php echo htmlspecialchars((string) $staff['surname']); ?>">
    <input type="text" name="email" value="<?php echo htmlspecialchars((string) $staff['email']); ?>">
    <input type="text" name="gender" value="<?php echo htmlspecialchars((string) $staff['gender']); ?>">
    <input type="text" name="dob" value="<?php echo htmlspecialchars((string) $staff['dob']); ?>">
    <input type="text" name="department" value="<?php echo htmlspecialchars((string) $staff['department']); ?>">
    <input type="text" name="designation" value="<?php echo htmlspecialchars((string) $staff['designation']); ?>">
    <button type="submit">Update</button>
</form>
</body>
</html>
