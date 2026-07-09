<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<h1>Pilot Students (tenant_id = <?php echo $this->session->userdata('pilot_tenant_id'); ?>)</h1>
<ul>
<?php foreach ($students as $student): ?>
    <li><?php echo htmlspecialchars($student['firstname'] . ' ' . $student['lastname'], ENT_QUOTES); ?></li>
<?php endforeach; ?>
</ul>
