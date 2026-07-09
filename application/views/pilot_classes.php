<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<h1>Pilot Classes</h1>
<ul>
<?php foreach ($rows as $row): ?>
    <li>
        <?php echo htmlspecialchars($row['class'], ENT_QUOTES); ?>:
        <?php echo htmlspecialchars(implode(', ', $row['sections']), ENT_QUOTES); ?>
    </li>
<?php endforeach; ?>
</ul>
