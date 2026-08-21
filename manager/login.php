<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

$base = '../';
$title = 'Manager Login';

$sid = isset($_REQUEST['school'])
    ? intval($_REQUEST['school'])
    : 0;

$err = '';
$school = null;


/* -------------------------------------------------
   ALREADY LOGGED IN
------------------------------------------------- */

if (
    isset($_SESSION['manager_id']) &&
    isset($_SESSION['manager_school'])
) {
    header('Location: dashboard.php');
    exit;
}


/* -------------------------------------------------
   CHECK SELECTED SCHOOL
------------------------------------------------- */

if ($sid > 0) {

    $q = $db->prepare(
        'SELECT *
         FROM schools
         WHERE id = ?
         LIMIT 1'
    );

    $q->execute(array($sid));

    $school = $q->fetch();

    if (!$school) {
        $sid = 0;
        $err = 'The selected school could not be found.';
    }
}


/* -------------------------------------------------
   LOGIN
------------------------------------------------- */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    $sid > 0
) {

    $user = isset($_POST['username'])
        ? trim($_POST['username'])
        : '';

    $pass = isset($_POST['password'])
        ? $_POST['password']
        : '';


    /* Basic validation */

    if ($user === '') {

        $err = 'Please enter your username.';

    } elseif ($pass === '') {

        $err = 'Please enter your password.';

    } else {

        $q = $db->prepare(
            'SELECT *
             FROM managers
             WHERE username = ?
             AND school_id = ?
             LIMIT 1'
        );

        $q->execute(array(
            $user,
            $sid
        ));

        $m = $q->fetch();


        /* Verify password */

        if (
            $m &&
            password_verify(
                $pass,
                $m['password']
            )
        ) {

            /*
             * Regenerate session ID after
             * successful authentication.
             */

            session_regenerate_id(true);


            $_SESSION['manager_id'] =
                $m['id'];

            $_SESSION['manager_name'] =
                $m['username'];

            $_SESSION['manager_school'] =
                $m['school_id'];


            header(
                'Location: dashboard.php'
            );

            exit;

        } else {

            $err =
                'Wrong username or password for the selected school.';
        }
    }
}


include '../includes/header.php';


/* =================================================
   SCHOOL SELECTION
================================================= */

if ($sid == 0) {

    $q = $db->query(
        'SELECT *
         FROM schools
         ORDER BY id'
    );

    $schools = $q->fetchAll();
?>

<style>

/* -----------------------------------------
   SCHOOL SELECTION
----------------------------------------- */

.login-intro {
    text-align: center;
    max-width: 650px;
    margin: 0 auto 30px;
}

.school-grid {
    display: grid;
    grid-template-columns:
        repeat(auto-fit, minmax(240px, 1fr));

    gap: 20px;
    max-width: 900px;
    margin: 0 auto;
}

.school-card {
    display: block;
    text-decoration: none;
    color: inherit;

    background: #fff;

    border-radius: 14px;

    padding: 25px;

    box-shadow:
        0 4px 15px rgba(0,0,0,.08);

    transition:
        transform .2s ease,
        box-shadow .2s ease;

    border: 1px solid #eee;
}

.school-card:hover {
    transform: translateY(-5px);

    box-shadow:
        0 8px 25px rgba(0,0,0,.12);
}

.school-badge {
    width: 55px;
    height: 55px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 50%;

    background: #d4af37;
    color: #fff;

    font-weight: bold;

    margin-bottom: 15px;
}

.school-card h3 {
    margin: 0 0 8px;
}

.school-card p {
    color: #777;
    margin: 0;
}

.step-label {
    display: inline-block;

    padding: 6px 12px;

    border-radius: 20px;

    background: #f1f1f1;

    font-size: 13px;

    margin-bottom: 15px;
}


/* -----------------------------------------
   LOGIN CARD
----------------------------------------- */

.manager-login-card {
    max-width: 500px;
    margin: 30px auto;
}

.selected-school {
    background: #f7f7f7;

    border-radius: 10px;

    padding: 15px;

    margin-bottom: 20px;
}

.selected-school strong {
    display: block;
    font-size: 18px;
}

.password-wrapper {
    position: relative;
}

.password-wrapper input {
    width: 100%;
    padding-right: 80px;
}

.password-toggle {
    position: absolute;

    right: 8px;
    top: 50%;

    transform: translateY(-50%);

    border: 0;

    background: transparent;

    cursor: pointer;

    font-size: 13px;

    padding: 8px;
}

.login-actions {
    display: flex;

    gap: 10px;

    flex-wrap: wrap;

    margin-top: 20px;
}


/* -----------------------------------------
   MOBILE
----------------------------------------- */

@media (max-width: 600px) {

    .school-grid {
        grid-template-columns: 1fr;
    }

    .login-actions {
        flex-direction: column;
    }

    .login-actions .btn {
        width: 100%;
        text-align: center;
    }

}

</style>


<div class="login-intro">

    <span class="step-label">
        Step 1 of 2
    </span>

    <h1>
        Manager Sign In
    </h1>

    <p class="lead">
        Select your school to continue to the manager portal.
    </p>

</div>


<?php if ($err !== '') { ?>

    <div class="msg bad">

        <?php
        echo htmlspecialchars($err);
        ?>

    </div>

<?php } ?>


<?php if (count($schools) === 0) { ?>

    <div class="card">

        <h2>
            No schools available
        </h2>

        <p>
            There are currently no schools registered
            in the system.
        </p>

    </div>

<?php } else { ?>


<div class="school-grid">

<?php foreach ($schools as $s) { ?>

    <a
        class="school-card"
        href="login.php?school=<?php
            echo intval($s['id']);
        ?>"
    >

        <div class="school-badge">

            <?php
            echo htmlspecialchars(
                strtoupper(
                    substr(
                        $s['short_name'],
                        0,
                        2
                    )
                )
            );
            ?>

        </div>


        <h3>

            <?php
            echo htmlspecialchars(
                $s['name']
            );
            ?>

        </h3>


        <p>
            Continue as manager
        </p>

    </a>

<?php } ?>

</div>


<?php } ?>


<?php

/* =================================================
   LOGIN FORM
================================================= */

} else {

?>

<div class="card manager-login-card">

    <span class="step-label">
        Step 2 of 2
    </span>


    <h1>

        <?php
        echo htmlspecialchars(
            $school['short_name']
        );
        ?>

        Manager

    </h1>


    <p class="lead">
        Enter your manager account details.
    </p>


    <?php if ($err !== '') { ?>

        <div class="msg bad">

            <?php
            echo htmlspecialchars($err);
            ?>

        </div>

    <?php } ?>


    <div class="selected-school">

        <strong>

            <?php
            echo htmlspecialchars(
                $school['name']
            );
            ?>

        </strong>

        <small>
            Selected school
        </small>

    </div>


    <form
        method="post"
        autocomplete="on"
    >

        <input
            type="hidden"
            name="school"
            value="<?php
                echo intval($sid);
            ?>"
        >


        <label>
            Username
        </label>

        <input
            type="text"
            name="username"
            autocomplete="username"
            maxlength="100"
            required
        >


        <label>
            Password
        </label>

        <div class="password-wrapper">

            <input
                type="password"
                id="managerPassword"
                name="password"
                autocomplete="current-password"
                required
            >

            <button
                type="button"
                class="password-toggle"
                onclick="togglePassword()"
            >
                Show
            </button>

        </div>


        <div class="login-actions">

            <button
                type="submit"
                class="btn"
            >
                Sign In
            </button>


            <a
                href="login.php"
                class="btn gold"
            >
                Change School
            </a>

        </div>

    </form>

</div>


<script>

function togglePassword() {

    var password =
        document.getElementById(
            'managerPassword'
        );

    var button =
        document.querySelector(
            '.password-toggle'
        );


    if (password.type === 'password') {

        password.type = 'text';

        button.textContent = 'Hide';

    } else {

        password.type = 'password';

        button.textContent = 'Show';
    }
}

</script>


<?php

}

include '../includes/footer.php';

?>
