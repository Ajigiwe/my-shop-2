<?php
/**
 * PHP Configuration Test
 */

// Check PHP settings that might affect form handling
echo "<h1>PHP Configuration Test</h1>";

echo "<h2>POST Data Settings:</h2>";
echo "<p>max_input_vars: " . ini_get('max_input_vars') . "</p>";
echo "<p>post_max_size: " . ini_get('post_max_size') . "</p>";
echo "<p>max_input_time: " . ini_get('max_input_time') . "</p>";
echo "<p>file_uploads: " . ini_get('file_uploads') . "</p>";
echo "<p>upload_max_filesize: " . ini_get('upload_max_filesize') . "</p>";

echo "<h2>Request Information:</h2>";
echo "<p>REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD'] . "</p>";
echo "<p>CONTENT_TYPE: " . ($_SERVER['CONTENT_TYPE'] ?? 'Not set') . "</p>";
echo "<p>CONTENT_LENGTH: " . ($_SERVER['CONTENT_LENGTH'] ?? 'Not set') . "</p>";
echo "<p>HTTP_USER_AGENT: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'Not set') . "</p>";

echo "<h2>Test Form Submission:</h2>";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<p style='color: green;'>✓ Form submitted</p>";
    echo "<h3>POST Data:</h3>";
    echo "<pre>";
    var_dump($_POST);
    echo "</pre>";

    echo "<h3>Raw POST Data:</h3>";
    echo "<pre>" . htmlspecialchars(file_get_contents('php://input')) . "</pre>";
} else {
    echo "<p style='color: orange;'>No form submission detected</p>";
}

echo "<h2>Test Form:</h2>";
echo "<form method='post' action=''>";
echo "<input type='hidden' name='test_field' value='test_value'>";
echo "<button type='submit'>Test Form</button>";
echo "</form>";

echo "<h2>Server Information:</h2>";
echo "<p>Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "</p>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>PHP SAPI: " . php_sapi_name() . "</p>";
?>
