<!DOCTYPE html>
<html>
<head><title>Tenant Resume Details List</title></head>
<body>
<h2>Work Experience (<?php echo count($workExperience); ?>)</h2>
<ul>
<?php foreach ($workExperience as $row): ?>
    <li><?php echo htmlspecialchars((string) $row['institute']); ?> — <?php echo htmlspecialchars((string) $row['designation']); ?></li>
<?php endforeach; ?>
</ul>
<h2>Education (<?php echo count($education); ?>)</h2>
<ul>
<?php foreach ($education as $row): ?>
    <li><?php echo htmlspecialchars((string) $row['course']); ?> — <?php echo htmlspecialchars((string) $row['university']); ?></li>
<?php endforeach; ?>
</ul>
<h2>Skills (<?php echo count($skills); ?>)</h2>
<ul>
<?php foreach ($skills as $row): ?>
    <li><?php echo htmlspecialchars((string) $row['skill_category']); ?> — <?php echo htmlspecialchars((string) $row['skill_detail']); ?></li>
<?php endforeach; ?>
</ul>
<h2>References (<?php echo count($references); ?>)</h2>
<ul>
<?php foreach ($references as $row): ?>
    <li><?php echo htmlspecialchars((string) $row['name']); ?> — <?php echo htmlspecialchars((string) $row['relation']); ?></li>
<?php endforeach; ?>
</ul>
</body>
</html>
