<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
need_admin();
$base = '../';
$title = 'Manage Managers';
$msg = '';
$bad = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
if (isset($_POST['add'])) {
$user = trim($_POST['username']);
$pass = $_POST['password'];
$sid = intval($_POST['school_id']);
if ($user != '' && $pass != '') {
$q = $db->prepare('INSERT INTO managers (username, password, school_id) VALUES (?, ?, ?)');
$q->execute(array($user, password_hash($pass, PASSWORD_DEFAULT), $sid));
$msg = 'Manager account created.';
} else {
$bad = 'Username and password are required.';
}
} else if (isset($_POST['edit_id'])) {
$id = intval($_POST['edit_id']);
$user = trim($_POST['username']);
$sid = intval($_POST['school_id']);
if (trim($_POST['password']) != '') {
$q = $db->prepare('UPDATE managers SET username = ?, password = ?, school_id = ? WHERE id = ?');
$q->execute(array($user, password_hash($_POST['password'], PASSWORD_DEFAULT), $sid, $id));
} else {
$q = $db->prepare('UPDATE managers SET username = ?, school_id = ? WHERE id = ?');
$q->execute(array($user, $sid, $id));
}
$msg = 'Manager account updated.';
}
}
if (isset($_GET['del'])) {
$q = $db->prepare('DELETE FROM managers WHERE id = ?');
$q->execute(array(intval($_GET['del'])));
$msg = 'Manager account deleted.';
}
$edit = null;
if (isset($_GET['edit'])) {
$q = $db->prepare('SELECT * FROM managers WHERE id = ?');
$q->execute(array(intval($_GET['edit'])));
$edit = $q->fetch();
}
$schools = $db->query('SELECT * FROM schools ORDER BY id')->fetchAll();
$q = $db->query('SELECT m.*, s.short_name FROM managers m JOIN schools s ON s.id = m.school_id ORDER BY s.id');
$managers = $q->fetchAll();
include '../includes/header.php';
?>
<h1>Manager Accounts</h1>
<p class="lead">Create, edit or delete manager accounts for any school.</p>
<?php if ($msg) { ?><div class="msg ok"><?php echo $msg; ?></div><?php } ?>
<?php if ($bad) { ?><div class="msg bad"><?php echo $bad; ?></div><?php } ?>
<div class="card">
<h2><?php echo $edit ? 'Edit manager' : 'Add a manager'; ?></h2>
<form method="post">
<?php if ($edit) { ?><input type="hidden" name="edit_id" value="<?php echo $edit['id']; ?>"><?php } else { ?><input type="hidden" name="add" value="1"><?php } ?>
<label>Username</label>
<input type="text" name="username" value="<?php echo $edit ? htmlspecialchars($edit['username']) : ''; ?>" required>
<label>Password <?php echo $edit ? '(leave empty to keep the current one)' : ''; ?></label>
<input type="password" name="password" <?php echo $edit ? '' : 'required'; ?>>
<label>School</label>
<select name="school_id">
<?php foreach ($schools as $s) { ?>
<option value="<?php echo $s['id']; ?>" <?php if ($edit && $edit['school_id'] == $s['id']) echo 'selected'; ?>><?php echo htmlspecialchars($s['name']); ?></option>
<?php } ?>
</select>
<button type="submit" class="btn"><?php echo $edit ? 'Save changes' : 'Create manager'; ?></button>
<?php if ($edit) { ?><a href="managers.php" class="btn gold">Cancel</a><?php } ?>
</form>
</div>
<h2>All managers</h2>
<table>
<tr><th>Username</th><th>School</th><th>Actions</th></tr>
<?php foreach ($managers as $m) { ?>
<tr>
<td><?php echo htmlspecialchars($m['username']); ?></td>
<td><?php echo htmlspecialchars($m['short_name']); ?></td>
<td>
<a class="btn small gold" href="managers.php?edit=<?php echo $m['id']; ?>">Edit</a>
<a class="btn small red" href="managers.php?del=<?php echo $m['id']; ?>" onclick="return confirm('Delete this manager?')">Delete</a>
</td>
</tr>
<?php } ?>
</table>
<?php include '../includes/footer.php'; ?>
