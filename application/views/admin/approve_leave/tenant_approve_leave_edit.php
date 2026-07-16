<!DOCTYPE html>
<html>
<head><title>Tenant Approve Leave Edit</title></head>
<body>
<h1>Approve/Reject Leave</h1>
<p>Current status: <?php echo (int) $leave['status']; ?></p>
<p>Reason: <?php echo htmlspecialchars($leave['reason']); ?></p>
<form method="post">
    <input type="text" name="status" placeholder="1=approve, 2=reject">
    <button type="submit">Save</button>
</form>
</body>
</html>
