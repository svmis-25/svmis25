<?php
session_start();
require_once __DIR__ . '/config.php';

// Check if the user is logged in before trying to log activity
if (isset($_SESSION["userID"]) && isset($_SESSION["userrole"])) {
    $current_user_id = $_SESSION["userID"];
    $current_user_role_id = $_SESSION["userrole"];

    // Record Activity
    require_once "activityLogsFunction.php";
    $activity_id = 2; // represents the activity ID for logout
    saveActivityLog($current_user_role_id, $activity_id, $current_user_id);
}

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to the login page
header("Location: index");
exit;
?>
