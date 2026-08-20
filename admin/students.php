<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

need_admin();

$base = '../';
$title = 'Manage Students';
$msg = '';
$bad = '';

/* ADD / EDIT / DELETE */
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    /* ADD STUDENT */
    if (isset($_POST['add'])) {

        $index = trim($_POST['index_number']);
        $sid = intval($_POST['school_id']);

        if ($index == '') {

            $bad = 'Index number is required.';

        } else {

            // Check if index number already exists
            $q = $db->prepare(
                'SELECT id FROM students WHERE index_number = ?'
            );

            $q->execute(array($index));

            if ($q->fetch()) {

                $bad = 'This index number already exists.';

            } else {

                $q = $db->prepare(
                    'INSERT INTO students (index_number, school_id)
                     VALUES (?, ?)'
                );

                $q->execute(array($index, $sid));

                $msg = 'Student index number added.';
            }
        }
    }


    /* EDIT STUDENT */
    else if (isset($_POST['edit_id'])) {

        $id = intval($_POST['edit_id']);
        $index = trim($_POST['index_number']);
        $sid = intval($_POST['school_id']);

        if ($index == '') {

            $bad = 'Index number is required.';

        } else {

            // Check if another student has this index number
            $q = $db->prepare(
                'SELECT id FROM students
                 WHERE index_number = ? AND id != ?'
            );

            $q->execute(array($index, $id));

            if ($q->fetch()) {

                $bad = 'This index number already belongs to another student.';

            } else {

                $q = $db->prepare(
                    'UPDATE students
                     SET index_number = ?, school_id = ?
                     WHERE id = ?'
                );

                $q->execute(array($index, $sid, $id));

                $msg = 'Student updated.';
            }
        }
    }


    /* DELETE STUDENT */
    else if (isset($_POST['delete_id'])) {

        $id = intval($_POST['delete_id']);

        $q = $db->prepare(
            'DELETE FROM students WHERE id = ?'
        );

        $q->execute(array($id));

        $msg = 'Student removed.';
    }
}


/* EDIT STUDENT */
$edit = null;

if (isset($_GET['edit'])) {

    $q = $db->prepare(
        'SELECT * FROM students WHERE id = ?'
    );

    $q->execute(array(intval($_GET['edit'])));

    $edit = $q->fetch();
}


/* SCHOOL FILTER */
$filter = isset($_GET['school'])
    ? intval($_GET['school'])
    : 0;


/* SEARCH */
$search = '';

if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
}


/* GET SCHOOLS */
$schools = $db->query(
    'SELECT * FROM schools ORDER BY id'
)->fetchAll();


/* GET STUDENTS */
if ($filter > 0 && $search != '') {

    $q = $db->prepare(
        'SELECT st.*, s.short_name
         FROM students st
         JOIN schools s ON s.id = st.school_id
         WHERE st.school_id = ?
         AND st.index_number LIKE ?
         ORDER BY st.index_number'
    );

    $q->execute(array(
        $filter,
        '%' . $search . '%'
    ));

} else if ($filter > 0) {

    $q = $db->prepare(
        'SELECT st.*, s.short_name
         FROM students st
         JOIN schools s ON s.id = st.school_id
         WHERE st.school_id = ?
         ORDER BY st.index_number'
    );

    $q->execute(array($filter));

} else if ($search != '') {

    $q = $db->prepare(
        'SELECT st.*, s.short_name
         FROM students st
         JOIN schools s ON s.id = st.school_id
         WHERE st.index_number LIKE ?
         ORDER BY s.id, st.index_number'
    );

    $q->execute(array(
        '%' . $search . '%'
    ));

} else {

    $q = $db->query(
        'SELECT st.*, s.short_name
         FROM students st
         JOIN schools s ON s.id = st.school_id
         ORDER BY s.id, st.index_number'
    );
}

$students = $q->fetchAll();

include '../includes/header.php';
?>

<h1>Student Index Numbers (All Schools)</h1>


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


<!-- ADD / EDIT STUDENT -->

<div class="card">

    <h2>
        <?php echo $edit ? 'Edit student' : 'Add a student'; ?>
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


        <label>Index number</label>

        <input
            type="text"
            name="index_number"
            value="<?php
                echo $edit
                    ? htmlspecialchars($edit['index_number'])
                    : '';
            ?>"
            required
        >


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
                : 'Add student';
            ?>

        </button>


        <?php if ($edit) { ?>

            <a
                href="students.php"
                class="btn gold"
            >
                Cancel
            </a>

        <?php } ?>

    </form>

</div>


<!-- SEARCH & FILTER -->

<div class="card">

    <h2>Find Students</h2>

    <form method="get">

        <label>Search index number</label>

        <input
            type="text"
            name="search"
            placeholder="Enter index number..."
            value="<?php echo htmlspecialchars($search); ?>"
        >


        <label>Filter by school</label>

        <select name="school">

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

                    <?php
                    echo htmlspecialchars($s['name']);
                    ?>

                </option>

            <?php } ?>

        </select>


        <button type="submit" class="btn">
            Search
        </button>


        <?php if ($search != '' || $filter > 0) { ?>

            <a
                href="students.php"
                class="btn gold"
            >
                Clear
            </a>

        <?php } ?>

    </form>

</div>


<h2>Students</h2>


<table>

    <tr>
        <th>Index number</th>
        <th>School</th>
        <th>Actions</th>
    </tr>


    <?php if (count($students) > 0) { ?>

        <?php foreach ($students as $st) { ?>

            <tr>

                <td>
                    <?php
                    echo htmlspecialchars(
                        $st['index_number']
                    );
                    ?>
                </td>


                <td>
                    <?php
                    echo htmlspecialchars(
                        $st['short_name']
                    );
                    ?>
                </td>


                <td>

                    <!-- EDIT -->

                    <a
                        class="btn small gold"
                        href="students.php?edit=<?php echo $st['id']; ?>"
                    >
                        Edit
                    </a>


                    <!-- DELETE -->

                    <form
                        method="post"
                        style="display:inline;"
                        onsubmit="return confirm('Remove this student? This action cannot be undone.');"
                    >

                        <input
                            type="hidden"
                            name="delete_id"
                            value="<?php echo $st['id']; ?>"
                        >

                        <button
                            type="submit"
                            class="btn small red"
                        >
                            Remove
                        </button>

                    </form>

                </td>

            </tr>

        <?php } ?>

    <?php } else { ?>

        <tr>

            <td
                colspan="3"
                style="text-align:center;"
            >

                <?php
                if ($search != '' || $filter > 0) {
                    echo 'No students found matching your search/filter.';
                } else {
                    echo 'No students have been added yet.';
                }
                ?>

            </td>

        </tr>

    <?php } ?>

</table><?php include '../includes/footer.php'; ?>
