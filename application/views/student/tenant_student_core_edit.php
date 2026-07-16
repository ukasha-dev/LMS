<!DOCTYPE html>
<html>
<head><title>Tenant Student Core Edit</title></head>
<body>
<?php if ($updated): ?>
<p>Student <?php echo (int) $student['id']; ?> updated.</p>
<?php endif; ?>
<h1>Edit Student #<?php echo (int) $student['id']; ?></h1>
<p>parent_id: <?php echo (int) $student['parent_id']; ?></p>
<p>image: <?php echo htmlspecialchars((string) ($student['image'] ?? '')); ?></p>
<form method="post" enctype="multipart/form-data">
    <input type="text" name="firstname" value="<?php echo htmlspecialchars((string) $student['firstname']); ?>">
    <input type="text" name="middlename" value="<?php echo htmlspecialchars((string) $student['middlename']); ?>">
    <input type="text" name="lastname" value="<?php echo htmlspecialchars((string) $student['lastname']); ?>">
    <input type="text" name="gender" value="<?php echo htmlspecialchars((string) $student['gender']); ?>">
    <input type="text" name="dob" value="<?php echo htmlspecialchars((string) $student['dob']); ?>">
    <input type="text" name="category_id" value="<?php echo htmlspecialchars((string) $student['category_id']); ?>">
    <input type="text" name="school_house_id" value="<?php echo htmlspecialchars((string) $student['school_house_id']); ?>">
    <input type="text" name="hostel_room_id" value="<?php echo htmlspecialchars((string) $student['hostel_room_id']); ?>">
    <input type="text" name="class_id" value="<?php echo htmlspecialchars((string) ($session['class_id'] ?? '')); ?>">
    <input type="text" name="section_id" value="<?php echo htmlspecialchars((string) ($session['section_id'] ?? '')); ?>">
    <input type="text" name="session_id" value="<?php echo htmlspecialchars((string) ($session['session_id'] ?? '')); ?>">
    <input type="text" name="sibling_id" placeholder="Sibling Id">
    <input type="file" name="photo">
    <button type="submit">Update</button>
</form>
</body>
</html>
