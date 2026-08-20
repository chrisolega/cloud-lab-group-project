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

    $q = $db->prepare(
        'UPDATE events SET school_id = ?, building_name = ?, building_location = ?, building_image = ?, event_info = ? WHERE id = ?'
    );

    $q->execute(array($sid, $bname, $bloc, $img, $info, $id));

    header('Location: events.php');
    exit;
}

include '../includes/header.php';
?>

<style>
.edit-box {
    max-width: 700px;
    margin: 20px auto;
}

.edit-box img {
    width: 100%;
    max-height: 350px;
    object-fit: cover;
    border-radius: 10px;
    margin-bottom: 15px;
}

.theme-btn {
    float: right;
    padding: 8px 12px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
}

.dark {
    background: #111827;
    color: white;
}

.dark .card {
    background: #1f2937;
    color: white;
}

.dark input,
.dark select,
.dark textarea {
    background: #374151;
    color: white;
    border-color: #4b5563;
}

.char-count {
    text-align: right;
    font-size: 12px;
    color: #777;
    margin-top: 4px;
}
</style>

<div class="edit-box">

    <button type="button" class="theme-btn" onclick="toggleTheme()">
        🌙 Dark / ☀️ Light
    </button>

    <div class="card narrow">

        <h1>Edit Event</h1>

        <!-- Image Preview -->
        <img
            id="imagePreview"
            src="<?php echo htmlspecialchars(pic($e['building_image'])); ?>"
            alt="Building image"
        >

        <form method="post" enctype="multipart/form-data">

            <label>School</label>

            <select name="school_id">
                <?php foreach ($schools as $s) { ?>

                    <option
                        value="<?php echo $s['id']; ?>"
                        <?php if ($e['school_id'] == $s['id']) echo 'selected'; ?>
                    >
                        <?php echo htmlspecialchars($s['name']); ?>
                    </option>

                <?php } ?>
            </select>


            <label>Change building image (optional)</label>

            <input
                type="file"
                name="building_image"
                id="imageInput"
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
                id="eventInfo"
                maxlength="500"
                required
            ><?php echo htmlspecialchars($e['event_info']); ?></textarea>

            <div class="char-count">
                <span id="charCount">0</span>/500 characters
            </div>


            <button type="submit" class="btn">
                💾 Save changes
            </button>

            <a href="events.php" class="btn gold">
                Cancel
            </a>

        </form>

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
document.getElementById('imageInput').addEventListener('change', function () {

    const file = this.files[0];

    if (file) {
        document.getElementById('imagePreview').src =
            URL.createObjectURL(file);
    }

});


/* Character Counter */
const eventInfo = document.getElementById('eventInfo');
const charCount = document.getElementById('charCount');

function updateCount() {
    charCount.textContent = eventInfo.value.length;
}

eventInfo.addEventListener('input', updateCount);

updateCount();

</script>

<?php include '../includes/footer.php'; ?>
