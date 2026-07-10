<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<h1>Pilot Attendance</h1>
<ul>
<?php foreach ($rows as $row): ?>
    <li>
        <?php echo htmlspecialchars($row['student'], ENT_QUOTES); ?> —
        <?php echo htmlspecialchars((string) $row['date'], ENT_QUOTES); ?>:
        <?php echo htmlspecialchars($row['type'], ENT_QUOTES); ?>
    </li>
<?php endforeach; ?>
</ul>
