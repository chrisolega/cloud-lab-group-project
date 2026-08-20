<?php
require_once '../includes/db.php';
$base = '../';
$title = 'Admin Login';
$err = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
$user = trim($_POST['username']);
$pass = $_POST['password'];
$q = $db->prepare('SELECT * FROM admins WHERE username = ?');
$q->execute(array($user));
$a = $q->fetch();
if ($a && password_verify($pass, $a['password'])) {
$_SESSION['admin_id'] = $a['id'];
$_SESSION['admin_name'] = $a['username'];
header('Location: dashboard.php');
exit;
} else {
$err = 'Wrong username or password.';
}
}
include '../includes/header.php';
?>
<div class="card narrow">
<h1>Admin Sign In</h1>
<p class="lead">For system administrators only.</p>
<?php if ($err) { ?><div class="msg bad"><?php echo $err; ?></div><?php } ?>
<form method="post">
<label>Username</label>
<input type="text" name="username" required>
<label>Password</label>
<input type="password" name="password" required>
<button type="submit" class="btn">Sign in</button>
</form>
</div>
<?php include '../includes/footer.php'; ?>
