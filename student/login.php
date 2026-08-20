<?php
require_once '../includes/db.php';
$base = '../';
$title = 'Student Login';
$sid = isset($_GET['school']) ? intval($_GET['school']) : 0;
$q = $db->prepare('SELECT * FROM schools WHERE id = ?');
$q->execute(array($sid));
$school = $q->fetch();
if (!$school) {
header('Location: ../index.php');
exit;
}
$err = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
$index = trim($_POST['index_number']);
$q = $db->prepare('SELECT * FROM students WHERE index_number = ? AND school_id = ?');
$q->execute(array($index, $sid));
$st = $q->fetch();
if ($st) {
$_SESSION['student_id'] = $st['id'];
$_SESSION['student_index'] = $st['index_number'];
$_SESSION['student_school'] = $sid;
header('Location: events.php');
exit;
} else {
$err = 'Index number not found for this school. Please check and try again.';
}
}
include '../includes/header.php';
?>
<div class="card narrow">
<h1><?php echo htmlspecialchars($school['short_name']); ?> Student Sign In</h1>
<p class="lead"><?php echo htmlspecialchars($school['name']); ?></p>
<?php if ($err) { ?><div class="msg bad"><?php echo $err; ?></div><?php } ?>
<form method="post">
<label>Index number</label>
<input type="text" name="index_number" required autofocus>
<button type="submit" class="btn">Sign in</button>
<a href="../index.php" class="btn gold">Back</a>
</form>
</div>
<?php include '../includes/footer.php'; ?>
