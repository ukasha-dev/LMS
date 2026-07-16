<!DOCTYPE html>
<html>
<head><title>Tenant Event Edit</title></head>
<body>
<h1>Edit Event</h1>
<form method="post">
    <input type="text" name="event_title" value="<?php echo htmlspecialchars($event['event_title']); ?>">
    <input type="text" name="event_description" value="<?php echo htmlspecialchars($event['event_description']); ?>">
    <input type="text" name="start_date" value="<?php echo htmlspecialchars($event['start_date']); ?>">
    <input type="text" name="end_date" value="<?php echo htmlspecialchars($event['end_date']); ?>">
    <button type="submit">Save</button>
</form>
</body>
</html>
