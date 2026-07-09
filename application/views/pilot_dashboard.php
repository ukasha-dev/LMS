<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<h1>Pilot Login Successful</h1>
<ul>
    <li>Staff ID: <?php echo htmlspecialchars((string) $session['id'], ENT_QUOTES); ?></li>
    <li>Name: <?php echo htmlspecialchars($session['username'], ENT_QUOTES); ?></li>
    <li>Email: <?php echo htmlspecialchars($session['email'], ENT_QUOTES); ?></li>
    <li>Role: <?php echo htmlspecialchars($session['role'], ENT_QUOTES); ?></li>
    <li>Tenant ID: <?php echo htmlspecialchars((string) $session['tenant_id'], ENT_QUOTES); ?></li>
</ul>
