<?php

require_once '../includes/db.php';
require_once '../includes/auth.php';

$base = '../';
$title = 'Student Login';


/*
|--------------------------------------------------------------------------
| START SESSION
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| IF ALREADY LOGGED IN
|--------------------------------------------------------------------------
*/

if (
    isset($_SESSION['student_id']) &&
    isset($_SESSION['student_school'])
) {

    header('Location: events.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| GET SCHOOL
|--------------------------------------------------------------------------
*/

$sid = isset($_GET['school'])
    ? intval($_GET['school'])
    : 0;


/*
|--------------------------------------------------------------------------
| Also allow school ID from POST
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['school'])
) {

    $sid = intval($_POST['school']);
}


if ($sid <= 0) {

    header('Location: ../index.php');
    exit;
}


$q = $db->prepare(
    'SELECT *
     FROM schools
     WHERE id = ?
     LIMIT 1'
);

$q->execute(array($sid));

$school = $q->fetch(PDO::FETCH_ASSOC);


if (!$school) {

    header('Location: ../index.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| CSRF TOKEN
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['student_login_csrf'])) {

    $_SESSION['student_login_csrf'] =
        bin2hex(random_bytes(32));
}

$csrf = $_SESSION['student_login_csrf'];


/*
|--------------------------------------------------------------------------
| LOGIN RATE LIMITING
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['student_login_attempts'])) {

    $_SESSION['student_login_attempts'] = 0;
}

if (!isset($_SESSION['student_login_last_attempt'])) {

    $_SESSION['student_login_last_attempt'] = 0;
}


/*
|--------------------------------------------------------------------------
| VARIABLES
|--------------------------------------------------------------------------
*/

$err = '';

$index = '';

$maxAttempts = 5;

$lockoutSeconds = 300;


/*
|--------------------------------------------------------------------------
| PROCESS LOGIN
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {


    /*
    |--------------------------------------------------------------------------
    | CSRF CHECK
    |--------------------------------------------------------------------------
    */

    $submittedToken = isset($_POST['csrf_token'])
        ? $_POST['csrf_token']
        : '';


    if (
        !hash_equals(
            $_SESSION['student_login_csrf'],
            $submittedToken
        )
    ) {

        $err =
            'Security verification failed. Please refresh the page and try again.';

    } else {


        /*
        |--------------------------------------------------------------------------
        | RATE LIMIT RESET
        |--------------------------------------------------------------------------
        */

        $now = time();

        $timeSinceAttempt =
            $now -
            intval(
                $_SESSION['student_login_last_attempt']
            );


        if (
            $timeSinceAttempt >
            $lockoutSeconds
        ) {

            $_SESSION['student_login_attempts'] = 0;
        }


        /*
        |--------------------------------------------------------------------------
        | CHECK LOCKOUT
        |--------------------------------------------------------------------------
        */

        if (
            $_SESSION['student_login_attempts']
            >= $maxAttempts
        ) {

            $remainingSeconds =
                max(
                    0,
                    $lockoutSeconds -
                    $timeSinceAttempt
                );


            $remainingMinutes =
                max(
                    1,
                    ceil(
                        $remainingSeconds / 60
                    )
                );


            $err =
                'Too many unsuccessful attempts. ' .
                'Please wait approximately ' .
                $remainingMinutes .
                ' minute(s) and try again.';

        } else {


            /*
            |--------------------------------------------------------------------------
            | GET INDEX NUMBER
            |--------------------------------------------------------------------------
            */

            $index = isset($_POST['index_number'])
                ? trim($_POST['index_number'])
                : '';


            /*
            |--------------------------------------------------------------------------
            | NORMALIZE INDEX NUMBER
            |--------------------------------------------------------------------------
            */

            $index = preg_replace(
                '/\s+/',
                '',
                $index
            );


            /*
            |--------------------------------------------------------------------------
            | VALIDATION
            |--------------------------------------------------------------------------
            */

            if ($index === '') {

                $err =
                    'Please enter your index number.';

            } elseif (strlen($index) > 100) {

                $err =
                    'The index number is too long.';

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
                | FIND STUDENT
                |--------------------------------------------------------------------------
                */

                $q = $db->prepare(
                    'SELECT id,
                            index_number,
                            school_id
                     FROM students
                     WHERE LOWER(index_number) = LOWER(?)
                     AND school_id = ?
                     LIMIT 1'
                );


                $q->execute(array(
                    $index,
                    $sid
                ));


                $student =
                    $q->fetch(PDO::FETCH_ASSOC);


                /*
                |--------------------------------------------------------------------------
                | LOGIN SUCCESS
                |--------------------------------------------------------------------------
                */

                if ($student) {


                    /*
                    | Prevent session fixation.
                    */

                    session_regenerate_id(true);


                    /*
                    | Store student session.
                    */

                    $_SESSION['student_id'] =
                        $student['id'];

                    $_SESSION['student_index'] =
                        $student['index_number'];

                    $_SESSION['student_school'] =
                        $student['school_id'];

                    $_SESSION['student_logged_in'] =
                        true;


                    /*
                    | Reset failed attempts.
                    */

                    $_SESSION['student_login_attempts'] =
                        0;

                    $_SESSION['student_login_last_attempt'] =
                        0;


                    /*
                    | Create fresh CSRF token.
                    */

                    $_SESSION['student_login_csrf'] =
                        bin2hex(
                            random_bytes(32)
                        );


                    /*
                    | Redirect to events.
                    */

                    header(
                        'Location: events.php'
                    );

                    exit;


                } else {


                    /*
                    |--------------------------------------------------------------------------
                    | LOGIN FAILED
                    |--------------------------------------------------------------------------
                    */

                    $_SESSION['student_login_attempts']++;

                    $_SESSION['student_login_last_attempt'] =
                        $now;


                    $attemptsLeft =
                        $maxAttempts -
                        $_SESSION['student_login_attempts'];


                    if ($attemptsLeft > 0) {

                        $err =
                            'Index number not found for this school. ' .
                            'Please check it and try again. ' .
                            $attemptsLeft .
                            ' attempt(s) remaining.';

                    } else {

                        $err =
                            'Too many unsuccessful attempts. ' .
                            'Please wait a few minutes before trying again.';
                    }
                }
            }
        }
    }
}


include '../includes/header.php';

?>


<style>

/*
|--------------------------------------------------------------------------
| STUDENT LOGIN PAGE
|--------------------------------------------------------------------------
*/

.student-login-card {

    max-width: 500px;

    margin: 35px auto;

}


/*
|--------------------------------------------------------------------------
| SCHOOL HEADER
|--------------------------------------------------------------------------
*/

.school-login-header {

    text-align: center;

    margin-bottom: 25px;

}


.school-badge {

    display: inline-block;

    padding: 8px 16px;

    border-radius: 30px;

    font-weight: bold;

    font-size: 14px;

    margin-bottom: 12px;

}


.school-login-header h1 {

    margin-bottom: 8px;

}


.school-login-header p {

    margin-top: 0;

}


/*
|--------------------------------------------------------------------------
| LOGIN INFORMATION
|--------------------------------------------------------------------------
*/

.login-info {

    margin: 20px 0;

    padding: 15px;

    border-radius: 10px;

    background: rgba(0,0,0,0.04);

}


.login-info p {

    margin: 5px 0;

}


.login-help {

    font-size: 13px;

    opacity: 0.75;

    margin-top: 12px;

}


/*
|--------------------------------------------------------------------------
| BUTTONS
|--------------------------------------------------------------------------
*/

.login-actions {

    display: flex;

    gap: 10px;

    margin-top: 20px;

    flex-wrap: wrap;

}


.login-actions .btn {

    flex: 1;

    text-align: center;

}


/*
|--------------------------------------------------------------------------
| MOBILE
|--------------------------------------------------------------------------
*/

@media (max-width: 600px) {

    .student-login-card {

        margin: 15px 0;

    }


    .login-actions {

        flex-direction: column;

    }


    .login-actions .btn {

        width: 100%;

    }

}

</style>


<!-- =========================================================
     LOGIN CARD
========================================================= -->

<div class="card student-login-card">


    <!-- =====================================================
         SCHOOL INFORMATION
    ====================================================== -->

    <div class="school-login-header">


        <div class="school-badge">

            <?php

            echo htmlspecialchars(
                $school['short_name']
            );

            ?>

        </div>


        <h1>

            Student Sign In

        </h1>


        <p class="lead">

            <?php

            echo htmlspecialchars(
                $school['name']
            );

            ?>

        </p>

    </div>


    <!-- =====================================================
         ERROR MESSAGE
    ====================================================== -->

    <?php if ($err !== '') { ?>

        <div
            class="msg bad"
            role="alert"
        >

            <?php

            echo htmlspecialchars(
                $err
            );

            ?>

        </div>

    <?php } ?>


    <!-- =====================================================
         LOGIN INFORMATION
    ====================================================== -->

    <div class="login-info">

        <p>

            <strong>
                How to sign in
            </strong>

        </p>


        <p>
            Enter the index number registered
            by your school.
        </p>


        <p class="login-help">

            You do not need a password to access
            the student event portal.

        </p>

    </div>


    <!-- =====================================================
         LOGIN FORM
    ====================================================== -->

    <form
        method="post"
        autocomplete="on"
    >


        <!-- SCHOOL -->

        <input
            type="hidden"
            name="school"
            value="<?php
                echo intval($sid);
            ?>"
        >


        <!-- CSRF -->

        <input
            type="hidden"
            name="csrf_token"
            value="<?php
                echo htmlspecialchars(
                    $csrf
                );
            ?>"
        >


        <!-- INDEX NUMBER -->

        <label
            for="index_number"
        >

            Index Number

        </label>


        <input
            type="text"
            id="index_number"
            name="index_number"
            value="<?php
                echo htmlspecialchars(
                    $index
                );
            ?>"
            placeholder="e.g. 10234567"
            autocomplete="username"
            autocapitalize="characters"
            spellcheck="false"
            maxlength="100"
            required
            autofocus
        >


        <!-- ACTIONS -->

        <div class="login-actions">


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


        </div>


    </form>


    <!-- =====================================================
         FOOTER MESSAGE
    ====================================================== -->

    <p
        class="login-help"
        style="text-align:center;"
    >

        Having trouble signing in?
        Make sure you selected the correct school
        and entered your index number correctly.

    </p>


</div>


<?php include '../includes/footer.php'; ?>
