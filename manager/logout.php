<?php

session_start();

/*
 * Clear all session variables.
 */
$_SESSION = array();


/*
 * Remove the session cookie if cookies
 * are being used.
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
 * Destroy the session.
 */
session_destroy();


/*
 * Send the manager back to the home page.
 */
header('Location: ../index.php');
exit;
