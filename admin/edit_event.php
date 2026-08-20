<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/upload.php';
need_admin();
$base = '../';
$title = 'Edit Event';
$id = intval($_GET['id']);
$q = $db->prepare('SELECT * FROM events WHERE id = ?');
$q->execute(array($id));
$e = $q->fetch();
if (!$e) {
header('Location: events.php');
exit;
}
$schools = $db->query('SELECT * FROM schools ORDER BY id')->fetchAll();
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
$bname = trim($_POST['building_name']);
$bloc = trim($_POST['building_location']);
$info = trim($_POST['event_info']);
$sid = intval($_POST['school_id']);
$img = $e['building_image'];
if (isset($_FILES['building_image']) && $_FILES['building_image']['error'] == 0) {
$new = save_image($_FILES['building_image']);
if ($new) {
$img = $new;
}
}
$q = $db->prepare('UPDATE events SET school_id = ?, building_name = ?, building_location = ?, building_image = ?, event_info = ? WHERE id = ?');
$q->execute(array($sid, $bname, $bloc, $img, $info, $id));
header('Location: events.php');
exit;
}
include '../includes/header.php';
?>
<div class="card narrow">
<h1>Edit event</h1>
<img src="<?php echo htmlspecialchars(pic($e['building_image'])); ?>" style="width:100%;border-radius:8px" alt="">
<form method="post" enctype="multipart/form-data">
<label>School</label>
<select name="school_id">
<?php foreach ($schools as $s) { ?>
<option value="<?php echo $s['id']; ?>" <?php if ($e['school_id'] == $s['id']) echo 'selected'; ?>><?php echo htmlspecialchars($s['name']); ?></option>
<?php } ?>
</select>
<label>Change building image (optional)</label>
<input type="file" name="building_image" accept="image/*">
<label>Building name</label>
<input type="text" name="building_name" value="<?php echo htmlspecialchars($e['building_name']); ?>" required>
<label>Location of the building</label>
<input type="text" name="building_location" value="<?php echo htmlspecialchars($e['building_location']); ?>" required>
<label>Brief information about the event</label>
<textarea name="event_info" required><?php echo htmlspecialchars($e['event_info']); ?></textarea>
<button type="submit" class="btn">Save changes</button>
<a href="events.php" class="btn gold">Cancel</a>
</form>
</div>
<?php include '../includes/footer.php'; ?>
