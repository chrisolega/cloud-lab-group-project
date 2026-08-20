<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/upload.php';

need_student();

$base = '../';
$title = 'Events';

$sid = $_SESSION['student_school'];

/*
|--------------------------------------------------------------------------
| Get School
|--------------------------------------------------------------------------
*/
$q = $db->prepare(
    'SELECT * FROM schools WHERE id = ?'
);

$q->execute(array($sid));

$school = $q->fetch();

/*
|--------------------------------------------------------------------------
| Search
|--------------------------------------------------------------------------
*/
$search = '';

if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
}

/*
|--------------------------------------------------------------------------
| Pagination
|--------------------------------------------------------------------------
*/
$perPage = 6;

$page = isset($_GET['page'])
    ? intval($_GET['page'])
    : 1;

if ($page < 1) {
    $page = 1;
}


/*
|--------------------------------------------------------------------------
| Count Events
|--------------------------------------------------------------------------
*/
if ($search !== '') {

    $countQ = $db->prepare(
        'SELECT COUNT(*)
         FROM events
         WHERE school_id = ?
         AND (
             building_name LIKE ?
             OR building_location LIKE ?
             OR event_info LIKE ?
         )'
    );

    $searchTerm = '%' . $search . '%';

    $countQ->execute(array(
        $sid,
        $searchTerm,
        $searchTerm,
        $searchTerm
    ));

} else {

    $countQ = $db->prepare(
        'SELECT COUNT(*)
         FROM events
         WHERE school_id = ?'
    );

    $countQ->execute(array($sid));
}

$totalEvents = intval(
    $countQ->fetchColumn()
);

$totalPages = max(
    1,
    ceil($totalEvents / $perPage)
);

if ($page > $totalPages) {
    $page = $totalPages;
}

$offset = ($page - 1) * $perPage;


/*
|--------------------------------------------------------------------------
| Get Events
|--------------------------------------------------------------------------
*/
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
         LIMIT ? OFFSET ?'
    );

    $searchTerm = '%' . $search . '%';

    $q->bindValue(
        1,
        $sid
    );

    $q->bindValue(
        2,
        $searchTerm,
        PDO::PARAM_STR
    );

    $q->bindValue(
        3,
        $searchTerm,
        PDO::PARAM_STR
    );

    $q->bindValue(
        4,
        $searchTerm,
        PDO::PARAM_STR
    );

    $q->bindValue(
        5,
        $perPage,
        PDO::PARAM_INT
    );

    $q->bindValue(
        6,
        $offset,
        PDO::PARAM_INT
    );

    $q->execute();

} else {

    $q = $db->prepare(
        'SELECT *
         FROM events
         WHERE school_id = ?
         ORDER BY created_at DESC
         LIMIT ? OFFSET ?'
    );

    $q->bindValue(
        1,
        $sid
    );

    $q->bindValue(
        2,
        $perPage,
        PDO::PARAM_INT
    );

    $q->bindValue(
        3,
        $offset,
        PDO::PARAM_INT
    );

    $q->execute();
}

$events = $q->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Display Information
|--------------------------------------------------------------------------
*/
$showingFrom = $totalEvents > 0
    ? $offset + 1
    : 0;

$showingTo = min(
    $offset + $perPage,
    $totalEvents
);

include '../includes/header.php';
?>

<h1>
    Events at
    <?php echo htmlspecialchars($school['short_name']); ?>
</h1>

<p class="lead">
    Here is everything happening on campus and the buildings
    where you can find them.
</p>


<!-- =========================================================
     EVENT STATISTICS
========================================================= -->

<div class="card">

    <h2>Campus Events</h2>

    <p>
        <strong>
            <?php echo number_format($totalEvents); ?>
        </strong>
        event<?php echo $totalEvents == 1 ? '' : 's'; ?>

        <?php if ($search !== '') { ?>

            found for
            "<strong><?php echo htmlspecialchars($search); ?></strong>"

        <?php } ?>

    </p>

</div>


<!-- =========================================================
     SEARCH
========================================================= -->

<div class="card">

    <h2>Find an Event</h2>

    <form method="get">

        <input
            type="text"
            name="search"
            value="<?php echo htmlspecialchars($search); ?>"
            placeholder="Search event, building or location..."
        >

        <button
            type="submit"
            class="btn"
        >
            Search
        </button>

        <?php if ($search !== '') { ?>

            <a
                href="events.php"
                class="btn gold"
            >
                Clear Search
            </a>

        <?php } ?>

    </form>

</div>


<!-- =========================================================
     EVENTS
========================================================= -->

<?php if (count($events) === 0) { ?>

    <div class="msg ok">

        <?php if ($search !== '') { ?>

            No events were found matching
            "<strong><?php echo htmlspecialchars($search); ?></strong>".

        <?php } else { ?>

            No events have been posted yet.
            Check back soon.

        <?php } ?>

    </div>

<?php } else { ?>


<div class="event-grid">

<?php foreach ($events as $e) { ?>

    <div class="event-card">

        <!-- Event / Building Image -->

        <?php
        $image = pic($e['building_image']);
        ?>

        <img
            src="<?php echo htmlspecialchars($image); ?>"
            alt="<?php echo htmlspecialchars($e['building_name']); ?>"
            loading="lazy"
        >


        <div class="body">

            <!-- Building Name -->

            <h3>
                <?php echo htmlspecialchars($e['building_name']); ?>
            </h3>


            <!-- Location -->

            <div class="loc">
                📍
                <?php echo htmlspecialchars($e['building_location']); ?>
            </div>


            <!-- Event Information -->

            <p>
                <?php
                echo nl2br(
                    htmlspecialchars($e['event_info'])
                );
                ?>
            </p>


            <!-- Date Posted -->

            <?php if (!empty($e['created_at'])) { ?>

                <div
                    class="loc"
                    style="margin-top:12px;"
                >
                    🕒 Posted:
                    <?php
                    echo htmlspecialchars(
                        date(
                            'M d, Y',
                            strtotime($e['created_at'])
                        )
                    );
                    ?>
                </div>

            <?php } ?>

        </div>

    </div>

<?php } ?>

</div>


<!-- =========================================================
     PAGINATION
========================================================= -->

<?php if ($totalPages > 1) { ?>

<div
    class="card"
    style="margin-top:25px;"
>

    <p>
        Showing
        <?php echo $showingFrom; ?>
        -
        <?php echo $showingTo; ?>
        of
        <?php echo $totalEvents; ?>
        events
    </p>


    <?php if ($page > 1) { ?>

        <a
            class="btn small gold"
            href="events.php?search=<?php echo urlencode($search); ?>&page=<?php echo $page - 1; ?>"
        >
            ← Previous
        </a>

    <?php } ?>


    <?php

    /*
    |--------------------------------------------------------------------------
    | Page Numbers
    |--------------------------------------------------------------------------
    */

    $startPage = max(
        1,
        $page - 3
    );

    $endPage = min(
        $totalPages,
        $page + 3
    );

    for (
        $i = $startPage;
        $i <= $endPage;
        $i++
    ) {

        if ($i == $page) {

    ?>

        <span class="btn small">
            <?php echo $i; ?>
        </span>

    <?php

        } else {

    ?>

        <a
            class="btn small gold"
            href="events.php?search=<?php echo urlencode($search); ?>&page=<?php echo $i; ?>"
        >
            <?php echo $i; ?>
        </a>

    <?php

        }
    }

    ?>


    <?php if ($page < $totalPages) { ?>

        <a
            class="btn small gold"
            href="events.php?search=<?php echo urlencode($search); ?>&page=<?php echo $page + 1; ?>"
        >
            Next →
        </a>

    <?php } ?>

</div>

<?php } ?>


<?php } ?>


<?php include '../includes/footer.php'; ?>
