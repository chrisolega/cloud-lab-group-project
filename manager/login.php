<?php
require_once '../includes/db.php';
$base = '../';
$title = 'Manager Login';
$sid = isset($_REQUEST['school']) ? intval($_REQUEST['school']) : 0;
$err = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $sid > 0) {
$user = trim($_POST['username']);
$pass = $_POST['password'];
$q = $db->prepare('SELECT * FROM managers WHERE username = ? AND school_id = ?');
$q->execute(array($user, $sid));
$m = $q->fetch();
if ($m && password_verify($pass, $m['password'])) {
$_SESSION['manager_id'] = $m['id'];
$_SESSION['manager_name'] = $m['username'];
$_SESSION['manager_school'] = $m['school_id'];
header('Location: dashboard.php');
exit;
} else {
$err = 'Wrong username or password for the selected school.';
}
}
include '../includes/header.php';
if ($sid == 0) {
$q = $db->query('SELECT * FROM schools ORDER BY id');
$schools = $q->fetchAll();
?>
<h1>Manager Sign In</h1>
<p class="lead">Step 1 of 2: select your school first.</p>
<div class="grid6">
<?php foreach ($schools as $s) { ?>
<a class="school-card" href="login.php?school=<?php echo $s['id']; ?>">
<div class="badge"><?php echo htmlspecialchars($s['short_name']); ?></div>
<h3><?php echo htmlspecialchars($s['name']); ?></h3>
<p>Manage this school</p>
</a>
<?php } ?>
</div>
<?php
} else {
$q = $db->prepare('SELECT * FROM schools WHERE id = ?');
$q->execute(array($sid));
$school = $q->fetch();
?>
<div class="card narrow">
<h1><?php echo htmlspecialchars($school['short_name']); ?> Manager</h1>
<p class="lead">Step 2 of 2: enter your login details.</p>
<?php if ($err) { ?><div class="msg bad"><?php echo $err; ?></div><?php } ?>
<form method="post">
<input type="hidden" name="school" value="<?php echo $sid; ?>">
<label>Username</label>
<input type="text" name="username" required>
<label>Password</label>
<input type="password" name="password" required>
<button type="submit" class="btn">Sign in</button>
<a href="login.php" class="btn gold">Change school</a>
</form>
</div>
<?php } ?>
<?php include '../includes/footer.php'; ?>
