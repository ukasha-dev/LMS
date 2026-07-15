<!DOCTYPE html>
<html>
<head><title>Tenant Student Core Create</title></head>
<body>
<?php if ($created): ?>
<p>Student created with id <?php echo (int) $id; ?>.</p>
<?php else: ?>
<h1>Create Student</h1>
<form method="post">
    <input type="text" name="firstname" placeholder="First Name">
    <input type="text" name="middlename" placeholder="Middle Name">
    <input type="text" name="lastname" placeholder="Last Name">
    <input type="text" name="admission_no" placeholder="Admission No">
    <input type="text" name="gender" placeholder="Gender">
    <input type="text" name="dob" placeholder="DOB (YYYY-MM-DD)">
    <input type="text" name="category_id" placeholder="Category Id">
    <input type="text" name="school_house_id" placeholder="School House Id">
    <input type="text" name="hostel_room_id" placeholder="Hostel Room Id">
    <input type="text" name="class_id" placeholder="Class Id">
    <input type="text" name="section_id" placeholder="Section Id">
    <input type="text" name="session_id" placeholder="Session Id">
    <button type="submit">Create</button>
</form>
<?php endif; ?>
</body>
</html>
