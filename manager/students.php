<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

need_manager();

$base = '../';
$title = 'Manage Students';
$sid = $_SESSION['manager_school'];

$msg = '';
$bad = '';

/*
|--------------------------------------------------------------------------
| CSRF Protection
|--------------------------------------------------------------------------
*/
if (empty($_SESSION['students_csrf'])) {
    $_SESSION['students_csrf'] = bin2hex(random_bytes(32));
}

$csrf = $_SESSION['students_csrf'];

/*
|--------------------------------------------------------------------------
| Helper: Validate CSRF
|--------------------------------------------------------------------------
*/
function valid_csrf($token) {
    return isset($_SESSION['students_csrf']) &&
           hash_equals($_SESSION['students_csrf'], $token);
}

/*
|--------------------------------------------------------------------------
| Helper: Clean Index Number
|--------------------------------------------------------------------------
*/
function clean_index($index) {
    $index = trim($index);
    $index = preg_replace('/\s+/', '', $index);
    return $index;
}

/*
|--------------------------------------------------------------------------
| Handle POST Requests
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_POST['csrf_token']) || !valid_csrf($_POST['csrf_token'])) {
        $bad = 'Security verification failed. Please try again.';
    }

    /*
    |--------------------------------------------------------------------------
    | CSV Upload
    |--------------------------------------------------------------------------
    */
    elseif (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === 0) {

        $added = 0;
        $duplicates = 0;
        $invalid = 0;

        $fileName = $_FILES['csv_file']['name'];
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if ($extension !== 'csv') {
            $bad = 'Please upload a valid CSV file.';
        } else {

            $fh = fopen($_FILES['csv_file']['tmp_name'], 'r');

            if ($fh === false) {
                $bad = 'Unable to open the CSV file.';
            } else {

                while (($row = fgetcsv($fh)) !== false) {

                    foreach ($row as $cell) {

                        $index = clean_index($cell);

                        if ($index === '') {
                            continue;
                        }

                        /*
                        | Skip common CSV headers
                        */
                        $lower = strtolower($index);

                        if (
                            $lower === 'index_number' ||
                            $lower === 'index number' ||
                            $lower === 'indexnumber' ||
                            $lower === 'index'
                        ) {
                            continue;
                        }

                        /*
                        | Basic validation
                        | Allows letters, numbers, hyphens and slashes.
                        */
                        if (!preg_match('/^[A-Za-z0-9\/_-]+$/', $index)) {
                            $invalid++;
                            continue;
                        }

                        $q = $db->prepare(
                            'SELECT id FROM students
                             WHERE index_number = ? AND school_id = ?
                             LIMIT 1'
                        );

                        $q->execute(array($index, $sid));

                        if ($q->fetch()) {
                            $duplicates++;
                            continue;
                        }

                        $insert = $db->prepare(
                            'INSERT INTO students (index_number, school_id)
                             VALUES (?, ?)'
                        );

                        $insert->execute(array($index, $sid));

                        if ($insert->rowCount() > 0) {
                            $added++;
                        }
                    }
                }

                fclose($fh);

                $msg = $added . ' index number(s) added from the CSV file.';

                if ($duplicates > 0) {
                    $msg .= ' ' . $duplicates . ' duplicate(s) skipped.';
                }

                if ($invalid > 0) {
                    $msg .= ' ' . $invalid . ' invalid entrie(s) skipped.';
                }
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Manual List
    |--------------------------------------------------------------------------
    */
    elseif (isset($_POST['manual_list'])) {

        $added = 0;
        $duplicates = 0;
        $invalid = 0;

        $list = preg_split(
            '/[\s,;]+/',
            $_POST['manual_list']
        );

        foreach ($list as $index) {

            $index = clean_index($index);

            if ($index === '') {
                continue;
            }

            if (!preg_match('/^[A-Za-z0-9\/_-]+$/', $index)) {
                $invalid++;
                continue;
            }

            $q = $db->prepare(
                'SELECT id FROM students
                 WHERE index_number = ? AND school_id = ?
                 LIMIT 1'
            );

            $q->execute(array($index, $sid));

            if ($q->fetch()) {
                $duplicates++;
                continue;
            }

            $insert = $db->prepare(
                'INSERT INTO students (index_number, school_id)
                 VALUES (?, ?)'
            );

            $insert->execute(array($index, $sid));

            if ($insert->rowCount() > 0) {
                $added++;
            }
        }

        $msg = $added . ' index number(s) added.';

        if ($duplicates > 0) {
            $msg .= ' ' . $duplicates . ' duplicate(s) skipped.';
        }

        if ($invalid > 0) {
            $msg .= ' ' . $invalid . ' invalid entrie(s) skipped.';
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Edit Student
    |--------------------------------------------------------------------------
    */
    elseif (isset($_POST['edit_id'])) {

        $editId = intval($_POST['edit_id']);
        $newIndex = clean_index($_POST['new_index']);

        if ($newIndex === '') {

            $bad = 'Index number cannot be empty.';

        } elseif (!preg_match('/^[A-Za-z0-9\/_-]+$/', $newIndex)) {

            $bad = 'The index number contains invalid characters.';

        } else {

            /*
            | Check if another student already has this index number.
            */
            $check = $db->prepare(
                'SELECT id FROM students
                 WHERE index_number = ?
                 AND school_id = ?
                 AND id != ?
                 LIMIT 1'
            );

            $check->execute(array(
                $newIndex,
                $sid,
                $editId
            ));

            if ($check->fetch()) {

                $bad = 'That index number already exists.';

            } else {

                $q = $db->prepare(
                    'UPDATE students
                     SET index_number = ?
                     WHERE id = ?
                     AND school_id = ?'
                );

                $q->execute(array(
                    $newIndex,
                    $editId,
                    $sid
                ));

                $msg = 'Index number updated successfully.';
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Bulk Delete
    |--------------------------------------------------------------------------
    */
    elseif (isset($_POST['bulk_delete'])) {

        if (
            !isset($_POST['student_ids']) ||
            !is_array($_POST['student_ids']) ||
            count($_POST['student_ids']) === 0
        ) {

            $bad = 'Please select at least one student.';

        } else {

            $ids = array();

            foreach ($_POST['student_ids'] as $id) {
                $id = intval($id);

                if ($id > 0) {
                    $ids[] = $id;
                }
            }

            if (count($ids) > 0) {

                $placeholders = implode(
                    ',',
                    array_fill(0, count($ids), '?')
                );

                $params = array_merge(
                    $ids,
                    array($sid)
                );

                $q = $db->prepare(
                    "DELETE FROM students
                     WHERE id IN ($placeholders)
                     AND school_id = ?"
                );

                $q->execute($params);

                $msg = $q->rowCount() .
                       ' student(s) removed successfully.';
            }
        }
    }
}

/*
|--------------------------------------------------------------------------
| Individual Delete
|--------------------------------------------------------------------------
|
| Deletion is now POST-based instead of GET-based.
|
*/
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['delete_id']) &&
    isset($_POST['csrf_token']) &&
    valid_csrf($_POST['csrf_token'])
) {

    $deleteId = intval($_POST['delete_id']);

    if ($deleteId > 0) {

        $q = $db->prepare(
            'DELETE FROM students
             WHERE id = ?
             AND school_id = ?'
        );

        $q->execute(array(
            $deleteId,
            $sid
        ));

        if ($q->rowCount() > 0) {
            $msg = 'Index number removed successfully.';
        } else {
            $bad = 'Student could not be found.';
        }
    }
}

/*
|--------------------------------------------------------------------------
| Export CSV
|--------------------------------------------------------------------------
*/
if (
    isset($_GET['export']) &&
    $_GET['export'] === 'csv'
) {

    $q = $db->prepare(
        'SELECT index_number
         FROM students
         WHERE school_id = ?
         ORDER BY index_number'
    );

    $q->execute(array($sid));

    $filename = 'students_' . date('Y-m-d_H-i-s') . '.csv';

    header('Content-Type: text/csv; charset=utf-8');
    header(
        'Content-Disposition: attachment; filename="' .
        $filename .
        '"'
    );

    $output = fopen('php://output', 'w');

    fputcsv($output, array('index_number'));

    while ($row = $q->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, array($row['index_number']));
    }

    fclose($output);
    exit;
}

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
$perPage = 20;

$page = isset($_GET['page'])
    ? intval($_GET['page'])
    : 1;

if ($page < 1) {
    $page = 1;
}

/*
|--------------------------------------------------------------------------
| Count Students
|--------------------------------------------------------------------------
*/
if ($search !== '') {

    $countQ = $db->prepare(
        'SELECT COUNT(*)
         FROM students
         WHERE school_id = ?
         AND index_number LIKE ?'
    );

    $countQ->execute(array(
        $sid,
        '%' . $search . '%'
    ));

} else {

    $countQ = $db->prepare(
        'SELECT COUNT(*)
         FROM students
         WHERE school_id = ?'
    );

    $countQ->execute(array($sid));
}

$totalStudents = intval($countQ->fetchColumn());

$totalPages = max(
    1,
    ceil($totalStudents / $perPage)
);

if ($page > $totalPages) {
    $page = $totalPages;
}

$offset = ($page - 1) * $perPage;

/*
|--------------------------------------------------------------------------
| Get Students
|--------------------------------------------------------------------------
*/
if ($search !== '') {

    $q = $db->prepare(
        'SELECT *
         FROM students
         WHERE school_id = ?
         AND index_number LIKE ?
         ORDER BY index_number
         LIMIT ? OFFSET ?'
    );

    /*
    | LIMIT/OFFSET need integers.
    */
    $q->bindValue(1, $sid);
    $q->bindValue(
        2,
        '%' . $search . '%',
        PDO::PARAM_STR
    );
    $q->bindValue(
        3,
        $perPage,
        PDO::PARAM_INT
    );
    $q->bindValue(
        4,
        $offset,
        PDO::PARAM_INT
    );

    $q->execute();

} else {

    $q = $db->prepare(
        'SELECT *
         FROM students
         WHERE school_id = ?
         ORDER BY index_number
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

$students = $q->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| Statistics
|--------------------------------------------------------------------------
*/
$statsQ = $db->prepare(
    'SELECT COUNT(*)
     FROM students
     WHERE school_id = ?'
);

$statsQ->execute(array($sid));

$allStudents = intval($statsQ->fetchColumn());

$showingFrom = $totalStudents > 0
    ? $offset + 1
    : 0;

$showingTo = min(
    $offset + $perPage,
    $totalStudents
);

/*
|--------------------------------------------------------------------------
| Edit Student
|--------------------------------------------------------------------------
*/
$edit = null;

if (isset($_GET['edit'])) {

    $editId = intval($_GET['edit']);

    $q = $db->prepare(
        'SELECT *
         FROM students
         WHERE id = ?
         AND school_id = ?'
    );

    $q->execute(array(
        $editId,
        $sid
    ));

    $edit = $q->fetch(PDO::FETCH_ASSOC);
}

include '../includes/header.php';
?>

<h1>Student Index Numbers</h1>

<p class="lead">
    Students on this list can sign in to your school with their
    index number alone.
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


<!-- =========================================================
     STATISTICS
========================================================= -->

<div class="card">

    <h2>Student Overview</h2>

    <p>
        <strong>Total registered students:</strong>
        <?php echo number_format($allStudents); ?>
    </p>

    <p>
        <strong>Students currently shown:</strong>
        <?php echo number_format($totalStudents); ?>
    </p>

</div>


<!-- =========================================================
     SEARCH + EXPORT
========================================================= -->

<div class="card">

    <h2>Search Students</h2>

    <form method="get">

        <input
            type="text"
            name="search"
            placeholder="Search by index number..."
            value="<?php echo htmlspecialchars($search); ?>"
        >

        <button type="submit" class="btn">
            Search
        </button>

        <?php if ($search !== '') { ?>

            <a
                href="students.php"
                class="btn gold"
            >
                Clear Search
            </a>

        <?php } ?>

        <a
            href="students.php?export=csv"
            class="btn"
        >
            Export CSV
        </a>

    </form>

</div>


<!-- =========================================================
     CSV UPLOAD
========================================================= -->

<div class="card">

    <h2>Upload a CSV File</h2>

    <p class="lead">
        Upload a CSV file containing student index numbers.
        Duplicate index numbers will automatically be skipped.
    </p>

    <form
        method="post"
        enctype="multipart/form-data"
    >

        <input
            type="hidden"
            name="csrf_token"
            value="<?php echo htmlspecialchars($csrf); ?>"
        >

        <input
            type="file"
            name="csv_file"
            accept=".csv"
            required
        >

        <button
            type="submit"
            class="btn"
        >
            Upload and Extract
        </button>

    </form>

</div>


<!-- =========================================================
     MANUAL ADD
========================================================= -->

<div class="card">

    <h2>Or Enter Index Numbers Manually</h2>

    <p class="lead">
        Enter one index number per line.
        Commas, spaces and semicolons are also supported.
    </p>

    <form method="post">

        <input
            type="hidden"
            name="csrf_token"
            value="<?php echo htmlspecialchars($csrf); ?>"
        >

        <label>
            Index Numbers
        </label>

        <textarea
            name="manual_list"
            required
            placeholder="Example:
10234567
10234568
10234569"
        ></textarea>

        <button
            type="submit"
            class="btn"
        >
            Add Index Numbers
        </button>

    </form>

</div>


<!-- =========================================================
     EDIT
========================================================= -->

<?php if ($edit) { ?>

<div class="card">

    <h2>Edit Index Number</h2>

    <form method="post">

        <input
            type="hidden"
            name="csrf_token"
            value="<?php echo htmlspecialchars($csrf); ?>"
        >

        <input
            type="hidden"
            name="edit_id"
            value="<?php echo intval($edit['id']); ?>"
        >

        <label>
            Index Number
        </label>

        <input
            type="text"
            name="new_index"
            value="<?php echo htmlspecialchars($edit['index_number']); ?>"
            required
        >

        <button
            type="submit"
            class="btn"
        >
            Save
        </button>

        <a
            href="students.php"
            class="btn gold"
        >
            Cancel
        </a>

    </form>

</div>

<?php } ?>


<!-- =========================================================
     STUDENT TABLE
========================================================= -->

<div class="card">

<h2>
    Registered Students
    (<?php echo number_format($totalStudents); ?>)
</h2>

<?php if (count($students) > 0) { ?>

<form
    method="post"
    id="bulkForm"
    onsubmit="return confirmBulkDelete();"
>

    <input
        type="hidden"
        name="csrf_token"
        value="<?php echo htmlspecialchars($csrf); ?>"
    >

    <input
        type="hidden"
        name="bulk_delete"
        value="1"
    >

    <div style="margin-bottom:15px;">

        <button
            type="submit"
            class="btn red"
            id="bulkDeleteButton"
        >
            Delete Selected
        </button>

    </div>

    <table>

        <tr>

            <th>
                <input
                    type="checkbox"
                    id="selectAll"
                    onclick="toggleAll(this)"
                >
            </th>

            <th>
                Index Number
            </th>

            <th>
                Actions
            </th>

        </tr>

        <?php foreach ($students as $st) { ?>

        <tr>

            <td>

                <input
                    type="checkbox"
                    class="studentCheckbox"
                    name="student_ids[]"
                    value="<?php echo intval($st['id']); ?>"
                >

            </td>

            <td>
                <?php echo htmlspecialchars($st['index_number']); ?>
            </td>

            <td>

                <a
                    class="btn small gold"
                    href="students.php?edit=<?php echo intval($st['id']); ?>"
                >
                    Edit
                </a>

                <form
                    method="post"
                    style="display:inline;"
                    onsubmit="return confirm('Remove this index number?');"
                >

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?php echo htmlspecialchars($csrf); ?>"
                    >

                    <input
                        type="hidden"
                        name="delete_id"
                        value="<?php echo intval($st['id']); ?>"
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

    </table>

</form>


<!-- =========================================================
     PAGINATION
========================================================= -->

<?php if ($totalPages > 1) { ?>

<div style="margin-top:20px;">

    <p>
        Showing
        <?php echo $showingFrom; ?>
        -
        <?php echo $showingTo; ?>
        of
        <?php echo $totalStudents; ?>
        students
    </p>

    <?php if ($page > 1) { ?>

        <a
            class="btn small gold"
            href="students.php?search=<?php echo urlencode($search); ?>&page=<?php echo $page - 1; ?>"
        >
            Previous
        </a>

    <?php } ?>


    <?php

    /*
    | Show a maximum of 7 page buttons.
    */

    $startPage = max(1, $page - 3);
    $endPage = min($totalPages, $page + 3);

    for ($i = $startPage; $i <= $endPage; $i++) {

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
            href="students.php?search=<?php echo urlencode($search); ?>&page=<?php echo $i; ?>"
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
            href="students.php?search=<?php echo urlencode($search); ?>&page=<?php echo $page + 1; ?>"
        >
            Next
        </a>

    <?php } ?>

</div>

<?php } ?>


<?php } else { ?>

    <p class="lead">
        <?php if ($search !== '') { ?>

            No students were found matching
            "<strong><?php echo htmlspecialchars($search); ?></strong>".

        <?php } else { ?>

            No students have been registered yet.

        <?php } ?>
    </p>

<?php } ?>

</div>


<!-- =========================================================
     JAVASCRIPT
========================================================= -->

<script>

function toggleAll(source) {

    var checkboxes =
        document.querySelectorAll('.studentCheckbox');

    for (var i = 0; i < checkboxes.length; i++) {

        checkboxes[i].checked =
            source.checked;

    }

}


function confirmBulkDelete() {

    var selected =
        document.querySelectorAll(
            '.studentCheckbox:checked'
        );

    if (selected.length === 0) {

        alert(
            'Please select at least one student.'
        );

        return false;
    }

    return confirm(
        'Are you sure you want to remove ' +
        selected.length +
        ' selected student(s)?'
    );

}

</script><?php include '../includes/footer.php'; ?>
