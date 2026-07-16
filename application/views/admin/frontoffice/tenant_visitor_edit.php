<!DOCTYPE html>
<html>
<head><title>Tenant Visitor Edit</title></head>
<body>
<?php if ($updated): ?>
<p>Visitor <?php echo (int) $visitor['id']; ?> updated.</p>
<?php endif; ?>
<h1>Edit Visitor #<?php echo (int) $visitor['id']; ?></h1>
<p>image: <?php echo htmlspecialchars((string) ($visitor['image'] ?? '')); ?></p>
<form method="post" enctype="multipart/form-data">
    <input type="text" name="purpose" value="<?php echo htmlspecialchars((string) $visitor['purpose']); ?>">
    <input type="text" name="name" value="<?php echo htmlspecialchars((string) $visitor['name']); ?>">
    <input type="text" name="contact" value="<?php echo htmlspecialchars((string) $visitor['contact']); ?>">
    <input type="text" name="id_proof" value="<?php echo htmlspecialchars((string) $visitor['id_proof']); ?>">
    <input type="text" name="no_of_people" value="<?php echo htmlspecialchars((string) $visitor['no_of_people']); ?>">
    <input type="text" name="date" value="<?php echo htmlspecialchars((string) $visitor['date']); ?>">
    <input type="text" name="in_time" value="<?php echo htmlspecialchars((string) $visitor['in_time']); ?>">
    <input type="text" name="out_time" value="<?php echo htmlspecialchars((string) $visitor['out_time']); ?>">
    <input type="text" name="note" value="<?php echo htmlspecialchars((string) $visitor['note']); ?>">
    <select name="meeting_with">
        <option value="staff" <?php echo $visitor['meeting_with'] === 'staff' ? 'selected' : ''; ?>>staff</option>
        <option value="student" <?php echo $visitor['meeting_with'] === 'student' ? 'selected' : ''; ?>>student</option>
    </select>
    <input type="text" name="staff_id" value="<?php echo htmlspecialchars((string) $visitor['staff_id']); ?>">
    <input type="text" name="student_session_id" value="<?php echo htmlspecialchars((string) $visitor['student_session_id']); ?>">
    <input type="file" name="photo">
    <button type="submit">Update</button>
</form>
</body>
</html>
