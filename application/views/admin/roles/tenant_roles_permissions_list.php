<!DOCTYPE html>
<html>
<head><title>Tenant Roles Permissions List</title></head>
<body>
<h1>Roles Permissions (<?php echo count($rolesPermissionsList); ?> real, tenant-scoped rows)</h1>
<ul>
<?php foreach ($rolesPermissionsList as $row): ?>
    <li>#<?php echo (int) $row['id']; ?> — <?php echo htmlspecialchars((string) $row['role_name']); ?>: <?php echo htmlspecialchars((string) $row['permission_name']); ?> (view=<?php echo (int) $row['can_view']; ?>, add=<?php echo (int) $row['can_add']; ?>, edit=<?php echo (int) $row['can_edit']; ?>, delete=<?php echo (int) $row['can_delete']; ?>)</li>
<?php endforeach; ?>
</ul>
</body>
</html>
