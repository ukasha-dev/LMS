<!DOCTYPE html>
<html>
<head><title>Tenant Visitor Create</title></head>
<body>
<?php if ($created): ?>
<p>Visitor created with id <?php echo (int) $id; ?>.</p>
<?php else: ?>
<h1>Create Visitor</h1>
<form method="post" enctype="multipart/form-data">
    <input type="text" name="purpose" placeholder="Purpose">
    <input type="text" name="name" placeholder="Name">
    <input type="text" name="contact" placeholder="Contact">
    <input type="text" name="id_proof" placeholder="Id Proof">
    <input type="text" name="no_of_people" placeholder="No Of People">
    <input type="text" name="date" placeholder="Date (YYYY-MM-DD)">
    <input type="text" name="in_time" placeholder="In Time">
    <input type="text" name="out_time" placeholder="Out Time">
    <input type="text" name="note" placeholder="Note">
    <select name="meeting_with">
        <option value="staff">staff</option>
        <option value="student">student</option>
    </select>
    <input type="text" name="staff_id" placeholder="Staff Id (if meeting_with=staff)">
    <input type="text" name="student_session_id" placeholder="Student Session Id (if meeting_with=student)">
    <input type="file" name="photo">
    <button type="submit">Create</button>
</form>
<?php endif; ?>
</body>
</html>
