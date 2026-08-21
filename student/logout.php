<?php

/*
|--------------------------------------------------------------------------
| STUDENT LOGOUT
|--------------------------------------------------------------------------
|
| Completely ends the student's authenticated session.
|
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| CLEAR STUDENT SESSION DATA
|--------------------------------------------------------------------------
*/

unset(
    $_SESSION['student_id'],
    $_SESSION['student_index'],
    $_SESSION['student_school'],
    $_SESSION['student_logged_in']
);


/*
|--------------------------------------------------------------------------
| CLEAR STUDENT LOGIN SECURITY DATA
|--------------------------------------------------------------------------
*/

unset(
    $_SESSION['student_login_attempts'],
    $_SESSION['student_login_last_attempt'],
    $_SESSION['student_login_csrf']
);


/*
|--------------------------------------------------------------------------
| PREVENT CACHED AUTHENTICATED PAGES
|--------------------------------------------------------------------------
*/

header(
    'Cache-Control: no-store, no-cache, must-revalidate, max-age=0'
);

header(
    'Cache-Control: post-check=0, pre-check=0',
    false
);

header(
    'Pragma: no-cache'
);

header(
    'Expires: 0'
);


/*
|--------------------------------------------------------------------------
| DESTROY SESSION COOKIE
|--------------------------------------------------------------------------
*/

if (ini_get('session.use_cookies')) {

    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}


/*
|--------------------------------------------------------------------------
| DESTROY SESSION
|--------------------------------------------------------------------------
*/

$_SESSION = array();

session_destroy();


/*
|--------------------------------------------------------------------------
| REDIRECT
|--------------------------------------------------------------------------
*/

header(
    'Location: ../index.php?logout=1'
);

exit;
