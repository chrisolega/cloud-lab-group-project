<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
need_admin();
$base = '../';
$title = 'Manage Students';
$msg = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
if (isset($_POST['add'])) {
$index = trim($_POST['index_number']);
$sid = intval($_POST['school_id']);
if ($index != '') {
$q = $db->prepare('INSERT IGNORE INTO students (index_number, school_id) VALUES (?, ?)');
$q->execute(array($index, $sid));
$msg = 'Student index number added.';
}
} else if (isset($_POST['edit_id'])) {
$q = $db->prepare('UPDATE students SET index_number = ?, school_id = ? WHERE id = ?');
$q->execute(array(trim($_POST['index_number']), intval($_POST['school_id']), intval($_POST['edit_id'])));
$msg = 'Student updated.';
}
}
if (isset($_GET['del'])) {
$q = $db->prepare('DELETE FROM students WHERE id = ?');
$q->execute(array(intval($_GET['del'])));
$msg = 'Student removed.';
}
$edit = null;
if (isset($_GET['edit'])) {
$q = $db->prepare('SELECT * FROM students WHERE id = ?');
$q->execute(array(intval($_GET['edit'])));
$edit = $q->fetch();
}
$filter = isset($_GET['school']) ? intval($_GET['school']) : 0;
$schools = $db->query('SELECT * FROM schools ORDER BY id')->fetchAll();
if ($filter > 0) {
$q = $db->prepare('SELECT st.*, s.short_name FROM students st JOIN schools s ON s.id = st.school_id WHERE st.school_id = ? ORDER BY st.index_number');
$q->execute(array($filter));
} else {
$q = $db->query('SELECT st.*, s.short_name FROM students st JOIN schools s ON s.id = st.school_id ORDER BY s.id, st.index_number');
}
$students = $q->fetchAll();
include '../includes/header.php';
?>
<h1>Student Index Numbers (All Schools)</h1>
<?php if ($msg) { ?><div class="msg ok"><?php echo $msg; ?></div><?php } ?>
<div class="card">
<h2><?php echo $edit ? 'Edit student' : 'Add a student'; ?></h2>
<form method="post">
<?php if ($edit) { ?><input type="hidden" name="edit_id" value="<?php echo $edit['id']; ?>"><?php } else { ?><input type="hidden" name="add" value="1"><?php } ?>
<label>Index number</label>
<input type="text" name="index_number" value="<?php echo $edit ? htmlspecialchars($edit['index_number']) : ''; ?>" required>
<label>School</label>
<select name="school_id">
<?php foreach ($schools as $s) { ?>
<option value="<?php echo $s['id']; ?>" <?php if ($edit && $edit['school_id'] == $s['id']) echo 'selected'; ?>><?php echo htmlspecialchars($s['name']); ?></option>
<?php } ?>
</select>
<button type="submit" class="btn"><?php echo $edit ? 'Save changes' : 'Add student'; ?></button>
<?php if ($edit) { ?><a href="students.php" class="btn gold">Cancel</a><?php } ?>
</form>
</div>
<form method="get" style="margin-bottom:14px">
<label>Filter by school</label>
<select name="school" onchange="this.form.submit()">
<option value="0">All schools</option>
<?php foreach ($schools as $s) { ?>
<option value="<?php echo $s['id']; ?>" <?php if ($filter == $s['id']) echo 'selected'; ?>><?php echo htmlspecialchars($s['name']); ?></option>
<?php } ?>
</select>
</form>
<table>
<tr><th>Index number</th><th>School</th><th>Actions</th></tr>
<?php foreach ($students as $st) { ?>
<tr>
<td><?php echo htmlspecialchars($st['index_number']); ?></td>
<td><?php echo htmlspecialchars($st['short_name']); ?></td>
<td>
<a class="btn small gold" href="students.php?edit=<?php echo $st['id']; ?>">Edit</a>
<a class="btn small red" href="students.php?del=<?php echo $st['id']; ?>" onclick="return confirm('Remove this student?')">Remove</a>
</td>
</tr>
<?php } ?>
</table>
<?php include '../includes/footer.php'; ?>
