<?php
session_start();

// Clear session data
$_SESSION = [];

// Destroy session
session_destroy();

// Prevent browser from caching protected pages
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// Redirect to login page
header('Location: ../index.php?logout=success');
exit;