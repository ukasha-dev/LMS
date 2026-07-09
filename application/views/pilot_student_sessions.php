<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<h1>Pilot Student Sessions</h1>
<ul>
<?php foreach ($rows as $row): ?>
    <li>
        <?php echo htmlspecialchars($row['student'], ENT_QUOTES); ?>:
        <?php echo htmlspecialchars($row['class'], ENT_QUOTES); ?>
        <?php echo htmlspecialchars($row['section'], ENT_QUOTES); ?>
    </li>
<?php endforeach; ?>
</ul>
