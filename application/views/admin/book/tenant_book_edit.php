<!DOCTYPE html>
<html>
<head><title>Tenant Book Edit</title></head>
<body>
<h1>Edit Book</h1>
<form method="post">
    <input type="text" name="book_title" value="<?php echo htmlspecialchars($book['book_title']); ?>">
    <input type="text" name="book_no" value="<?php echo htmlspecialchars($book['book_no']); ?>">
    <input type="text" name="isbn_no" value="<?php echo htmlspecialchars($book['isbn_no']); ?>">
    <input type="text" name="rack_no" value="<?php echo htmlspecialchars($book['rack_no']); ?>">
    <button type="submit">Save</button>
</form>
</body>
</html>
