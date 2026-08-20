<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

need_admin();

$base = '../';
$title = 'Manage Managers';
$msg = '';
$bad = '';

/* ADD / EDIT MANAGER */
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    /* ADD MANAGER */
    if (isset($_POST['add'])) {

        $user = trim($_POST['username']);
        $pass = $_POST['password'];
        $sid = intval($_POST['school_id']);

        if ($user != '' && $pass != '') {

            // Check if username already exists
            $q = $db->prepare('SELECT id FROM managers WHERE username = ?');
            $q->execute(array($user));

            if ($q->fetch()) {
                $bad = 'That username already exists.';
            } else {

                $q = $db->prepare(
                    'INSERT INTO managers (username, password, school_id)
                     VALUES (?, ?, ?)'
                );

                $q->execute(array(
                    $user,
                    password_hash($pass, PASSWORD_DEFAULT),
                    $sid
                ));

                $msg = 'Manager account created.';
            }

        } else {
            $bad = 'Username and password are required.';
        }
    }

    /* EDIT MANAGER */
    else if (isset($_POST['edit_id'])) {

        $id = intval($_POST['edit_id']);
        $user = trim($_POST['username']);
        $sid = intval($_POST['school_id']);
        $pass = trim($_POST['password']);

        if ($user == '') {
            $bad = 'Username is required.';
        } else {

            // Check if another manager already uses this username
            $q = $db->prepare(
                'SELECT id FROM managers WHERE username = ? AND id != ?'
            );

            $q->execute(array($user, $id));

            if ($q->fetch()) {

                $bad = 'That username is already being used by another manager.';

            } else {

                if ($pass != '') {

                    $q = $db->prepare(
                        'UPDATE managers
                         SET username = ?, password = ?, school_id = ?
                         WHERE id = ?'
                    );

                    $q->execute(array(
                        $user,
                        password_hash($pass, PASSWORD_DEFAULT),
                        $sid,
                        $id
                    ));

                } else {

                    $q = $db->prepare(
                        'UPDATE managers
                         SET username = ?, school_id = ?
                         WHERE id = ?'
                    );

                    $q->execute(array(
                        $user,
                        $sid,
                        $id
                    ));
                }

                $msg = 'Manager account updated.';
            }
        }
    }

    /* DELETE MANAGER */
    else if (isset($_POST['delete_id'])) {

        $id = intval($_POST['delete_id']);

        $q = $db->prepare('DELETE FROM managers WHERE id = ?');
        $q->execute(array($id));

        $msg = 'Manager account deleted.';
    }
}


/* EDIT MANAGER */
$edit = null;

if (isset($_GET['edit'])) {

    $q = $db->prepare('SELECT * FROM managers WHERE id = ?');
    $q->execute(array(intval($_GET['edit'])));

    $edit = $q->fetch();
}


/* GET SCHOOLS */
$schools = $db->query(
    'SELECT * FROM schools ORDER BY id'
)->fetchAll();


/* SEARCH */
$search = '';

if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
}


/* GET MANAGERS */
if ($search != '') {

    $q = $db->prepare(
        'SELECT m.*, s.short_name
         FROM managers m
         JOIN schools s ON s.id = m.school_id
         WHERE m.username LIKE ?
         OR s.name LIKE ?
         OR s.short_name LIKE ?
         ORDER BY s.id'
    );

    $like = '%' . $search . '%';

    $q->execute(array($like, $like, $like));

} else {

    $q = $db->query(
        'SELECT m.*, s.short_name
         FROM managers m
         JOIN schools s ON s.id = m.school_id
         ORDER BY s.id'
    );
}

$managers = $q->fetchAll();

include '../includes/header.php';
?>

<h1>Manager Accounts</h1>

<p class="lead">
    Create, edit or delete manager accounts for any school.
</p>


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


<!-- ADD / EDIT MANAGER -->
<div class="card">

    <h2>
        <?php echo $edit ? 'Edit manager' : 'Add a manager'; ?>
    </h2>

    <form method="post">

        <?php if ($edit) { ?>

            <input
                type="hidden"
                name="edit_id"
                value="<?php echo $edit['id']; ?>"
            >

        <?php } else { ?>

            <input
                type="hidden"
                name="add"
                value="1"
            >

        <?php } ?>


        <label>Username</label>

        <input
            type="text"
            name="username"
            value="<?php
                echo $edit
                    ? htmlspecialchars($edit['username'])
                    : '';
            ?>"
            required
        >


        <label>
            Password

            <?php
            echo $edit
                ? '(leave empty to keep the current one)'
                : '';
            ?>
        </label>


        <div style="display:flex; gap:8px; align-items:center;">

            <input
                type="password"
                name="password"
                id="managerPassword"
                <?php echo $edit ? '' : 'required'; ?>
                style="flex:1;"
            >

            <button
                type="button"
                class="btn gold"
                onclick="togglePassword()"
            >
                Show
            </button>

        </div>


        <label>School</label>

        <select name="school_id" required>

            <?php foreach ($schools as $s) { ?>

                <option
                    value="<?php echo $s['id']; ?>"

                    <?php
                    if (
                        $edit &&
                        $edit['school_id'] == $s['id']
                    ) {
                        echo 'selected';
                    }
                    ?>
                >

                    <?php
                    echo htmlspecialchars($s['name']);
                    ?>

                </option>

            <?php } ?>

        </select>


        <button type="submit" class="btn">

            <?php
            echo $edit
                ? 'Save changes'
                : 'Create manager';
            ?>

        </button>


        <?php if ($edit) { ?>

            <a
                href="managers.php"
                class="btn gold"
            >
                Cancel
            </a>

        <?php } ?>

    </form>

</div>


<!-- SEARCH -->
<div class="card">

    <h2>Search Managers</h2>

    <form method="get">

        <input
            type="text"
            name="search"
            placeholder="Search username or school..."
            value="<?php echo htmlspecialchars($search); ?>"
        >

        <button type="submit" class="btn">
            Search
        </button>

        <?php if ($search != '') { ?>

            <a
                href="managers.php"
                class="btn gold"
            >
                Clear
            </a>

        <?php } ?>

    </form>

</div>


<h2>
    All managers

    <?php if ($search != '') { ?>

        <small>
            — Search results for
            "<?php echo htmlspecialchars($search); ?>"
        </small>

    <?php } ?>

</h2>


<table>

    <tr>
        <th>Username</th>
        <th>School</th>
        <th>Actions</th>
    </tr>


    <?php if (count($managers) > 0) { ?>

        <?php foreach ($managers as $m) { ?>

            <tr>

                <td>
                    <?php
                    echo htmlspecialchars($m['username']);
                    ?>
                </td>


                <td>
                    <?php
                    echo htmlspecialchars($m['short_name']);
                    ?>
                </td>


                <td>

                    <!-- EDIT -->
                    <a
                        class="btn small gold"
                        href="managers.php?edit=<?php echo $m['id']; ?>"
                    >
                        Edit
                    </a>


                    <!-- DELETE -->
                    <form
                        method="post"
                        style="display:inline;"
                        onsubmit="return confirm('Delete this manager? This action cannot be undone.');"
                    >

                        <input
                            type="hidden"
                            name="delete_id"
                            value="<?php echo $m['id']; ?>"
                        >

                        <button
                            type="submit"
                            class="btn small red"
                        >
                            Delete
                        </button>

                    </form>

                </td>

            </tr>

        <?php } ?>

    <?php } else { ?>

        <tr>

            <td colspan="3" style="text-align:center;">

                <?php
                echo $search != ''
                    ? 'No managers found.'
                    : 'No manager accounts yet.';
                ?>

            </td>

        </tr>

    <?php } ?>

</table>


<script>

function togglePassword() {

    var password = document.getElementById('managerPassword');

    var button = event.target;

    if (password.type === 'password') {

        password.type = 'text';
        button.textContent = 'Hide';

    } else {

        password.type = 'password';
        button.textContent = 'Show';

    }
}

</script><?php include '../includes/footer.php'; ?>
