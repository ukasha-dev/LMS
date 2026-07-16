<!DOCTYPE html>
<html>
<head><title>Tenant Event Create</title></head>
<body>
<?php if ($created): ?>
<p>Event created with id <?php echo (int) $id; ?>.</p>
<?php else: ?>
<h1>Create Event</h1>
<form method="post">
    <input type="text" name="event_title" placeholder="Title">
    <input type="text" name="event_description" placeholder="Description">
    <input type="text" name="start_date" placeholder="Start Date">
    <input type="text" name="end_date" placeholder="End Date">
    <input type="text" name="event_type" placeholder="Event Type">
    <input type="text" name="event_for" placeholder="Event For">
    <input type="text" name="role_id" placeholder="Role Id">
    <button type="submit">Create</button>
</form>
<?php endif; ?>
</body>
</html>
