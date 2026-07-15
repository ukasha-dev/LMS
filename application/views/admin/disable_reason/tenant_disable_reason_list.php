<!DOCTYPE html>
<html>
<head><title>Tenant Disable Reason List</title></head>
<body>
<h1>Disable Reasons (<?php echo count($disableReasonList); ?> real, tenant-scoped rows)</h1>
<ul>
<?php foreach ($disableReasonList as $row): ?>
    <li>Disable Reason #<?php echo (int) $row['id']; ?> — <?php echo htmlspecialchars((string) $row['reason']); ?></li>
<?php endforeach; ?>
</ul>
</body>
</html>
