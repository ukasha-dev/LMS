<!DOCTYPE html>
<html>
<head><title>Tenant Book Create</title></head>
<body>
<?php if ($created): ?>
<p>Book created with id <?php echo (int) $id; ?>.</p>
<?php else: ?>
<h1>Create Book</h1>
<form method="post">
    <input type="text" name="book_title" placeholder="Title">
    <input type="text" name="book_no" placeholder="Book No">
    <input type="text" name="isbn_no" placeholder="ISBN No">
    <input type="text" name="rack_no" placeholder="Rack No">
    <input type="text" name="subject" placeholder="Subject">
    <input type="text" name="author" placeholder="Author">
    <input type="text" name="qty" placeholder="Quantity">
    <button type="submit">Create</button>
</form>
<?php endif; ?>
</body>
</html>
