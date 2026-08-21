<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/upload.php';

need_manager();

$base = '../';
$title = 'Edit Event';
$sid = intval($_SESSION['manager_school']);

$bad = '';
$msg = '';

/* -------------------------------------------------
   CSRF TOKEN
------------------------------------------------- */

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrf_token = $_SESSION['csrf_token'];


/* -------------------------------------------------
   GET EVENT ID
------------------------------------------------- */

$id = isset($_GET['id'])
    ? intval($_GET['id'])
    : 0;

if ($id <= 0) {
    header('Location: dashboard.php');
    exit;
}


/* -------------------------------------------------
   GET EVENT
------------------------------------------------- */

$q = $db->prepare(
    'SELECT *
     FROM events
     WHERE id = ?
     AND school_id = ?'
);

$q->execute(array(
    $id,
    $sid
));

$e = $q->fetch();


if (!$e) {
    header('Location: dashboard.php');
    exit;
}


/* -------------------------------------------------
   UPDATE EVENT
------------------------------------------------- */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = isset($_POST['csrf_token'])
        ? $_POST['csrf_token']
        : '';

    /* Security check */

    if (!hash_equals($csrf_token, $token)) {

        $bad = 'Security check failed. Please refresh the page and try again.';

    } else {

        $bname = isset($_POST['building_name'])
            ? trim($_POST['building_name'])
            : '';

        $bloc = isset($_POST['building_location'])
            ? trim($_POST['building_location'])
            : '';

        $info = isset($_POST['event_info'])
            ? trim($_POST['event_info'])
            : '';


        /* -----------------------------------------
           VALIDATE TEXT
        ----------------------------------------- */

        if ($bname === '') {

            $bad = 'Please enter the building name.';

        } elseif (strlen($bname) < 2) {

            $bad = 'Building name is too short.';

        } elseif ($bloc === '') {

            $bad = 'Please enter the building location.';

        } elseif ($info === '') {

            $bad = 'Please enter information about the event.';

        } else {


            /* -----------------------------------------
               CHECK FOR DUPLICATE EVENT
            ----------------------------------------- */

            $duplicate = $db->prepare(
                'SELECT id
                 FROM events
                 WHERE school_id = ?
                 AND building_name = ?
                 AND building_location = ?
                 AND id != ?
                 LIMIT 1'
            );

            $duplicate->execute(array(
                $sid,
                $bname,
                $bloc,
                $id
            ));


            if ($duplicate->fetch()) {

                $bad = 'Another event already exists for this building and location.';

            } else {


                /* -----------------------------------------
                   KEEP CURRENT IMAGE
                ----------------------------------------- */

                $img = $e['building_image'];


                /* -----------------------------------------
                   CHECK NEW IMAGE
                ----------------------------------------- */

                if (
                    isset($_FILES['building_image']) &&
                    $_FILES['building_image']['error'] !== UPLOAD_ERR_NO_FILE
                ) {

                    if (
                        $_FILES['building_image']['error']
                        !== UPLOAD_ERR_OK
                    ) {

                        $bad = 'There was a problem uploading the new image.';

                    } else {

                        $file = $_FILES['building_image'];

                        $max_size = 5 * 1024 * 1024;


                        /* File size */

                        if ($file['size'] > $max_size) {

                            $bad = 'Image is too large. Maximum size is 5MB.';

                        } else {


                            /* Check MIME type */

                            $allowed_types = array(
                                'image/jpeg',
                                'image/png',
                                'image/gif',
                                'image/webp'
                            );

                            $finfo = finfo_open(FILEINFO_MIME_TYPE);

                            $mime = finfo_file(
                                $finfo,
                                $file['tmp_name']
                            );

                            finfo_close($finfo);


                            if (
                                !in_array(
                                    $mime,
                                    $allowed_types
                                )
                            ) {

                                $bad = 'Invalid image type. Use JPG, PNG, GIF or WEBP.';

                            } else {


                                /* Save new image */

                                $new_image = save_image($file);


                                if (!$new_image) {

                                    $bad =
                                        'The new image could not be uploaded. '
                                        . 'Your old image has been kept.';

                                } else {

                                    $img = $new_image;
                                }
                            }
                        }
                    }
                }


                /* -----------------------------------------
                   UPDATE DATABASE
                ----------------------------------------- */

                if ($bad === '') {

                    $q = $db->prepare(
                        'UPDATE events
                         SET building_name = ?,
                             building_location = ?,
                             building_image = ?,
                             event_info = ?
                         WHERE id = ?
                         AND school_id = ?'
                    );

                    $q->execute(array(
                        $bname,
                        $bloc,
                        $img,
                        $info,
                        $id,
                        $sid
                    ));


                    if ($q->rowCount() > 0) {

                        header(
                            'Location: dashboard.php?updated=1'
                        );

                        exit;

                    } else {

                        /*
                         * rowCount can be zero when the user
                         * saves without changing anything.
                         */

                        $msg = 'No changes were made to this event.';
                    }


                    /* Keep values on page */

                    $e['building_name'] = $bname;
                    $e['building_location'] = $bloc;
                    $e['building_image'] = $img;
                    $e['event_info'] = $info;
                }
            }
        }


        /* -----------------------------------------
           KEEP ENTERED VALUES AFTER ERROR
        ----------------------------------------- */

        if ($bad !== '') {

            $e['building_name'] = $bname;
            $e['building_location'] = $bloc;
            $e['event_info'] = $info;
        }
    }
}


include '../includes/header.php';
?>


<style>

/* -----------------------------------------
   EDIT EVENT
----------------------------------------- */

.edit-event-card {
    max-width: 700px;
    margin: 30px auto;
}

.current-image {
    margin: 20px 0;
}

.current-image img {
    width: 100%;
    max-height: 350px;
    object-fit: cover;
    border-radius: 12px;
    display: block;
}

.image-note {
    display: block;
    margin-top: 8px;
    color: #777;
    font-size: 13px;
}

.form-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 20px;
}

.danger-note {
    margin-top: 10px;
    font-size: 13px;
    color: #777;
}


/* -----------------------------------------
   MOBILE
----------------------------------------- */

@media (max-width: 600px) {

    .edit-event-card {
        margin: 15px 0;
    }

    .form-actions {
        flex-direction: column;
    }

    .form-actions .btn {
        width: 100%;
        text-align: center;
    }

}

</style>


<div class="card edit-event-card">


    <h1>Edit Event</h1>

    <p class="lead">
        Update the building information and event details below.
    </p>


    <!-- -------------------------------------
         MESSAGES
    -------------------------------------- -->

    <?php if ($bad !== '') { ?>

        <div class="msg bad">

            <?php
            echo htmlspecialchars($bad);
            ?>

        </div>

    <?php } ?>


    <?php if ($msg !== '') { ?>

        <div class="msg ok">

            <?php
            echo htmlspecialchars($msg);
            ?>

        </div>

    <?php } ?>


    <!-- -------------------------------------
         CURRENT IMAGE
    -------------------------------------- -->

    <?php if (!empty($e['building_image'])) { ?>

        <div class="current-image">

            <label>
                Current Building Image
            </label>

            <img
                src="<?php
                    echo htmlspecialchars(
                        pic($e['building_image'])
                    );
                ?>"
                alt="<?php
                    echo htmlspecialchars(
                        $e['building_name']
                    );
                ?>"
            >

            <span class="image-note">
                Upload a new image below if you want to replace this one.
            </span>

        </div>

    <?php } ?>


    <!-- -------------------------------------
         FORM
    -------------------------------------- -->

    <form
        method="post"
        enctype="multipart/form-data"
    >


        <input
            type="hidden"
            name="csrf_token"
            value="<?php
                echo htmlspecialchars(
                    $csrf_token
                );
            ?>"
        >


        <!-- IMAGE -->

        <label>
            Change building image
            <small>(optional)</small>
        </label>

        <input
            type="file"
            name="building_image"
            accept=".jpg,.jpeg,.png,.gif,.webp,image/*"
        >

        <span class="image-note">
            JPG, PNG, GIF or WEBP. Maximum 5MB.
        </span>


        <!-- BUILDING -->

        <label>
            Building name
        </label>

        <input
            type="text"
            name="building_name"
            value="<?php
                echo htmlspecialchars(
                    $e['building_name']
                );
            ?>"
            maxlength="150"
            placeholder="e.g. Main Administration Block"
            required
        >


        <!-- LOCATION -->

        <label>
            Location of the building
        </label>

        <input
            type="text"
            name="building_location"
            value="<?php
                echo htmlspecialchars(
                    $e['building_location']
                );
            ?>"
            maxlength="255"
            placeholder="e.g. Behind the main library"
            required
        >


        <!-- INFORMATION -->

        <label>
            Event information
        </label>

        <textarea
            name="event_info"
            rows="6"
            maxlength="2000"
            placeholder="Enter information students should know..."
            required
        ><?php
            echo htmlspecialchars(
                $e['event_info']
            );
        ?></textarea>


        <!-- ACTIONS -->

        <div class="form-actions">

            <button
                type="submit"
                class="btn"
            >
                Save Changes
            </button>


            <a
                href="dashboard.php"
                class="btn gold"
            >
                Cancel
            </a>

        </div>


        <p class="danger-note">
            Only events belonging to your school can be edited.
        </p>


    </form>

</div>


<?php include '../includes/footer.php'; ?>
