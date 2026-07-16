<!DOCTYPE html>
<html>
<head><title>Tenant Member Create</title></head>
<body>
<?php if ($created): ?>
<p>Member created with id <?php echo (int) $id; ?>.</p>
<?php else: ?>
<h1>Create Member</h1>
<form method="post">
    <input type="text" name="member_type" placeholder="Member Type (student/teacher)">
    <input type="text" name="member_id" placeholder="Member Id">
    <input type="text" name="library_card_no" placeholder="Library Card No">
    <button type="submit">Create</button>
</form>
<?php endif; ?>
</body>
</html>
