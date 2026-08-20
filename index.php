<?php
require_once 'includes/db.php';
$base = '';
$title = 'Home';
$q = $db->query('SELECT * FROM schools ORDER BY id');
$schools = $q->fetchAll();
include 'includes/header.php';
?>
<h1>Find your way around campus</h1>
<p class="lead">Select your school below to sign in and see where events are happening.</p>
<div class="grid6">
<?php foreach ($schools as $s) { ?>
<a class="school-card" href="student/login.php?school=<?php echo $s['id']; ?>">
<div class="badge"><?php echo htmlspecialchars($s['short_name']); ?></div>
<h3><?php echo htmlspecialchars($s['name']); ?></h3>
<p>Tap to sign in with your index number</p>
</a>
<?php } ?>
</div>
<?php include 'includes/footer.php'; ?>
