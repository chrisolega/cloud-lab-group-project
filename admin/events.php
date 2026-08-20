<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/upload.php';

need_admin();

$base = '../';
$title = 'Manage Events';

$msg = '';
$bad = '';

/* Add Event */
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $bname = trim($_POST['building_name']);
    $bloc = trim($_POST['building_location']);
    $info = trim($_POST['event_info']);
    $sid = intval($_POST['school_id']);

    $img = save_image($_FILES['building_image']);

    if ($img && $bname != '' && $bloc != '' && $info != '') {

        $q = $db->prepare(
            'INSERT INTO events
            (school_id, building_name, building_location, building_image, event_info)
            VALUES (?, ?, ?, ?, ?)'
        );

        $q->execute(array($sid, $bname, $bloc, $img, $info));

        $msg = 'Event added successfully.';

    } else {

        $bad = 'Please fill every field and upload a valid image.';
    }
}


/* Delete Event */
if (isset($_GET['del'])) {

    $q = $db->prepare('DELETE FROM events WHERE id = ?');

    $q->execute(array(intval($_GET['del'])));

    $msg = 'Event deleted successfully.';
}


/* School Filter */
$filter = isset($_GET['school'])
    ? intval($_GET['school'])
    : 0;

$schools = $db->query(
    'SELECT * FROM schools ORDER BY id'
)->fetchAll();


if ($filter > 0) {

    $q = $db->prepare(
        'SELECT e.*, s.short_name
         FROM events e
         JOIN schools s ON s.id = e.school_id
         WHERE e.school_id = ?
         ORDER BY e.created_at DESC'
    );

    $q->execute(array($filter));

} else {

    $q = $db->query(
        'SELECT e.*, s.short_name
         FROM events e
         JOIN schools s ON s.id = e.school_id
         ORDER BY e.created_at DESC'
    );
}

$events = $q->fetchAll();

include '../includes/header.php';
?>

<style>

/* Page */

.events-page {
    padding: 20px;
}

/* Header */

.events-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    gap: 15px;
    flex-wrap: wrap;
}

.events-header h1 {
    margin: 0;
}

.theme-btn {
    border: none;
    padding: 9px 13px;
    border-radius: 8px;
    cursor: pointer;
}

/* Add Event */

.add-event {
    margin-bottom: 20px;
}

.add-event img {
    display: none;
    width: 150px;
    height: 100px;
    object-fit: cover;
    border-radius: 8px;
    margin-top: 10px;
}

/* Search */

.event-tools {
    display: flex;
    gap: 12px;
    margin-bottom: 18px;
    flex-wrap: wrap;
}

.event-search {
    flex: 1;
    min-width: 200px;
}

.event-search input {
    width: 100%;
    box-sizing: border-box;
}

/* Cards */

.event-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 18px;
}

.event-card {
    transition: transform .2s ease, box-shadow .2s ease;
}

.event-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 20px rgba(0,0,0,.12);
}

.event-card img {
    height: 190px;
    object-fit: cover;
}

/* Dark Mode */

body.dark {
    background: #111827;
    color: white;
}

body.dark .card,
body.dark .event-card {
    background: #1f2937;
    color: white;
}

body.dark input,
body.dark select,
body.dark textarea {
    background: #374151;
    color: white;
    border-color: #4b5563;
}

/* Mobile */

@media(max-width:600px) {

    .events-page {
        padding: 10px;
    }

    .event-grid {
        grid-template-columns: 1fr;
    }

}

</style>


<div class="events-page">

    <div class="events-header">

        <div>
            <h1>Events</h1>
            <p>Manage events across all schools.</p>
        </div>

        <button
            type="button"
            class="theme-btn"
            onclick="toggleTheme()"
        >
            🌙 / ☀️ Theme
        </button>

    </div>


    <!-- Messages -->

    <?php if ($msg) { ?>

        <div class="msg ok">
            <?php echo htmlspecialchars($msg); ?>
        </div>

    <?php } ?>


    <?php if ($bad) { ?>

        <div class="msg bad">
            <?php echo htmlspecialchars($bad); ?>
        </div>

    <?php } ?>


    <!-- ADD EVENT -->

    <div class="card add-event">

        <h2>Add an Event</h2>

        <form
            method="post"
            enctype="multipart/form-data"
            id="eventForm"
        >

            <label>School</label>

            <select name="school_id" required>

                <?php foreach ($schools as $s) { ?>

                    <option value="<?php echo $s['id']; ?>">
                        <?php echo htmlspecialchars($s['name']); ?>
                    </option>

                <?php } ?>

            </select>


            <label>Image of the building</label>

            <input
                type="file"
                name="building_image"
                id="imageInput"
                accept="image/*"
                required
            >

            <img id="imagePreview" alt="Image preview">


            <label>Building name</label>

            <input
                type="text"
                name="building_name"
                placeholder="Enter building name"
                required
            >


            <label>Location of the building</label>

            <input
                type="text"
                name="building_location"
                placeholder="Enter building location"
                required
            >


            <label>Brief information about the event</label>

            <textarea
                name="event_info"
                placeholder="Enter event information"
                required
            ></textarea>


            <button type="submit" class="btn">
                ➕ Add Event
            </button>

        </form>

    </div>


    <!-- SEARCH + FILTER -->

    <div class="event-tools">

        <div class="event-search">

            <input
                type="text"
                id="eventSearch"
                placeholder="🔍 Search events..."
                onkeyup="searchEvents()"
            >

        </div>


        <form method="get">

            <select
                name="school"
                onchange="this.form.submit()"
            >

                <option value="0">
                    All schools
                </option>

                <?php foreach ($schools as $s) { ?>

                    <option
                        value="<?php echo $s['id']; ?>"
                        <?php
                        if ($filter == $s['id']) {
                            echo 'selected';
                        }
                        ?>
                    >
                        <?php echo htmlspecialchars($s['name']); ?>
                    </option>

                <?php } ?>

            </select>

        </form>

    </div>


    <!-- EVENTS -->

    <div class="event-grid" id="eventGrid">

        <?php if (count($events) > 0) { ?>

            <?php foreach ($events as $e) { ?>

                <div class="event-card">

                    <img
                        src="<?php echo htmlspecialchars(
                            pic($e['building_image'])
                        ); ?>"
                        alt="Event building"
                    >

                    <div class="body">

                        <h3>
                            <?php echo htmlspecialchars(
                                $e['building_name']
                            ); ?>

                            <small>
                                (<?php echo htmlspecialchars(
                                    $e['short_name']
                                ); ?>)
                            </small>
                        </h3>


                        <div class="loc">
                            📍
                            <?php echo htmlspecialchars(
                                $e['building_location']
                            ); ?>
                        </div>


                        <p>
                            <?php echo nl2br(
                                htmlspecialchars($e['event_info'])
                            ); ?>
                        </p>


                        <p class="actions">

                            <a
                                class="btn small gold"
                                href="edit_event.php?id=<?php echo $e['id']; ?>"
                            >
                                Edit
                            </a>


                            <a
                                class="btn small red"
                                href="events.php?del=<?php echo $e['id']; ?>"
                                onclick="return confirm('Are you sure you want to delete this event?');"
                            >
                                Delete
                            </a>

                        </p>

                    </div>

                </div>

            <?php } ?>

        <?php } else { ?>

            <p>
                No events found.
            </p>

        <?php } ?>

    </div>

</div>


<script>

/* Dark / Light Mode */

function toggleTheme() {

    document.body.classList.toggle('dark');

    if (document.body.classList.contains('dark')) {

        localStorage.setItem('theme', 'dark');

    } else {

        localStorage.setItem('theme', 'light');

    }
}


/* Remember Theme */

if (localStorage.getItem('theme') === 'dark') {

    document.body.classList.add('dark');

}


/* Image Preview */

document.getElementById('imageInput')
.addEventListener('change', function () {

    const file = this.files[0];

    const preview =
        document.getElementById('imagePreview');

    if (file) {

        preview.src =
            URL.createObjectURL(file);

        preview.style.display = 'block';

    }

});


/* Search Events */

function searchEvents() {

    const search =
        document.getElementById('eventSearch')
        .value
        .toLowerCase();

    const cards =
        document.querySelectorAll('.event-card');

    cards.forEach(function(card) {

        const text =
            card.textContent.toLowerCase();

        if (text.includes(search)) {

            card.style.display = '';

        } else {

            card.style.display = 'none';

        }

    });

}

</script>


<?php include '../includes/footer.php'; ?></div>
<?php include '../includes/footer.php'; ?>
