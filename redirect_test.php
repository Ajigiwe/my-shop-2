<?php
// Quick redirect test
session_start();

echo "<h1>🔍 Redirect Test</h1>\n";

if (isset($_SESSION['pending_order'])) {
    echo "<p>✅ Session data found</p>\n";
    echo "<p>Order Total: " . $_SESSION['pending_order']['total_amount'] . "</p>\n";

    // Test if we can redirect
    $test_url = "https://www.google.com";
    echo "<p>Testing redirect to: " . htmlspecialchars($test_url) . "</p>\n";

    echo "<h2>Testing header redirect...</h2>\n";
    header('Location: ' . $test_url);
    exit();
} else {
    echo "<p>❌ No session data found</p>\n";
    echo "<p><a href='test_paystack.php'>Set Session Data</a></p>\n";
}
?>
