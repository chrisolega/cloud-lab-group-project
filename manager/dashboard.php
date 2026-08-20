<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/upload.php';
need_manager();
$base = '../';
$title = 'Manager Dashboard';
$sid = $_SESSION['manager_school'];
$msg = '';
$bad = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
$bname = trim($_POST['building_name']);
$bloc = trim($_POST['building_location']);
$info = trim($_POST['event_info']);
$img = save_image($_FILES['building_image']);
if ($img && $bname != '' && $bloc != '' && $info != '') {
$q = $db->prepare('INSERT INTO events (school_id, building_name, building_location, building_image, event_info) VALUES (?, ?, ?, ?, ?)');
$q->execute(array($sid, $bname, $bloc, $img, $info));
$msg = 'Event added. Students can see it right away.';
} else {
$bad = 'Please fill every field and upload a valid image (jpg, png, gif or webp, max 5MB).';
}
}
if (isset($_GET['del'])) {
$q = $db->prepare('DELETE FROM events WHERE id = ? AND school_id = ?');
$q->execute(array(intval($_GET['del']), $sid));
$msg = 'Event deleted.';
}
$q = $db->prepare('SELECT * FROM events WHERE school_id = ? ORDER BY created_at DESC');
$q->execute(array($sid));
$events = $q->fetchAll();
include '../includes/header.php';
?>
<h1>Manage Events</h1>
<p class="lead">Add an event and the building where it will happen. Changes show to students immediately.</p>
<?php if ($msg) { ?><div class="msg ok"><?php echo $msg; ?></div><?php } ?>
<?php if ($bad) { ?><div class="msg bad"><?php echo $bad; ?></div><?php } ?>
<div class="card">
<h2>Add a new event</h2>
<form method="post" enctype="multipart/form-data">
<label>Image of the building</label>
<input type="file" name="building_image" accept="image/*" required>
<label>Building name</label>
<input type="text" name="building_name" required>
<label>Location of the building</label>
<input type="text" name="building_location" placeholder="e.g. Behind the main library, North Campus" required>
<label>Brief information about the event</label>
<textarea name="event_info" required></textarea>
<button type="submit" class="btn">Add event</button>
</form>
</div>
<h2>Your events</h2>
<div class="event-grid">
<?php foreach ($events as $e) { ?>
<div class="event-card">
<img src="<?php echo htmlspecialchars(pic($e['building_image'])); ?>" alt="">
<div class="body">
<h3><?php echo htmlspecialchars($e['building_name']); ?></h3>
<div class="loc"><?php echo htmlspecialchars($e['building_location']); ?></div>
<p><?php echo nl2br(htmlspecialchars($e['event_info'])); ?></p>
<p class="actions" style="margin-top:12px">
<a class="btn small gold" href="edit_event.php?id=<?php echo $e['id']; ?>">Edit</a>
<a class="btn small red" href="dashboard.php?del=<?php echo $e['id']; ?>" onclick="return confirm('Delete this event?')">Delete</a>
</p>
</div>
</div>
<?php } ?>
</div>
<?php include '../includes/footer.php'; ?>
