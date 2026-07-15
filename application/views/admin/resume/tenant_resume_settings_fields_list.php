<!DOCTYPE html>
<html>
<head><title>Tenant Resume Settings Fields List</title></head>
<body>
<h1>Resume Settings Fields (<?php echo count($resumeSettingsFieldsList); ?> real, global reference rows)</h1>
<ul>
<?php foreach ($resumeSettingsFieldsList as $row): ?>
    <li>Field #<?php echo (int) $row['id']; ?> — <?php echo htmlspecialchars((string) $row['name']); ?></li>
<?php endforeach; ?>
</ul>
</body>
</html>
