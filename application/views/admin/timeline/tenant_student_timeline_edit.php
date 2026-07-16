<!DOCTYPE html>
<html>
<head><title>Tenant Student Timeline Edit</title></head>
<body>
<h1>Edit Student Timeline</h1>
<form method="post">
    <input type="text" name="timeline_title" value="<?php echo htmlspecialchars($timeline['title']); ?>">
    <input type="text" name="timeline_date" value="<?php echo htmlspecialchars($timeline['timeline_date']); ?>">
    <input type="text" name="timeline_desc" value="<?php echo htmlspecialchars($timeline['description']); ?>">
    <button type="submit">Save</button>
</form>
</body>
</html>
