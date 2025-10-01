<?php
/**
 * Admin route guard
 * - Include this file on admin pages AFTER session_start()
 * - Redirects non-admin users to the login page
 */
?>
<?php
// Admin guard: include this at the top of admin pages after session start
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: ../login.php');
    exit();
}
