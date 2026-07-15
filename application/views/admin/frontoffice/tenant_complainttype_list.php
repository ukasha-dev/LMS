<!DOCTYPE html>
<html>
<head><title>Tenant Complainttype List</title></head>
<body>
<h1>Complaint Types (<?php echo count($complainttypeList); ?> real, tenant-scoped rows)</h1>
<ul>
<?php foreach ($complainttypeList as $row): ?>
    <li>Complaint Type #<?php echo (int) $row['id']; ?> — <?php echo htmlspecialchars((string) $row['complaint_type']); ?></li>
<?php endforeach; ?>
</ul>
</body>
</html>
