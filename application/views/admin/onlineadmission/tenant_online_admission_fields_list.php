<!DOCTYPE html>
<html>
<head><title>Tenant Online Admission Fields List</title></head>
<body>
<h1>Online Admission Fields (<?php echo count($onlineAdmissionFieldsList); ?> real, global reference rows)</h1>
<ul>
<?php foreach ($onlineAdmissionFieldsList as $row): ?>
    <li>Field #<?php echo (int) $row['id']; ?> — <?php echo htmlspecialchars((string) $row['name']); ?></li>
<?php endforeach; ?>
</ul>
</body>
</html>
