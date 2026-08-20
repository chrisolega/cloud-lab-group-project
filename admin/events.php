<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/upload.php';
need_admin();
$base = '../';
$title = 'Manage Events';
$msg = '';
$bad = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
$bname = trim($_POST['building_name']);
$bloc = trim($_POST['building_location']);
$info = trim($_POST['event_info']);
$sid = intval($_POST['school_id']);
$img = save_image($_FILES['building_image']);
if ($img && $bname != '' && $bloc != '' && $info != '') {
$q = $db->prepare('INSERT INTO events (school_id, building_name, building_location, building_image, event_info) VALUES (?, ?, ?, ?, ?)');
$q->execute(array($sid, $bname, $bloc, $img, $info));
$msg = 'Event added.';
} else {
$bad = 'Please fill every field and upload a valid image.';
}
}
if (isset($_GET['del'])) {
$q = $db->prepare('DELETE FROM events WHERE id = ?');
$q->execute(array(intval($_GET['del'])));
$msg = 'Event deleted.';
}
$filter = isset($_GET['school']) ? intval($_GET['school']) : 0;
$schools = $db->query('SELECT * FROM schools ORDER BY id')->fetchAll();
if ($filter > 0) {
$q = $db->prepare('SELECT e.*, s.short_name FROM events e JOIN schools s ON s.id = e.school_id WHERE e.school_id = ? ORDER BY e.created_at DESC');
$q->execute(array($filter));
} else {
$q = $db->query('SELECT e.*, s.short_name FROM events e JOIN schools s ON s.id = e.school_id ORDER BY e.created_at DESC');
}
$events = $q->fetchAll();
include '../includes/header.php';
?>
<h1>Events (All Schools)</h1>
<?php if ($msg) { ?><div class="msg ok"><?php echo $msg; ?></div><?php } ?>
<?php if ($bad) { ?><div class="msg bad"><?php echo $bad; ?></div><?php } ?>
<div class="card">
<h2>Add an event for any school</h2>
<form method="post" enctype="multipart/form-data">
<label>School</label>
<select name="school_id">
<?php foreach ($schools as $s) { ?>
<option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['name']); ?></option>
<?php } ?>
</select>
<label>Image of the building</label>
<input type="file" name="building_image" accept="image/*" required>
<label>Building name</label>
<input type="text" name="building_name" required>
<label>Location of the building</label>
<input type="text" name="building_location" required>
<label>Brief information about the event</label>
<textarea name="event_info" required></textarea>
<button type="submit" class="btn">Add event</button>
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
<div class="event-grid">
<?php foreach ($events as $e) { ?>
<div class="event-card">
<img src="<?php echo htmlspecialchars(pic($e['building_image'])); ?>" alt="">
<div class="body">
<h3><?php echo htmlspecialchars($e['building_name']); ?> <small>(<?php echo htmlspecialchars($e['short_name']); ?>)</small></h3>
<div class="loc"><?php echo htmlspecialchars($e['building_location']); ?></div>
<p><?php echo nl2br(htmlspecialchars($e['event_info'])); ?></p>
<p class="actions" style="margin-top:12px">
<a class="btn small gold" href="edit_event.php?id=<?php echo $e['id']; ?>">Edit</a>
<a class="btn small red" href="events.php?del=<?php echo $e['id']; ?>" onclick="return confirm('Delete this event?')">Delete</a>
</p>
</div>
</div>
<?php } ?>
</div>
<?php include '../includes/footer.php'; ?>
