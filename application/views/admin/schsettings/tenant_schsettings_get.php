<!DOCTYPE html>
<html>
<head><title>Tenant School Settings</title></head>
<body>
<h1>School Settings</h1>
<p>Name: <?php echo htmlspecialchars((string) $settings['name']); ?></p>
<p>Email: <?php echo htmlspecialchars((string) $settings['email']); ?></p>
<p>Timezone: <?php echo htmlspecialchars((string) $settings['timezone']); ?></p>
</body>
</html>
