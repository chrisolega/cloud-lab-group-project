<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/upload.php';
need_student();
$base = '../';
$title = 'Events';
$sid = $_SESSION['student_school'];
$q = $db->prepare('SELECT * FROM schools WHERE id = ?');
$q->execute(array($sid));
$school = $q->fetch();
$q = $db->prepare('SELECT * FROM events WHERE school_id = ? ORDER BY created_at DESC');
$q->execute(array($sid));
$events = $q->fetchAll();
include '../includes/header.php';
?>
<h1>Events at <?php echo htmlspecialchars($school['short_name']); ?></h1>
<p class="lead">Here is everything happening on campus and the buildings where you can find them.</p>
<?php if (count($events) == 0) { ?>
<div class="msg ok">No events posted yet. Check back soon.</div>
<?php } ?>
<div class="event-grid">
<?php foreach ($events as $e) { ?>
<div class="event-card">
<img src="<?php echo htmlspecialchars(pic($e['building_image'])); ?>" alt="<?php echo htmlspecialchars($e['building_name']); ?>">
<div class="body">
<h3><?php echo htmlspecialchars($e['building_name']); ?></h3>
<div class="loc"><?php echo htmlspecialchars($e['building_location']); ?></div>
<p><?php echo nl2br(htmlspecialchars($e['event_info'])); ?></p>
</div>
</div>
<?php } ?>
</div>
<?php include '../includes/footer.php'; ?>
