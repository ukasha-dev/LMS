<!DOCTYPE html>
<html>
<head><title>Tenant Staff Core Create</title></head>
<body>
<?php if ($created): ?>
<p>Staff created with id <?php echo (int) $id; ?>.</p>
<p>employee_id: <?php echo htmlspecialchars((string) $employeeId); ?></p>
<?php if (!empty($image)): ?>
<p>image: <?php echo htmlspecialchars((string) $image); ?></p>
<?php endif; ?>
<?php elseif (!empty($duplicate)): ?>
<p>Employee id or email already exists for this tenant.</p>
<?php else: ?>
<h1>Create Staff</h1>
<form method="post" enctype="multipart/form-data">
    <input type="text" name="employee_id" placeholder="Employee Id (optional, auto-generated if left blank)">
    <input type="text" name="name" placeholder="Name">
    <input type="text" name="surname" placeholder="Surname">
    <input type="text" name="email" placeholder="Email">
    <input type="text" name="gender" placeholder="Gender">
    <input type="text" name="dob" placeholder="DOB (YYYY-MM-DD)">
    <input type="text" name="department" placeholder="Department Id">
    <input type="text" name="designation" placeholder="Designation Id">
    <input type="text" name="role_id" placeholder="Role Id">
    <input type="file" name="photo">
    <button type="submit">Create</button>
</form>
<?php endif; ?>
</body>
</html>
