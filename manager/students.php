<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
need_manager();
$base = '../';
$title = 'Manage Students';
$sid = $_SESSION['manager_school'];
$msg = '';
$bad = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == 0) {
$added = 0;
$fh = fopen($_FILES['csv_file']['tmp_name'], 'r');
while (($row = fgetcsv($fh)) !== false) {
foreach ($row as $cell) {
$index = trim($cell);
if ($index != '' && strtolower($index) != 'index_number' && strtolower($index) != 'index number') {
$q = $db->prepare('INSERT IGNORE INTO students (index_number, school_id) VALUES (?, ?)');
$q->execute(array($index, $sid));
if ($q->rowCount() > 0) {
$added = $added + 1;
}
}
}
}
fclose($fh);
$msg = $added . ' index number(s) added from the CSV file.';
} else if (isset($_POST['manual_list'])) {
$added = 0;
$list = preg_split('/[\s,;]+/', $_POST['manual_list']);
foreach ($list as $index) {
$index = trim($index);
if ($index != '') {
$q = $db->prepare('INSERT IGNORE INTO students (index_number, school_id) VALUES (?, ?)');
$q->execute(array($index, $sid));
if ($q->rowCount() > 0) {
$added = $added + 1;
}
}
}
$msg = $added . ' index number(s) added.';
} else if (isset($_POST['edit_id'])) {
$q = $db->prepare('UPDATE students SET index_number = ? WHERE id = ? AND school_id = ?');
$q->execute(array(trim($_POST['new_index']), intval($_POST['edit_id']), $sid));
$msg = 'Index number updated.';
}
}
if (isset($_GET['del'])) {
$q = $db->prepare('DELETE FROM students WHERE id = ? AND school_id = ?');
$q->execute(array(intval($_GET['del']), $sid));
$msg = 'Index number removed.';
}
$edit = null;
if (isset($_GET['edit'])) {
$q = $db->prepare('SELECT * FROM students WHERE id = ? AND school_id = ?');
$q->execute(array(intval($_GET['edit']), $sid));
$edit = $q->fetch();
}
$q = $db->prepare('SELECT * FROM students WHERE school_id = ? ORDER BY index_number');
$q->execute(array($sid));
$students = $q->fetchAll();
include '../includes/header.php';
?>
<h1>Student Index Numbers</h1>
<p class="lead">Students on this list can sign in to your school with their index number alone.</p>
<?php if ($msg) { ?><div class="msg ok"><?php echo $msg; ?></div><?php } ?>
<?php if ($bad) { ?><div class="msg bad"><?php echo $bad; ?></div><?php } ?>
<div class="card">
<h2>Upload a CSV file</h2>
<p class="lead">The file should contain the index numbers of all students in your school. They will be extracted and saved automatically.</p>
<form method="post" enctype="multipart/form-data">
<input type="file" name="csv_file" accept=".csv" required>
<button type="submit" class="btn">Upload and extract</button>
</form>
</div>
<div class="card">
<h2>Or enter index numbers manually</h2>
<form method="post">
<label>Type one index number per line (commas also work)</label>
<textarea name="manual_list" required></textarea>
<button type="submit" class="btn">Add index numbers</button>
</form>
</div>
<?php if ($edit) { ?>
<div class="card">
<h2>Edit index number</h2>
<form method="post">
<input type="hidden" name="edit_id" value="<?php echo $edit['id']; ?>">
<input type="text" name="new_index" value="<?php echo htmlspecialchars($edit['index_number']); ?>" required>
<button type="submit" class="btn">Save</button>
<a href="students.php" class="btn gold">Cancel</a>
</form>
</div>
<?php } ?>
<h2>Registered students (<?php echo count($students); ?>)</h2>
<table>
<tr><th>Index number</th><th>Actions</th></tr>
<?php foreach ($students as $st) { ?>
<tr>
<td><?php echo htmlspecialchars($st['index_number']); ?></td>
<td>
<a class="btn small gold" href="students.php?edit=<?php echo $st['id']; ?>">Edit</a>
<a class="btn small red" href="students.php?del=<?php echo $st['id']; ?>" onclick="return confirm('Remove this index number?')">Remove</a>
</td>
</tr>
<?php } ?>
</table>
<?php include '../includes/footer.php'; ?>
