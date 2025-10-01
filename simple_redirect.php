<?php
// Ultra-simple redirect test
session_start();

echo "<h1>🔍 Simple Redirect Test</h1>\n";

if (isset($_SESSION['simple_test'])) {
    echo "<p>✅ Session data found: " . $_SESSION['simple_test'] . "</p>\n";

    // Test simple redirect
    $redirect_url = "https://www.google.com";
    echo "<p>Testing redirect to: " . htmlspecialchars($redirect_url) . "</p>\n";

    echo "<h2>Executing header redirect...</h2>\n";
    header('Location: ' . $redirect_url);
    exit();
} else {
    echo "<p>❌ No session data found</p>\n";
    echo "<p><a href='simple_session.php'>Go back to set session data</a></p>\n";
}
?>
