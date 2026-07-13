<!DOCTYPE html>
<html>
<head><title>Tenant Exam Results List</title></head>
<body>
<h1>Exam Results (<?php echo count($examResultsList); ?> real, tenant-scoped rows)</h1>
<ul>
<?php foreach ($examResultsList as $result): ?>
    <li>Result #<?php echo (int) $result['id']; ?> — student link <?php echo (int) $result['exam_group_class_batch_exam_student_id']; ?>, marks <?php echo htmlspecialchars((string) $result['get_marks']); ?></li>
<?php endforeach; ?>
</ul>
</body>
</html>
