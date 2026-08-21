<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/upload.php';

need_manager();

$base = '../';
$title = 'Manager Dashboard';
$sid = intval($_SESSION['manager_school']);

$msg = '';
$bad = '';

/* -------------------------------------------------
   CSRF TOKEN
------------------------------------------------- */

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrf_token = $_SESSION['csrf_token'];


/* -------------------------------------------------
   DELETE EVENT
------------------------------------------------- */

if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['delete_event'])) {

    $token = isset($_POST['csrf_token'])
        ? $_POST['csrf_token']
        : '';

    if (!hash_equals($csrf_token, $token)) {

        $bad = 'Security check failed. Please try again.';

    } else {

        $event_id = isset($_POST['event_id'])
            ? intval($_POST['event_id'])
            : 0;

        if ($event_id > 0) {

            $q = $db->prepare(
                'DELETE FROM events
                 WHERE id = ? AND school_id = ?'
            );

            $q->execute(array(
                $event_id,
                $sid
            ));

            if ($q->rowCount() > 0) {
                $msg = 'Event deleted successfully.';
            } else {
                $bad = 'Event not found or you do not have permission to delete it.';
            }

        } else {

            $bad = 'Invalid event selected.';
        }
    }
}


/* -------------------------------------------------
   ADD EVENT
------------------------------------------------- */

if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['add_event'])) {

    $token = isset($_POST['csrf_token'])
        ? $_POST['csrf_token']
        : '';

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


        /* Basic validation */

        if ($bname === '') {

            $bad = 'Please enter the building name.';

        } elseif (strlen($bname) < 2) {

            $bad = 'Building name is too short.';

        } elseif ($bloc === '') {

            $bad = 'Please enter the building location.';

        } elseif ($info === '') {

            $bad = 'Please enter information about the event.';

        } elseif (
            !isset($_FILES['building_image']) ||
            $_FILES['building_image']['error'] !== UPLOAD_ERR_OK
        ) {

            $bad = 'Please upload a valid building image.';

        } else {

            /* Validate uploaded image */

            $file = $_FILES['building_image'];

            $max_size = 5 * 1024 * 1024;

            if ($file['size'] > $max_size) {

                $bad = 'Image is too large. Maximum size is 5MB.';

            } else {

                $allowed_types = array(
                    'image/jpeg',
                    'image/png',
                    'image/gif',
                    'image/webp'
                );

                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);

                if (!in_array($mime, $allowed_types)) {

                    $bad = 'Invalid image type. Use JPG, PNG, GIF or WEBP.';

                } else {

                    /*
                     * Prevent accidental duplicate events.
                     */

                    $duplicate = $db->prepare(
                        'SELECT id
                         FROM events
                         WHERE school_id = ?
                         AND building_name = ?
                         AND building_location = ?
                         LIMIT 1'
                    );

                    $duplicate->execute(array(
                        $sid,
                        $bname,
                        $bloc
                    ));

                    if ($duplicate->fetch()) {

                        $bad = 'An event already exists for this building and location.';

                    } else {

                        /* Save image */

                        $img = save_image($file);

                        if (!$img) {

                            $bad = 'The image could not be uploaded. Please try another image.';

                        } else {

                            /*
                             * Insert event
                             */

                            $q = $db->prepare(
                                'INSERT INTO events
                                (
                                    school_id,
                                    building_name,
                                    building_location,
                                    building_image,
                                    event_info
                                )
                                VALUES (?, ?, ?, ?, ?)'
                            );

                            $q->execute(array(
                                $sid,
                                $bname,
                                $bloc,
                                $img,
                                $info
                            ));

                            $msg = 'Event added successfully. Students can see it right away.';

                            /*
                             * Clear form values after successful submission.
                             */

                            $bname = '';
                            $bloc = '';
                            $info = '';
                        }
                    }
                }
            }
        }
    }
}


/* -------------------------------------------------
   SEARCH
------------------------------------------------- */

$search = isset($_GET['search'])
    ? trim($_GET['search'])
    : '';


/* -------------------------------------------------
   PAGINATION
------------------------------------------------- */

$per_page = 6;

$page = isset($_GET['page'])
    ? intval($_GET['page'])
    : 1;

if ($page < 1) {
    $page = 1;
}


/* -------------------------------------------------
   TOTAL EVENTS
------------------------------------------------- */

$count = $db->prepare(
    'SELECT COUNT(*)
     FROM events
     WHERE school_id = ?'
);

$count->execute(array($sid));

$total_events = intval($count->fetchColumn());


/* -------------------------------------------------
   EVENTS ADDED TODAY
------------------------------------------------- */

$today_count = 0;

try {

    $today = $db->prepare(
        'SELECT COUNT(*)
         FROM events
         WHERE school_id = ?
         AND DATE(created_at) = CURDATE()'
    );

    $today->execute(array($sid));

    $today_count = intval($today->fetchColumn());

} catch (Exception $e) {

    /*
     * If created_at does not exist in the database,
     * keep the dashboard working.
     */

    $today_count = 0;
}


/* -------------------------------------------------
   SEARCH COUNT
------------------------------------------------- */

if ($search !== '') {

    $term = '%' . $search . '%';

    $search_count = $db->prepare(
        'SELECT COUNT(*)
         FROM events
         WHERE school_id = ?
         AND (
             building_name LIKE ?
             OR building_location LIKE ?
             OR event_info LIKE ?
         )'
    );

    $search_count->execute(array(
        $sid,
        $term,
        $term,
        $term
    ));

    $display_count = intval($search_count->fetchColumn());

} else {

    $display_count = $total_events;
}


/* -------------------------------------------------
   PAGINATION TOTAL
------------------------------------------------- */

$total_pages = max(
    1,
    ceil($display_count / $per_page)
);

if ($page > $total_pages) {
    $page = $total_pages;
}

$offset = ($page - 1) * $per_page;


/* -------------------------------------------------
   GET EVENTS
------------------------------------------------- */

if ($search !== '') {

    $q = $db->prepare(
        'SELECT *
         FROM events
         WHERE school_id = ?
         AND (
             building_name LIKE ?
             OR building_location LIKE ?
             OR event_info LIKE ?
         )
         ORDER BY created_at DESC
         LIMIT ' . intval($per_page) . '
         OFFSET ' . intval($offset)
    );

    $q->execute(array(
        $sid,
        $term,
        $term,
        $term
    ));

} else {

    $q = $db->prepare(
        'SELECT *
         FROM events
         WHERE school_id = ?
         ORDER BY created_at DESC
         LIMIT ' . intval($per_page) . '
         OFFSET ' . intval($offset)
    );

    $q->execute(array($sid));
}

$events = $q->fetchAll();


include '../includes/header.php';
?>


<style>

/* -----------------------------------------
   DASHBOARD
----------------------------------------- */

.dashboard-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 18px;
    margin: 25px 0;
}

.stat-card {
    background: #fff;
    border-radius: 12px;
    padding: 22px;
    box-shadow: 0 3px 12px rgba(0,0,0,.08);
    border-left: 5px solid #d4af37;
}

.stat-card h3 {
    margin: 0 0 8px;
    font-size: 15px;
    color: #666;
}

.stat-number {
    font-size: 30px;
    font-weight: bold;
}


/* -----------------------------------------
   EVENT GRID
----------------------------------------- */

.event-grid {
    display: grid;
    grid-template-columns:
        repeat(auto-fit, minmax(280px, 1fr));

    gap: 22px;
    margin-top: 20px;
}

.event-card {
    background: #fff;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0,0,0,.08);
    transition: transform .2s ease,
                box-shadow .2s ease;
}

.event-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 22px rgba(0,0,0,.12);
}

.event-card img {
    width: 100%;
    height: 190px;
    object-fit: cover;
    display: block;
}

.event-card .body {
    padding: 18px;
}

.event-card h3 {
    margin-top: 0;
    margin-bottom: 8px;
}

.event-card .loc {
    font-size: 14px;
    color: #777;
    margin-bottom: 12px;
}

.event-card p {
    line-height: 1.6;
}

.event-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-top: 15px;
}


/* -----------------------------------------
   SEARCH
----------------------------------------- */

.search-form {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.search-form input {
    flex: 1;
    min-width: 220px;
}


/* -----------------------------------------
   PAGINATION
----------------------------------------- */

.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 7px;
    margin: 30px 0;
    flex-wrap: wrap;
}

.pagination a,
.pagination span {
    padding: 9px 13px;
    border-radius: 7px;
    text-decoration: none;
}

.pagination a {
    background: #eee;
    color: #222;
}

.pagination a:hover {
    background: #ddd;
}

.pagination .active {
    background: #d4af37;
    color: #fff;
    font-weight: bold;
}


/* -----------------------------------------
   MOBILE
----------------------------------------- */

@media (max-width: 700px) {

    .dashboard-stats {
        grid-template-columns: 1fr;
    }

    .event-grid {
        grid-template-columns: 1fr;
    }

}

</style>


<h1>Manager Dashboard</h1>

<p class="lead">
    Manage your school's events, buildings and event information
    from one place.
</p>


<!-- ---------------------------------------
     MESSAGES
---------------------------------------- -->

<?php if ($msg !== '') { ?>

    <div class="msg ok">
        <?php echo htmlspecialchars($msg); ?>
    </div>

<?php } ?>


<?php if ($bad !== '') { ?>

    <div class="msg bad">
        <?php echo htmlspecialchars($bad); ?>
    </div>

<?php } ?>


<!-- ---------------------------------------
     DASHBOARD STATISTICS
---------------------------------------- -->

<div class="dashboard-stats">

    <div class="stat-card">

        <h3>Total Events</h3>

        <div class="stat-number">
            <?php echo $total_events; ?>
        </div>

        <small>
            All events in your school
        </small>

    </div>


    <div class="stat-card">

        <h3>Events Today</h3>

        <div class="stat-number">
            <?php echo $today_count; ?>
        </div>

        <small>
            Added today
        </small>

    </div>


    <div class="stat-card">

        <h3>Search Results</h3>

        <div class="stat-number">
            <?php echo $display_count; ?>
        </div>

        <small>
            Currently displayed
        </small>

    </div>

</div>


<!-- ---------------------------------------
     ADD EVENT
---------------------------------------- -->

<div class="card">

    <h2>Add New Event</h2>

    <p>
        Add information about an event and the building
        where students can find it.
    </p>


    <form
        method="post"
        enctype="multipart/form-data"
    >

        <input
            type="hidden"
            name="csrf_token"
            value="<?php echo htmlspecialchars($csrf_token); ?>"
        >

        <input
            type="hidden"
            name="add_event"
            value="1"
        >


        <label>
            Image of the building
        </label>

        <input
            type="file"
            name="building_image"
            accept=".jpg,.jpeg,.png,.gif,.webp,image/*"
            required
        >

        <small>
            JPG, PNG, GIF or WEBP. Maximum 5MB.
        </small>


        <label>
            Building name
        </label>

        <input
            type="text"
            name="building_name"
            value="<?php
                echo isset($bname)
                    ? htmlspecialchars($bname)
                    : '';
            ?>"
            maxlength="150"
            placeholder="e.g. Main Administration Block"
            required
        >


        <label>
            Location of the building
        </label>

        <input
            type="text"
            name="building_location"
            value="<?php
                echo isset($bloc)
                    ? htmlspecialchars($bloc)
                    : '';
            ?>"
            maxlength="255"
            placeholder="e.g. Behind the main library, North Campus"
            required
        >


        <label>
            Event information
        </label>

        <textarea
            name="event_info"
            rows="5"
            maxlength="2000"
            placeholder="Enter information students should know about this event..."
            required
        ><?php
            echo isset($info)
                ? htmlspecialchars($info)
                : '';
        ?></textarea>


        <button
            type="submit"
            class="btn"
        >
            Add Event
        </button>

    </form>

</div>


<!-- ---------------------------------------
     SEARCH
---------------------------------------- -->

<div class="card">

    <h2>Find Events</h2>

    <form
        method="get"
        class="search-form"
    >

        <input
            type="text"
            name="search"
            value="<?php echo htmlspecialchars($search); ?>"
            placeholder="Search by building, location or event..."
        >

        <button
            type="submit"
            class="btn"
        >
            Search
        </button>


        <?php if ($search !== '') { ?>

            <a
                href="dashboard.php"
                class="btn small"
            >
                Clear
            </a>

        <?php } ?>

    </form>

</div>


<!-- ---------------------------------------
     EVENTS
---------------------------------------- -->

<h2>
    Your Events

    <?php if ($search !== '') { ?>

        <small>
            — Search: "<?php echo htmlspecialchars($search); ?>"
        </small>

    <?php } ?>

</h2>


<?php if (count($events) === 0) { ?>

    <div class="card">

        <?php if ($search !== '') { ?>

            <p>
                No events were found for
                <strong>
                    <?php echo htmlspecialchars($search); ?>
                </strong>.
            </p>

            <a
                href="dashboard.php"
                class="btn small"
            >
                View All Events
            </a>

        <?php } else { ?>

            <p>
                You have not added any events yet.
            </p>

        <?php } ?>

    </div>

<?php } else { ?>


<div class="event-grid">

<?php foreach ($events as $e) { ?>

    <div class="event-card">


        <?php if (!empty($e['building_image'])) { ?>

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
                loading="lazy"
            >

        <?php } ?>


        <div class="body">

            <h3>
                <?php
                echo htmlspecialchars(
                    $e['building_name']
                );
                ?>
            </h3>


            <div class="loc">

                📍

                <?php
                echo htmlspecialchars(
                    $e['building_location']
                );
                ?>

            </div>


            <p>
                <?php
                echo nl2br(
                    htmlspecialchars(
                        $e['event_info']
                    )
                );
                ?>
            </p>


            <?php if (isset($e['created_at'])) { ?>

                <small>
                    Added:
                    <?php
                    echo htmlspecialchars(
                        date(
                            'M j, Y g:i A',
                            strtotime($e['created_at'])
                        )
                    );
                    ?>
                </small>

            <?php } ?>


            <div class="event-actions">


                <a
                    class="btn small gold"
                    href="edit_event.php?id=<?php
                        echo intval($e['id']);
                    ?>"
                >
                    Edit
                </a>


                <form
                    method="post"
                    style="display:inline"
                    onsubmit="return confirm(
                        'Are you sure you want to delete this event? This action cannot be undone.'
                    );"
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

                    <input
                        type="hidden"
                        name="event_id"
                        value="<?php
                            echo intval($e['id']);
                        ?>"
                    >

                    <button
                        type="submit"
                        name="delete_event"
                        value="1"
                        class="btn small red"
                    >
                        Delete
                    </button>

                </form>

            </div>

        </div>

    </div>

<?php } ?>

</div>


<!-- ---------------------------------------
     PAGINATION
---------------------------------------- -->

<?php if ($total_pages > 1) { ?>

    <div class="pagination">


        <?php if ($page > 1) { ?>

            <a href="?<?php
                echo http_build_query(array(
                    'search' => $search,
                    'page' => $page - 1
                ));
            ?>">
                Previous
            </a>

        <?php } ?>


        <?php

        for (
            $i = 1;
            $i <= $total_pages;
            $i++
        ) {

        ?>

            <?php if ($i == $page) { ?>

                <span class="active">
                    <?php echo $i; ?>
                </span>

            <?php } else { ?>

                <a href="?<?php
                    echo http_build_query(array(
                        'search' => $search,
                        'page' => $i
                    ));
                ?>">
                    <?php echo $i; ?>
                </a>

            <?php } ?>

        <?php } ?>


        <?php if ($page < $total_pages) { ?>

            <a href="?<?php
                echo http_build_query(array(
                    'search' => $search,
                    'page' => $page + 1
                ));
            ?>">
                Next
            </a>

        <?php } ?>


    </div>

<?php } ?>


<?php } ?>


<?php include '../includes/footer.php'; ?>
