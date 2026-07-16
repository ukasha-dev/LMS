<!DOCTYPE html>
<html>
<head><title>Tenant Approve Leave Create</title></head>
<body>
<?php if ($created): ?>
<p>Leave request created with id <?php echo (int) $id; ?>.</p>
<?php else: ?>
<h1>Apply Leave</h1>
<form method="post" enctype="multipart/form-data">
    <input type="text" name="student_session_id" placeholder="Student Session Id">
    <input type="text" name="apply_date" placeholder="Apply Date">
    <input type="text" name="from_date" placeholder="From Date">
    <input type="text" name="to_date" placeholder="To Date">
    <textarea name="reason" placeholder="Reason"></textarea>
    <input type="file" name="userfile">
    <button type="submit">Apply</button>
</form>
<?php endif; ?>
</body>
</html>
