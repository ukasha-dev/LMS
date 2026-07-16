<!DOCTYPE html>
<html>
<head><title>Tenant Events Create</title></head>
<body>
<?php if ($created): ?>
<p>Event created with id <?php echo (int) $id; ?>.</p>
<?php else: ?>
<h1>Create Event</h1>
<form method="post">
    <input type="text" name="title" placeholder="Title">
    <input type="text" name="start_date" placeholder="Start Date">
    <input type="text" name="end_date" placeholder="End Date">
    <input type="text" name="venue" placeholder="Venue">
    <textarea name="description" placeholder="Description"></textarea>
    <button type="submit">Create</button>
</form>
<?php endif; ?>
</body>
</html>
