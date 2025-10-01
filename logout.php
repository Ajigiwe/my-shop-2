<?php
// Include database connection
require_once 'includes/db.php';

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Clear all session data
$_SESSION = [];

// Destroy the session
session_destroy();

// Redirect to home page
header('Location: index.php?logged_out=1');
exit();
?>
