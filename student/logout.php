<?php

session_start();

/*
|--------------------------------------------------------------------------
| Clear Student Session Data
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
| Clear Session Cookie
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
| Destroy Session
|--------------------------------------------------------------------------
*/
session_destroy();

/*
|--------------------------------------------------------------------------
| Redirect
|--------------------------------------------------------------------------
*/
header('Location: ../index.php');
exit;