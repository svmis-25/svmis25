<?php
// session_check.php

define('SESSION_TIMEOUT', 1800); // Set timeout duration in seconds

if (isset($_SESSION['LAST_ACTIVITY'])) {
    $idleTime = time() - $_SESSION['LAST_ACTIVITY'];

    if ($idleTime > SESSION_TIMEOUT) {
        session_unset();
        session_destroy();
        header("Location: index.php?timeout=true");
        exit();
    }
}

$_SESSION['LAST_ACTIVITY'] = time(); // Update last activity time
?>