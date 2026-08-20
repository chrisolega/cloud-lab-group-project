<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/upload.php';

need_manager();

$base = '../';
$title = 'Edit Event';
$sid = $_SESSION['manager_school'];

$id = intval($_GET['id']);

$q = $db->prepare(
    'SELECT * FROM events WHERE id = ? AND school_id = ?'
);

$q->execute(array($id, $sid));

$e = $q->fetch();

if (!$e) {
    header('Location: dashboard.php');
    exit;
}

$bad = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $bname = trim($_POST['building_name']);
    $bloc = trim($_POST['building_location']);
    $info = trim($_POST['event_info']);

    if ($bname == '' || $bloc == '' || $info == '') {

        $bad = 'Please fill in all the fields.';

    } else {

        $img = $e['building_image'];

        /* Change image if a new one was uploaded */
        if (
            isset($_FILES['building_image']) &&
            $_FILES['building_image']['error'] == 0
        ) {

            $new = save_image($_FILES['building_image']);

            if ($new) {
                $img = $new;
            } else {
                $bad = 'That image was not valid, keeping the old one.';
            }
        }

        /* Update event */
        $q = $db->prepare(
            'UPDATE events
             SET building_name = ?,
                 building_location = ?,
                 building_image = ?,
                 event_info = ?
             WHERE id = ? AND school_id = ?'
        );

        $q->execute(array(
            $bname,
            $bloc,
            $img,
            $info,
            $id,
            $sid
        ));

        if ($bad == '') {
            header('Location: dashboard.php');
            exit;
        }

        /* Keep entered values if there is an error */
        $e['building_name'] = $bname;
        $e['building_location'] = $bloc;
        $e['building_image'] = $img;
        $e['event_info'] = $info;
    }
}

include '../includes/header.php';
?>

<div class="card narrow">

    <h1>Edit Event</h1>

    <?php if ($bad) { ?>

        <div class="msg bad">
            <?php echo htmlspecialchars($bad); ?>
        </div>

    <?php } ?>


    <img
        src="<?php echo htmlspecialchars(pic($e['building_image'])); ?>"
        style="width:100%;border-radius:8px"
        alt=""
    >


    <form method="post" enctype="multipart/form-data">

        <label>Change building image (optional)</label>

        <input
            type="file"
            name="building_image"
            accept="image/*"
        >


        <label>Building name</label>

        <input
            type="text"
            name="building_name"
            value="<?php echo htmlspecialchars($e['building_name']); ?>"
            required
        >


        <label>Location of the building</label>

        <input
            type="text"
            name="building_location"
            value="<?php echo htmlspecialchars($e['building_location']); ?>"
            required
        >


        <label>Brief information about the event</label>

        <textarea
            name="event_info"
            required
        ><?php echo htmlspecialchars($e['event_info']); ?></textarea>


        <button type="submit" class="btn">
            Save Changes
        </button>

        <a
            href="dashboard.php"
            class="btn gold"
        >
            Cancel
        </a>

    </form>

</div><?php include '../includes/footer.php'; ?>
