<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

need_manager();

$base = '../';
$title = 'Manage Students';
$sid = intval($_SESSION['manager_school']);

$msg = '';
$bad = '';

/*
|--------------------------------------------------------------------------
| CSRF PROTECTION
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['students_csrf'])) {
    $_SESSION['students_csrf'] = bin2hex(random_bytes(32));
}

$csrf = $_SESSION['students_csrf'];

function valid_csrf($token)
{
    return isset($_SESSION['students_csrf']) &&
           hash_equals($_SESSION['students_csrf'], $token);
}


/*
|--------------------------------------------------------------------------
| CLEAN INDEX NUMBER
|--------------------------------------------------------------------------
*/

function clean_index($index)
{
    $index = trim($index);
    $index = preg_replace('/\s+/', '', $index);

    return $index;
}


/*
|--------------------------------------------------------------------------
| VALIDATE INDEX NUMBER
|--------------------------------------------------------------------------
*/

function valid_index($index)
{
    return preg_match(
        '/^[A-Za-z0-9\/_-]+$/',
        $index
    );
}


/*
|--------------------------------------------------------------------------
| ADD STUDENT HELPER
|--------------------------------------------------------------------------
*/

function add_student($db, $index, $sid)
{
    $index = clean_index($index);

    if ($index === '') {
        return 'invalid';
    }

    if (!valid_index($index)) {
        return 'invalid';
    }

    $check = $db->prepare(
        'SELECT id
         FROM students
         WHERE index_number = ?
         AND school_id = ?
         LIMIT 1'
    );

    $check->execute(array(
        $index,
        $sid
    ));

    if ($check->fetch()) {
        return 'duplicate';
    }

    $insert = $db->prepare(
        'INSERT INTO students
        (index_number, school_id)
        VALUES (?, ?)'
    );

    $insert->execute(array(
        $index,
        $sid
    ));

    if ($insert->rowCount() > 0) {
        return 'added';
    }

    return 'invalid';
}


/*
|--------------------------------------------------------------------------
| HANDLE POST
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = isset($_POST['csrf_token'])
        ? $_POST['csrf_token']
        : '';

    if (!valid_csrf($token)) {

        $bad =
            'Security verification failed. Please refresh the page and try again.';

    } else {


        /*
        |--------------------------------------------------------------------------
        | CSV UPLOAD
        |--------------------------------------------------------------------------
        */

        if (
            isset($_FILES['csv_file']) &&
            $_FILES['csv_file']['error'] !== UPLOAD_ERR_NO_FILE
        ) {

            $added = 0;
            $duplicates = 0;
            $invalid = 0;

            $file = $_FILES['csv_file'];

            $extension = strtolower(
                pathinfo(
                    $file['name'],
                    PATHINFO_EXTENSION
                )
            );


            if ($file['error'] !== UPLOAD_ERR_OK) {

                $bad = 'There was a problem uploading the CSV file.';

            } elseif ($extension !== 'csv') {

                $bad = 'Please upload a CSV file only.';

            } elseif ($file['size'] > 5 * 1024 * 1024) {

                $bad =
                    'The CSV file is too large. Maximum size is 5MB.';

            } else {

                $fh = fopen(
                    $file['tmp_name'],
                    'r'
                );


                if ($fh === false) {

                    $bad =
                        'Unable to read the CSV file.';

                } else {

                    /*
                    | Read CSV line by line.
                    */

                    while (($row = fgetcsv($fh)) !== false) {

                        if (!isset($row[0])) {
                            continue;
                        }

                        $index = clean_index($row[0]);

                        if ($index === '') {
                            continue;
                        }


                        /*
                        | Remove UTF-8 BOM.
                        */

                        $index = preg_replace(
                            '/^\xEF\xBB\xBF/',
                            '',
                            $index
                        );


                        /*
                        | Skip common headers.
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


                        $result = add_student(
                            $db,
                            $index,
                            $sid
                        );


                        if ($result === 'added') {

                            $added++;

                        } elseif ($result === 'duplicate') {

                            $duplicates++;

                        } else {

                            $invalid++;
                        }
                    }

                    fclose($fh);


                    $msg =
                        $added .
                        ' student(s) added from the CSV file.';


                    if ($duplicates > 0) {

                        $msg .=
                            ' ' .
                            $duplicates .
                            ' duplicate(s) skipped.';
                    }


                    if ($invalid > 0) {

                        $msg .=
                            ' ' .
                            $invalid .
                            ' invalid entrie(s) skipped.';
                    }
                }
            }
        }


        /*
        |--------------------------------------------------------------------------
        | MANUAL LIST
        |--------------------------------------------------------------------------
        */

        elseif (isset($_POST['manual_list'])) {

            $added = 0;
            $duplicates = 0;
            $invalid = 0;

            $manual = isset($_POST['manual_list'])
                ? $_POST['manual_list']
                : '';


            $list = preg_split(
                '/[\s,;]+/',
                $manual
            );


            foreach ($list as $index) {

                $result = add_student(
                    $db,
                    $index,
                    $sid
                );


                if ($result === 'added') {

                    $added++;

                } elseif ($result === 'duplicate') {

                    $duplicates++;

                } elseif ($result === 'invalid') {

                    $invalid++;
                }
            }


            $msg =
                $added .
                ' student(s) added successfully.';


            if ($duplicates > 0) {

                $msg .=
                    ' ' .
                    $duplicates .
                    ' duplicate(s) skipped.';
            }


            if ($invalid > 0) {

                $msg .=
                    ' ' .
                    $invalid .
                    ' invalid entrie(s) skipped.';
            }
        }


        /*
        |--------------------------------------------------------------------------
        | EDIT STUDENT
        |--------------------------------------------------------------------------
        */

        elseif (isset($_POST['edit_id'])) {

            $editId = intval(
                $_POST['edit_id']
            );

            $newIndex = clean_index(
                isset($_POST['new_index'])
                    ? $_POST['new_index']
                    : ''
            );


            if ($newIndex === '') {

                $bad =
                    'Index number cannot be empty.';

            } elseif (!valid_index($newIndex)) {

                $bad =
                    'The index number contains invalid characters.';

            } else {

                /*
                | Check duplicate.
                */

                $check = $db->prepare(
                    'SELECT id
                     FROM students
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

                    $bad =
                        'That index number already exists.';

                } else {

                    $update = $db->prepare(
                        'UPDATE students
                         SET index_number = ?
                         WHERE id = ?
                         AND school_id = ?'
                    );

                    $update->execute(array(
                        $newIndex,
                        $editId,
                        $sid
                    ));


                    if ($update->rowCount() > 0) {

                        $msg =
                            'Index number updated successfully.';

                    } else {

                        $msg =
                            'No changes were made.';
                    }
                }
            }
        }


        /*
        |--------------------------------------------------------------------------
        | BULK DELETE
        |--------------------------------------------------------------------------
        */

        elseif (isset($_POST['bulk_delete'])) {

            $studentIds =
                isset($_POST['student_ids']) &&
                is_array($_POST['student_ids'])
                    ? $_POST['student_ids']
                    : array();


            $ids = array();


            foreach ($studentIds as $studentId) {

                $studentId = intval($studentId);

                if ($studentId > 0) {
                    $ids[] = $studentId;
                }
            }


            $ids = array_unique($ids);


            if (count($ids) === 0) {

                $bad =
                    'Please select at least one student.';

            } else {

                $placeholders = implode(
                    ',',
                    array_fill(
                        0,
                        count($ids),
                        '?'
                    )
                );


                $params = array_merge(
                    $ids,
                    array($sid)
                );


                $delete = $db->prepare(
                    "DELETE FROM students
                     WHERE id IN ($placeholders)
                     AND school_id = ?"
                );


                $delete->execute($params);


                $msg =
                    $delete->rowCount() .
                    ' student(s) removed successfully.';
            }
        }


        /*
        |--------------------------------------------------------------------------
        | INDIVIDUAL DELETE
        |--------------------------------------------------------------------------
        */

        elseif (isset($_POST['delete_id'])) {

            $deleteId = intval(
                $_POST['delete_id']
            );


            if ($deleteId <= 0) {

                $bad =
                    'Invalid student selected.';

            } else {

                $delete = $db->prepare(
                    'DELETE FROM students
                     WHERE id = ?
                     AND school_id = ?'
                );


                $delete->execute(array(
                    $deleteId,
                    $sid
                ));


                if ($delete->rowCount() > 0) {

                    $msg =
                        'Student removed successfully.';

                } else {

                    $bad =
                        'Student could not be found.';
                }
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| EXPORT CSV
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


    $filename =
        'students_' .
        date('Y-m-d_H-i-s') .
        '.csv';


    header(
        'Content-Type: text/csv; charset=utf-8'
    );

    header(
        'Content-Disposition: attachment; filename="' .
        $filename .
        '"'
    );


    $output = fopen(
        'php://output',
        'w'
    );


    fputcsv(
        $output,
        array('index_number')
    );


    while (
        $row = $q->fetch(
            PDO::FETCH_ASSOC
        )
    ) {

        fputcsv(
            $output,
            array($row['index_number'])
        );
    }


    fclose($output);

    exit;
}


/*
|--------------------------------------------------------------------------
| DOWNLOAD CSV TEMPLATE
|--------------------------------------------------------------------------
*/

if (
    isset($_GET['template']) &&
    $_GET['template'] === 'csv'
) {

    header(
        'Content-Type: text/csv; charset=utf-8'
    );

    header(
        'Content-Disposition: attachment; filename="student_template.csv"'
    );


    $output = fopen(
        'php://output',
        'w'
    );


    fputcsv(
        $output,
        array('index_number')
    );

    fputcsv(
        $output,
        array('10234567')
    );

    fputcsv(
        $output,
        array('10234568')
    );


    fclose($output);

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
|--------------------------------------------------------------------------
| PAGINATION
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
| COUNT
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

    $countQ->execute(
        array($sid)
    );
}


$totalStudents = intval(
    $countQ->fetchColumn()
);


$totalPages = max(
    1,
    ceil(
        $totalStudents / $perPage
    )
);


if ($page > $totalPages) {
    $page = $totalPages;
}


$offset =
    ($page - 1) *
    $perPage;


/*
|--------------------------------------------------------------------------
| GET STUDENTS
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


    $q->bindValue(
        1,
        $sid,
        PDO::PARAM_INT
    );

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


$students = $q->fetchAll(
    PDO::FETCH_ASSOC
);


/*
|--------------------------------------------------------------------------
| EDIT STUDENT
|--------------------------------------------------------------------------
*/

$edit = null;

if (isset($_GET['edit'])) {

    $editId = intval(
        $_GET['edit']
    );


    $editQ = $db->prepare(
        'SELECT *
         FROM students
         WHERE id = ?
         AND school_id = ?'
    );


    $editQ->execute(array(
        $editId,
        $sid
    ));


    $edit =
        $editQ->fetch(
            PDO::FETCH_ASSOC
        );
}


/*
|--------------------------------------------------------------------------
| STATISTICS
|--------------------------------------------------------------------------
*/

$showingFrom =
    $totalStudents > 0
        ? $offset + 1
        : 0;


$showingTo =
    min(
        $offset + $perPage,
        $totalStudents
    );


include '../includes/header.php';
?>


<style>

/* =========================================================
   STUDENT MANAGEMENT
========================================================= */

.student-stats {

    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 18px;

    margin: 25px 0;
}


.student-stat {

    background: #fff;

    padding: 22px;

    border-radius: 12px;

    box-shadow:
        0 4px 15px rgba(0,0,0,.08);

    border-left:
        5px solid #d4af37;
}


.student-stat h3 {

    margin: 0 0 8px;

    font-size: 14px;

    color: #777;
}


.student-stat-number {

    font-size: 30px;

    font-weight: bold;
}


/* =========================================================
   TOOLBAR
========================================================= */

.student-toolbar {

    display: flex;

    gap: 10px;

    flex-wrap: wrap;

    align-items: center;
}


.student-toolbar input {

    flex: 1;

    min-width: 220px;
}


/* =========================================================
   TABLE
========================================================= */

.student-table-wrapper {

    overflow-x: auto;
}


.student-table {

    width: 100%;

    border-collapse: collapse;

    margin-top: 15px;
}


.student-table th,
.student-table td {

    padding: 13px;

    border-bottom:
        1px solid #eee;

    text-align: left;
}


.student-table th {

    background: #f7f7f7;

    font-weight: bold;
}


.student-table tr:hover {

    background: #fafafa;
}


.student-actions {

    display: flex;

    gap: 7px;

    flex-wrap: wrap;
}


/* =========================================================
   PAGINATION
========================================================= */

.student-pagination {

    display: flex;

    gap: 7px;

    flex-wrap: wrap;

    align-items: center;

    margin-top: 25px;
}


.student-pagination a,
.student-pagination span {

    padding: 8px 12px;

    border-radius: 7px;

    text-decoration: none;
}


.student-pagination a {

    background: #eee;

    color: #222;
}


.student-pagination .current {

    background: #d4af37;

    color: #fff;

    font-weight: bold;
}


/* =========================================================
   HELP TEXT
========================================================= */

.help-text {

    font-size: 13px;

    color: #777;

    margin-top: 5px;
}


/* =========================================================
   MOBILE
========================================================= */

@media (max-width: 700px) {

    .student-stats {

        grid-template-columns: 1fr;
    }

    .student-toolbar {

        flex-direction: column;

        align-items: stretch;
    }

    .student-toolbar input {

        width: 100%;
    }

}

</style>


<h1>Manage Students</h1>

<p class="lead">
    Add, edit, search, export and manage student index numbers
    for your school.
</p>


<?php if ($msg !== '') { ?>

    <div class="msg ok">

        <?php
        echo htmlspecialchars($msg);
        ?>

    </div>

<?php } ?>


<?php if ($bad !== '') { ?>

    <div class="msg bad">

        <?php
        echo htmlspecialchars($bad);
        ?>

    </div>

<?php } ?>


<!-- =========================================================
     STATISTICS
========================================================= -->

<div class="student-stats">

    <div class="student-stat">

        <h3>
            Total Students
        </h3>

        <div class="student-stat-number">

            <?php
            echo number_format(
                $totalStudents
            );
            ?>

        </div>

    </div>


    <div class="student-stat">

        <h3>
            Current Page
        </h3>

        <div class="student-stat-number">

            <?php
            echo $page;
            ?>

        </div>

    </div>


    <div class="student-stat">

        <h3>
            Students Per Page
        </h3>

        <div class="student-stat-number">

            <?php
            echo $perPage;
            ?>

        </div>

    </div>

</div>


<!-- =========================================================
     SEARCH
========================================================= -->

<div class="card">

    <h2>
        Search Students
    </h2>

    <form
        method="get"
        class="student-toolbar"
    >

        <input
            type="text"
            name="search"
            value="<?php
                echo htmlspecialchars(
                    $search
                );
            ?>"
            placeholder="Search by index number..."
        >


        <button
            type="submit"
            class="btn"
        >
            Search
        </button>


        <?php if ($search !== '') { ?>

            <a
                href="students.php"
                class="btn gold"
            >
                Clear
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
     CSV IMPORT
========================================================= -->

<div class="card">

    <h2>
        Import Students
    </h2>

    <p class="lead">
        Upload a CSV file containing student index numbers.
        Duplicate numbers will automatically be skipped.
    </p>


    <form
        method="post"
        enctype="multipart/form-data"
    >

        <input
            type="hidden"
            name="csrf_token"
            value="<?php
                echo htmlspecialchars(
                    $csrf
                );
            ?>"
        >


        <label>
            CSV File
        </label>

        <input
            type="file"
            name="csv_file"
            accept=".csv,text/csv"
            required
        >


        <p class="help-text">
            Maximum file size: 5MB.
            The first column should contain index numbers.
        </p>


        <button
            type="submit"
            class="btn"
        >
            Import Students
        </button>


        <a
            href="students.php?template=csv"
            class="btn gold"
        >
            Download CSV Template
        </a>

    </form>

</div>


<!-- =========================================================
     MANUAL ADD
========================================================= -->

<div class="card">

    <h2>
        Add Students Manually
    </h2>

    <p class="lead">
        Enter multiple index numbers separated by spaces,
        commas, semicolons or new lines.
    </p>


    <form method="post">

        <input
            type="hidden"
            name="csrf_token"
            value="<?php
                echo htmlspecialchars(
                    $csrf
                );
            ?>"
        >


        <label>
            Student Index Numbers
        </label>

        <textarea
            name="manual_list"
            rows="7"
            placeholder="Example:
10234567
10234568
10234569"
            required
        ></textarea>


        <button
            type="submit"
            class="btn"
        >
            Add Students
        </button>

    </form>

</div>


<!-- =========================================================
     EDIT STUDENT
========================================================= -->

<?php if ($edit) { ?>

<div class="card">

    <h2>
        Edit Student
    </h2>


    <form method="post">

        <input
            type="hidden"
            name="csrf_token"
            value="<?php
                echo htmlspecialchars(
                    $csrf
                );
            ?>"
        >


        <input
            type="hidden"
            name="edit_id"
            value="<?php
                echo intval(
                    $edit['id']
                );
            ?>"
        >


        <label>
            Index Number
        </label>


        <input
            type="text"
            name="new_index"
            value="<?php
                echo htmlspecialchars(
                    $edit['index_number']
                );
            ?>"
            required
        >


        <button
            type="submit"
            class="btn"
        >
            Save Changes
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
     STUDENT LIST
========================================================= -->

<div class="card">

    <h2>
        Registered Students
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
            value="<?php
                echo htmlspecialchars(
                    $csrf
                );
            ?>"
        >


        <input
            type="hidden"
            name="bulk_delete"
            value="1"
        >


        <button
            type="submit"
            class="btn red"
        >
            Delete Selected
        </button>


        <div class="student-table-wrapper">

            <table class="student-table">

                <thead>

                    <tr>

                        <th>
                            <input
                                type="checkbox"
                                id="selectAll"
                                onclick="toggleAll(this)"
                            >
                        </th>

                        <th>
                            #
                        </th>

                        <th>
                            Index Number
                        </th>

                        <th>
                            Actions
                        </th>

                    </tr>

                </thead>


                <tbody>

                <?php

                $number =
                    $offset + 1;

                foreach (
                    $students
                    as $student
                ) {

                ?>

                    <tr>

                        <td>

                            <input
                                type="checkbox"
                                class="studentCheckbox"
                                name="student_ids[]"
                                value="<?php
                                    echo intval(
                                        $student['id']
                                    );
                                ?>"
                            >

                        </td>


                        <td>
                            <?php
                            echo $number++;
                            ?>
                        </td>


                        <td>

                            <strong>

                                <?php
                                echo htmlspecialchars(
                                    $student[
                                        'index_number'
                                    ]
                                );
                                ?>

                            </strong>

                        </td>


                        <td>

                            <div class="student-actions">


                                <a
                                    href="students.php?edit=<?php
                                        echo intval(
                                            $student['id']
                                        );
                                    ?>"
                                    class="btn small gold"
                                >
                                    Edit
                                </a>


                                <button
                                    type="submit"
                                    form="delete-<?php
                                        echo intval(
                                            $student['id']
                                        );
                                    ?>"
                                    class="btn small red"
                                >
                                    Remove
                                </button>

                            </div>


                            <!-- Separate delete form -->

                            <form
                                method="post"
                                id="delete-<?php
                                    echo intval(
                                        $student['id']
                                    );
                                ?>"
                                style="display:none;"
                                onsubmit="return confirm(
                                    'Remove this student?'
                                );"
                            >

                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?php
                                        echo htmlspecialchars(
                                            $csrf
                                        );
                                    ?>"
                                >


                                <input
                                    type="hidden"
                                    name="delete_id"
                                    value="<?php
                                        echo intval(
                                            $student['id']
                                        );
                                    ?>"
                                >

                            </form>

                        </td>

                    </tr>

                <?php } ?>

                </tbody>

            </table>

        </div>

    </form>


    <!-- =====================================================
         PAGINATION
    ====================================================== -->

    <div class="student-pagination">

        <?php if ($page > 1) { ?>

            <a
                href="students.php?search=<?php
                    echo urlencode($search);
                ?>&page=<?php
                    echo $page - 1;
                ?>"
            >
                Previous
            </a>

        <?php } ?>


        <?php

        $startPage =
            max(
                1,
                $page - 3
            );

        $endPage =
            min(
                $totalPages,
                $page + 3
            );


        for (
            $i = $startPage;
            $i <= $endPage;
            $i++
        ) {

        ?>

            <?php if ($i == $page) { ?>

                <span class="current">
                    <?php echo $i; ?>
                </span>

            <?php } else { ?>

                <a
                    href="students.php?search=<?php
                        echo urlencode(
                            $search
                        );
                    ?>&page=<?php
                        echo $i;
                    ?>"
                >
                    <?php echo $i; ?>
                </a>

            <?php } ?>

        <?php } ?>


        <?php if ($page < $totalPages) { ?>

            <a
                href="students.php?search=<?php
                    echo urlencode(
                        $search
                    );
                ?>&page=<?php
                    echo $page + 1;
                ?>"
            >
                Next
            </a>

        <?php } ?>

    </div>


    <p class="help-text">

        Showing
        <?php echo $showingFrom; ?>
        -
        <?php echo $showingTo; ?>

        of

        <?php echo $totalStudents; ?>

        student(s).

    </p>


    <?php } else { ?>


        <p class="lead">

            <?php if ($search !== '') { ?>

                No students found for
                <strong>
                    <?php
                    echo htmlspecialchars(
                        $search
                    );
                    ?>
                </strong>.

            <?php } else { ?>

                No students have been registered yet.

            <?php } ?>

        </p>


    <?php } ?>

</div>


<script>

/*
|--------------------------------------------------------------------------
| SELECT / DESELECT ALL
|--------------------------------------------------------------------------
*/

function toggleAll(source) {

    var boxes =
        document.querySelectorAll(
            '.studentCheckbox'
        );


    for (
        var i = 0;
        i < boxes.length;
        i++
    ) {

        boxes[i].checked =
            source.checked;
    }
}


/*
|--------------------------------------------------------------------------
| BULK DELETE CONFIRMATION
|--------------------------------------------------------------------------
*/

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
        'Are you sure you want to delete ' +
        selected.length +
        ' selected student(s)?'
    );
}

</script>


<?php include '../includes/footer.php'; ?>
