<?php
require_once '../includes/db.php';

$base = '../';
$title = 'Student Login';

/*
|--------------------------------------------------------------------------
| Start Session
|--------------------------------------------------------------------------
*/
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| Get School
|--------------------------------------------------------------------------
*/
$sid = isset($_GET['school'])
    ? intval($_GET['school'])
    : 0;

$q = $db->prepare(
    'SELECT * FROM schools WHERE id = ?'
);

$q->execute(array($sid));

$school = $q->fetch();

if (!$school) {
    header('Location: ../index.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| CSRF Token
|--------------------------------------------------------------------------
*/
if (empty($_SESSION['student_login_csrf'])) {
    $_SESSION['student_login_csrf'] =
        bin2hex(random_bytes(32));
}

$csrf = $_SESSION['student_login_csrf'];

/*
|--------------------------------------------------------------------------
| Login Attempt Protection
|--------------------------------------------------------------------------
*/
if (!isset($_SESSION['student_login_attempts'])) {
    $_SESSION['student_login_attempts'] = 0;
}

if (!isset($_SESSION['student_login_last_attempt'])) {
    $_SESSION['student_login_last_attempt'] = 0;
}

$err = '';

/*
|--------------------------------------------------------------------------
| Process Login
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /*
    |--------------------------------------------------------------------------
    | CSRF Validation
    |--------------------------------------------------------------------------
    */
    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals(
            $_SESSION['student_login_csrf'],
            $_POST['csrf_token']
        )
    ) {

        $err = 'Security verification failed. Please refresh the page and try again.';

    } else {

        /*
        |--------------------------------------------------------------------------
        | Simple Login Rate Limiting
        |--------------------------------------------------------------------------
        */
        $now = time();

        /*
        | Reset attempts after 5 minutes.
        */
        if (
            $now -
            $_SESSION['student_login_last_attempt']
            > 300
        ) {

            $_SESSION['student_login_attempts'] = 0;
        }

        if (
            $_SESSION['student_login_attempts'] >= 5
        ) {

            $err =
                'Too many unsuccessful attempts. Please wait a few minutes and try again.';

        } else {

            /*
            |--------------------------------------------------------------------------
            | Get Index Number
            |--------------------------------------------------------------------------
            */
            $index = isset($_POST['index_number'])
                ? trim($_POST['index_number'])
                : '';

            /*
            | Remove unnecessary spaces inside the value.
            */
            $index = preg_replace(
                '/\s+/',
                '',
                $index
            );

            /*
            |--------------------------------------------------------------------------
            | Validate Input
            |--------------------------------------------------------------------------
            */
            if ($index === '') {

                $err = 'Please enter your index number.';

            } elseif (strlen($index) > 100) {

                $err = 'The index number is too long.';

            } elseif (
                !preg_match(
                    '/^[A-Za-z0-9\/_-]+$/',
                    $index
                )
            ) {

                $err =
                    'Please enter a valid index number.';

            } else {

                /*
                |--------------------------------------------------------------------------
                | Find Student
                |--------------------------------------------------------------------------
                |
                | LOWER() makes the comparison case-insensitive.
                |
                */
                $q = $db->prepare(
                    'SELECT *
                     FROM students
                     WHERE LOWER(index_number) = LOWER(?)
                     AND school_id = ?
                     LIMIT 1'
                );

                $q->execute(array(
                    $index,
                    $sid
                ));

                $st = $q->fetch();

                /*
                |--------------------------------------------------------------------------
                | Successful Login
                |--------------------------------------------------------------------------
                */
                if ($st) {

                    /*
                    | Prevent session fixation.
                    */
                    session_regenerate_id(true);

                    $_SESSION['student_id'] =
                        $st['id'];

                    $_SESSION['student_index'] =
                        $st['index_number'];

                    $_SESSION['student_school'] =
                        $sid;

                    /*
                    | Useful if you later want to display
                    | the student's last login session.
                    */
                    $_SESSION['student_logged_in'] =
                        true;

                    /*
                    | Reset failed attempts.
                    */
                    $_SESSION['student_login_attempts'] = 0;

                    /*
                    | Send student to events page.
                    */
                    header('Location: events.php');
                    exit;

                } else {

                    /*
                    | Increase failed attempt counter.
                    */
                    $_SESSION['student_login_attempts']++;

                    $_SESSION['student_login_last_attempt'] =
                        $now;

                    $remaining =
                        5 -
                        $_SESSION['student_login_attempts'];

                    if ($remaining > 0) {

                        $err =
                            'Index number not found for this school. ' .
                            'Please check your index number and try again.';

                    } else {

                        $err =
                            'Too many unsuccessful attempts. ' .
                            'Please wait a few minutes and try again.';
                    }
                }
            }
        }
    }
}

include '../includes/header.php';
?>

<div class="card narrow">

    <h1>
        <?php echo htmlspecialchars(
            $school['short_name']
        ); ?>

        Student Sign In
    </h1>

    <p class="lead">
        <?php echo htmlspecialchars(
            $school['name']
        ); ?>
    </p>


    <?php if ($err) { ?>

        <div class="msg bad">
            <?php echo htmlspecialchars($err); ?>
        </div>

    <?php } ?>


    <form method="post">

        <input
            type="hidden"
            name="csrf_token"
            value="<?php echo htmlspecialchars($csrf); ?>"
        >


        <label for="index_number">
            Index Number
        </label>

        <input
            type="text"
            id="index_number"
            name="index_number"
            placeholder="Enter your index number"
            autocomplete="username"
            autocapitalize="characters"
            spellcheck="false"
            maxlength="100"
            required
            autofocus
        >


        <button
            type="submit"
            class="btn"
        >
            Sign In
        </button>


        <a
            href="../index.php"
            class="btn gold"
        >
            Back
        </a>

    </form>

</div>


<?php include '../includes/footer.php'; ?>
