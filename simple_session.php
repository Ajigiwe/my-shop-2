<?php
// Ultra-simple session test
session_start();

echo "<h1>🔍 Simple Session Test</h1>\n";

if (isset($_POST['set_session'])) {
    // Set simple session data
    $_SESSION['simple_test'] = 'test_value_' . time();
    $_SESSION['test_timestamp'] = date('Y-m-d H:i:s');

    echo "<h2>✅ Session Data Set</h2>\n";
    echo "<p>Session ID: " . session_id() . "</p>\n";
    echo "<p>Test Value: " . $_SESSION['simple_test'] . "</p>\n";
    echo "<p><a href='simple_redirect.php'>Test Redirect</a></p>\n";
} else {
    echo "<h2>Current Session Data</h2>\n";
    echo "<pre>\n";
    echo "Session ID: " . session_id() . "\n";
    echo "Simple Test: " . ($_SESSION['simple_test'] ?? 'NOT SET') . "\n";
    echo "Timestamp: " . ($_SESSION['test_timestamp'] ?? 'NOT SET') . "\n";
    echo "All Keys: " . implode(', ', array_keys($_SESSION)) . "\n";
    echo "</pre>\n";

    echo "<form method='POST'>\n";
    echo "<input type='hidden' name='set_session' value='1'>\n";
    echo "<button type='submit'>Set Simple Session Data</button>\n";
    echo "</form>\n";
}
?>
