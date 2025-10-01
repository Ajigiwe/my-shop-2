<?php
// Session ID comparison test
session_start();

echo "<h1>🔍 Session ID Debug</h1>\n";
echo "<h2>Current Request:</h2>\n";
echo "<pre>\n";
echo "Session ID: " . session_id() . "\n";
echo "Cookie: " . ($_COOKIE[session_name()] ?? 'NOT SET') . "\n";
echo "Session Name: " . session_name() . "\n";
echo "Request URI: " . $_SERVER['REQUEST_URI'] . "\n";
echo "</pre>\n";

echo "<h2>Session Data:</h2>\n";
echo "<pre>\n";
echo "User ID: " . ($_SESSION['user_id'] ?? 'NOT SET') . "\n";
echo "Pending Order: " . (isset($_SESSION['pending_order']) ? 'EXISTS' : 'MISSING') . "\n";
if (isset($_SESSION['pending_order'])) {
    echo "Order Total: " . $_SESSION['pending_order']['total_amount'] . "\n";
}
echo "</pre>\n";

echo "<h2>Set Test Data:</h2>\n";
if (isset($_POST['set_test'])) {
    $_SESSION['debug_test'] = 'test_value_' . time();
    echo "<p>✅ Test data set!</p>\n";
}

echo "<form method='POST'>\n";
echo "<input type='hidden' name='set_test' value='1'>\n";
echo "<button type='submit'>Set Test Data</button>\n";
echo "</form>\n";

echo "<hr>\n";
echo "<p><a href='session_debug.php'>Session Debug</a> | <a href='test_paystack.php'>Paystack Test</a></p>\n";
?>
