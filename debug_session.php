<?php
// Session debugging with cookie tracking
session_start();

echo "<h1>🔍 Session & Cookie Debug</h1>\n";

echo "<h2>Request Information:</h2>\n";
echo "<pre>\n";
echo "Session ID: " . session_id() . "\n";
echo "Session Name: " . session_name() . "\n";
echo "Cookie Present: " . (isset($_COOKIE[session_name()]) ? 'YES' : 'NO') . "\n";
if (isset($_COOKIE[session_name()])) {
    echo "Cookie Value: " . $_COOKIE[session_name()] . "\n";
}
echo "Request Method: " . $_SERVER['REQUEST_METHOD'] . "\n";
echo "Request URI: " . $_SERVER['REQUEST_URI'] . "\n";
echo "HTTP Referer: " . ($_SERVER['HTTP_REFERER'] ?? 'NOT SET') . "\n";
echo "</pre>\n";

echo "<h2>Session Data:</h2>\n";
echo "<pre>\n";
echo "User ID: " . ($_SESSION['user_id'] ?? 'NOT SET') . "\n";
echo "Pending Order: " . (isset($_SESSION['pending_order']) ? 'EXISTS' : 'MISSING') . "\n";
if (isset($_SESSION['pending_order'])) {
    echo "Order Total: " . $_SESSION['pending_order']['total_amount'] . "\n";
}
echo "All Session Keys: " . implode(', ', array_keys($_SESSION)) . "\n";
echo "</pre>\n";

echo "<h2>Set Test Session Data:</h2>\n";
if (isset($_POST['set_session'])) {
    $_SESSION['debug_user_id'] = 999;
    $_SESSION['debug_pending_order'] = [
        'total_amount' => 999.99,
        'payment_method' => 'debug',
        'debug_time' => date('Y-m-d H:i:s')
    ];

    echo "<p>✅ Debug session data set!</p>\n";
    echo "<p><a href='?'>Refresh to test persistence</a></p>\n";
    echo "<p><a href='paystack_payment.php'>Test Paystack Payment</a></p>\n";
}

echo "<form method='POST'>\n";
echo "<input type='hidden' name='set_session' value='1'>\n";
echo "<button type='submit'>Set Debug Session Data</button>\n";
echo "</form>\n";

echo "<hr>\n";
echo "<p><a href='session_debug.php'>Session Debug</a> | <a href='test_paystack.php'>Paystack Test</a></p>\n";
?>
