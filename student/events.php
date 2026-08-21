<?php

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/upload.php';

need_student();

$base = '../';
$title = 'Campus Events';

$sid = intval($_SESSION['student_school']);


/*
|--------------------------------------------------------------------------
| GET SCHOOL
|--------------------------------------------------------------------------
*/

$q = $db->prepare(
    'SELECT *
     FROM schools
     WHERE id = ?
     LIMIT 1'
);

$q->execute(array($sid));

$school = $q->fetch(PDO::FETCH_ASSOC);

if (!$school) {
    session_destroy();
    header('Location: ../index.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| SEARCH
|--------------------------------------------------------------------------
*/

$search = isset($_GET['search'])
    ? trim($_GET['search'])
    : '';

/*
| Prevent excessively long searches.
*/

if (strlen($search) > 100) {
    $search = substr($search, 0, 100);
}


/*
|--------------------------------------------------------------------------
| PAGINATION
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
| COUNT EVENTS
|--------------------------------------------------------------------------
*/

if ($search !== '') {

    $searchTerm = '%' . $search . '%';

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


/*
|--------------------------------------------------------------------------
| PAGINATION CALCULATION
|--------------------------------------------------------------------------
*/

$totalPages = max(
    1,
    (int) ceil($totalEvents / $perPage)
);

if ($page > $totalPages) {
    $page = $totalPages;
}

$offset = ($page - 1) * $perPage;


/*
|--------------------------------------------------------------------------
| GET EVENTS
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

    $q->bindValue(
        1,
        $sid,
        PDO::PARAM_INT
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
        $sid,
        PDO::PARAM_INT
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
| STATISTICS
|--------------------------------------------------------------------------
*/

/*
| Total events on campus.
*/

$allEventsQ = $db->prepare(
    'SELECT COUNT(*)
     FROM events
     WHERE school_id = ?'
);

$allEventsQ->execute(array($sid));

$allEvents = intval(
    $allEventsQ->fetchColumn()
);


/*
| Latest event.
*/

$latestQ = $db->prepare(
    'SELECT building_name, created_at
     FROM events
     WHERE school_id = ?
     ORDER BY created_at DESC
     LIMIT 1'
);

$latestQ->execute(array($sid));

$latestEvent = $latestQ->fetch(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| DISPLAY INFORMATION
|--------------------------------------------------------------------------
*/

$showingFrom = $totalEvents > 0
    ? $offset + 1
    : 0;

$showingTo = min(
    $offset + $perPage,
    $totalEvents
);


/*
|--------------------------------------------------------------------------
| PAGE URL HELPER
|--------------------------------------------------------------------------
*/

function event_page_url($page, $search)
{
    $params = array(
        'page' => $page
    );

    if ($search !== '') {
        $params['search'] = $search;
    }

    return 'events.php?' . http_build_query($params);
}


include '../includes/header.php';

?>


<!-- =========================================================
     PAGE HEADER
========================================================= -->

<div class="card">

    <h1>
        Campus Events
    </h1>

    <p class="lead">

        Events at
        <strong>
            <?php
            echo htmlspecialchars(
                $school['short_name']
            );
            ?>
        </strong>

        — stay updated with what's happening on campus.

    </p>

</div>


<!-- =========================================================
     EVENT STATISTICS
========================================================= -->

<div class="grid6">

    <div class="card">

        <h3>
            Total Events
        </h3>

        <p style="font-size:28px;font-weight:bold;margin:5px 0;">

            <?php
            echo number_format($allEvents);
            ?>

        </p>

        <p class="lead">
            Events posted by your school
        </p>

    </div>


    <div class="card">

        <h3>
            Showing
        </h3>

        <p style="font-size:28px;font-weight:bold;margin:5px 0;">

            <?php
            echo number_format($totalEvents);
            ?>

        </p>

        <p class="lead">

            <?php if ($search !== '') { ?>

                Matching your search

            <?php } else { ?>

                Available events

            <?php } ?>

        </p>

    </div>


    <div class="card">

        <h3>
            Latest Update
        </h3>

        <?php if ($latestEvent) { ?>

            <p style="font-weight:bold;margin:5px 0;">

                <?php
                echo htmlspecialchars(
                    $latestEvent['building_name']
                );
                ?>

            </p>

            <?php if (!empty($latestEvent['created_at'])) { ?>

                <p class="lead">

                    <?php
                    echo htmlspecialchars(
                        date(
                            'M d, Y',
                            strtotime(
                                $latestEvent['created_at']
                            )
                        )
                    );
                    ?>

                </p>

            <?php } ?>

        <?php } else { ?>

            <p class="lead">
                No events yet.
            </p>

        <?php } ?>

    </div>

</div>


<!-- =========================================================
     SEARCH
========================================================= -->

<div class="card">

    <h2>
        Find an Event
    </h2>

    <p class="lead">
        Search by event information, building name or location.
    </p>

    <form method="get">

        <input
            type="text"
            name="search"
            value="<?php
                echo htmlspecialchars($search);
            ?>"
            maxlength="100"
            placeholder="e.g. library, seminar, administration..."
        >

        <button
            type="submit"
            class="btn"
        >
            🔍 Search Events
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
     SEARCH RESULT MESSAGE
========================================================= -->

<?php if ($search !== '') { ?>

    <div class="card">

        <p>

            Search results for:

            <strong>
                "<?php
                echo htmlspecialchars($search);
                ?>"
            </strong>

            —

            <?php
            echo number_format($totalEvents);
            ?>

            result<?php echo $totalEvents == 1 ? '' : 's'; ?>

        </p>

    </div>

<?php } ?>


<!-- =========================================================
     EVENTS
========================================================= -->

<?php if (count($events) === 0) { ?>

    <div class="msg ok">

        <?php if ($search !== '') { ?>

            <strong>
                No events found.
            </strong>

            <br>

            Try searching with another building name,
            location or keyword.

        <?php } else { ?>

            <strong>
                No events have been posted yet.
            </strong>

            <br>

            Check back later for new campus announcements.

        <?php } ?>

    </div>


<?php } else { ?>


<div class="event-grid">


<?php foreach ($events as $index => $e) { ?>


    <div class="event-card">


        <!-- =================================================
             IMAGE
        ================================================== -->

        <?php

        $image = pic(
            $e['building_image']
        );

        ?>


        <img
            src="<?php
                echo htmlspecialchars($image);
            ?>"
            alt="<?php
                echo htmlspecialchars(
                    $e['building_name']
                );
            ?>"
            loading="lazy"
        >


        <!-- =================================================
             EVENT BODY
        ================================================== -->

        <div class="body">


            <!-- NEW BADGE -->

            <?php

            $isNew = false;

            if (!empty($e['created_at'])) {

                $eventTime = strtotime(
                    $e['created_at']
                );

                /*
                | Event is considered new for 7 days.
                */

                if (
                    $eventTime !== false &&
                    $eventTime >= strtotime('-7 days')
                ) {

                    $isNew = true;
                }
            }

            ?>


            <?php if ($isNew) { ?>

                <span
                    style="
                        display:inline-block;
                        padding:5px 9px;
                        border-radius:20px;
                        font-size:12px;
                        font-weight:bold;
                        margin-bottom:8px;
                    "
                >
                    NEW
                </span>

            <?php } ?>


            <!-- BUILDING -->

            <h3>

                <?php
                echo htmlspecialchars(
                    $e['building_name']
                );
                ?>

            </h3>


            <!-- LOCATION -->

            <div class="loc">

                📍

                <?php
                echo htmlspecialchars(
                    $e['building_location']
                );
                ?>

            </div>


            <!-- EVENT INFORMATION -->

            <p>

                <?php
                echo nl2br(
                    htmlspecialchars(
                        $e['event_info']
                    )
                );
                ?>

            </p>


            <!-- DATE -->

            <?php if (!empty($e['created_at'])) { ?>

                <div
                    class="loc"
                    style="margin-top:14px;"
                >

                    🕒 Posted:

                    <?php

                    $formattedDate = date(
                        'M d, Y',
                        strtotime(
                            $e['created_at']
                        )
                    );

                    echo htmlspecialchars(
                        $formattedDate
                    );

                    ?>

                </div>

            <?php } ?>


            <!-- EVENT NUMBER -->

            <div
                class="loc"
                style="margin-top:6px;font-size:12px;"
            >

                Event #<?php
                echo intval(
                    $offset + $index + 1
                );
                ?>

            </div>


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


    <h3>
        Browse Events
    </h3>


    <p>

        Showing

        <strong>
            <?php
            echo $showingFrom;
            ?>
        </strong>

        -

        <strong>
            <?php
            echo $showingTo;
            ?>
        </strong>

        of

        <strong>
            <?php
            echo $totalEvents;
            ?>
        </strong>

        events.

    </p>


    <div
        style="
            display:flex;
            gap:7px;
            flex-wrap:wrap;
            align-items:center;
        "
    >


        <!-- PREVIOUS -->

        <?php if ($page > 1) { ?>

            <a
                class="btn small gold"
                href="<?php
                    echo htmlspecialchars(
                        event_page_url(
                            $page - 1,
                            $search
                        )
                    );
                ?>"
            >
                ← Previous
            </a>

        <?php } ?>


        <!-- PAGE NUMBERS -->

        <?php

        $startPage = max(
            1,
            $page - 3
        );

        $endPage = min(
            $totalPages,
            $page + 3
        );


        /*
        | First page.
        */

        if ($startPage > 1) {

        ?>

            <a
                class="btn small gold"
                href="<?php
                    echo htmlspecialchars(
                        event_page_url(
                            1,
                            $search
                        )
                    );
                ?>"
            >
                1
            </a>

            <?php if ($startPage > 2) { ?>

                <span>
                    ...
                </span>

            <?php } ?>

        <?php } ?>


        <?php

        for (
            $i = $startPage;
            $i <= $endPage;
            $i++
        ) {

            if ($i == $page) {

        ?>

            <span class="btn small">

                <?php
                echo $i;
                ?>

            </span>

        <?php

            } else {

        ?>

            <a
                class="btn small gold"
                href="<?php
                    echo htmlspecialchars(
                        event_page_url(
                            $i,
                            $search
                        )
                    );
                ?>"
            >

                <?php
                echo $i;
                ?>

            </a>

        <?php

            }

        }

        ?>


        <!-- LAST PAGE -->

        <?php if ($endPage < $totalPages) { ?>

            <?php if ($endPage < $totalPages - 1) { ?>

                <span>
                    ...
                </span>

            <?php } ?>


            <a
                class="btn small gold"
                href="<?php
                    echo htmlspecialchars(
                        event_page_url(
                            $totalPages,
                            $search
                        )
                    );
                ?>"
            >

                <?php
                echo $totalPages;
                ?>

            </a>

        <?php } ?>


        <!-- NEXT -->

        <?php if ($page < $totalPages) { ?>

            <a
                class="btn small gold"
                href="<?php
                    echo htmlspecialchars(
                        event_page_url(
                            $page + 1,
                            $search
                        )
                    );
                ?>"
            >
                Next →
            </a>

        <?php } ?>


    </div>

</div>


<?php } ?>


<?php } ?>


<!-- =========================================================
     STUDENT TIP
========================================================= -->

<div class="card">

    <h2>
        💡 Student Notice
    </h2>

    <p class="lead">

        Check this page regularly for new campus events,
        announcements and important locations.

    </p>

</div>


<?php include '../includes/footer.php'; ?>
