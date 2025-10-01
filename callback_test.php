<?php
// Simple callback test
echo "<h1>✅ Callback Test</h1>\n";
echo "<p>Current time: " . date('Y-m-d H:i:s') . "</p>\n";
echo "<p>Request URI: " . $_SERVER['REQUEST_URI'] . "</p>\n";
echo "<p>Server name: " . $_SERVER['SERVER_NAME'] . "</p>\n";
echo "<p>HTTP host: " . $_SERVER['HTTP_HOST'] . "</p>\n";

if (isset($_GET['reference'])) {
    echo "<p>✅ Reference received: " . htmlspecialchars($_GET['reference']) . "</p>\n";
} else {
    echo "<p>❌ No reference parameter</p>\n";
}

echo "<p><a href='index.php'>Go Home</a></p>\n";
?>
