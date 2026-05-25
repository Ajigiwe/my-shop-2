<?php
// Clear output buffering and start session
ob_start();
session_start();

if (!isset($_SESSION['test_counter'])) {
    $_SESSION['test_counter'] = 1;
} else {
    $_SESSION['test_counter']++;
}

echo "<h1>PHP Session Test</h1>";
echo "Session ID: <code>" . session_id() . "</code><br>";
echo "Counter: <strong>" . $_SESSION['test_counter'] . "</strong><br><br>";
echo "<a href='session_test.php' style='padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;'>Refresh Page</a>";

echo "<br><br><h3>Session Debug Info:</h3>";
echo "<pre>";
print_r(session_get_cookie_params());
echo "</pre>";
?>
