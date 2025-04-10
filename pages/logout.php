<?php
// Start the session
session_start();

// Include database connection and functions
require_once('../includes/db_connect.php');
require_once('../includes/functions.php');

// Record logout activity if the user is logged in
if (isset($_SESSION['user_id'])) {
    record_activity($conn, $_SESSION['user_id'], 'logout', 'You logged out from your account');
}

// Unset all of the session variables
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finally, destroy the session
session_destroy();

// Redirect to the home page
header("Location: ../index.php");
exit();
?> 