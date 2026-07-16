<!DOCTYPE html>
<html>
<head><title>Tenant Events Edit</title></head>
<body>
<h1>Edit Event</h1>
<form method="post">
    <input type="text" name="title" value="<?php echo htmlspecialchars($event['title']); ?>">
    <input type="text" name="start_date" value="<?php echo htmlspecialchars($event['event_start']); ?>">
    <input type="text" name="end_date" value="<?php echo htmlspecialchars($event['event_end']); ?>">
    <textarea name="description"><?php echo htmlspecialchars($event['description']); ?></textarea>
    <button type="submit">Save</button>
</form>
</body>
</html>
