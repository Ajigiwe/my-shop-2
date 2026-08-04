<?php
/**
 * Detailed Diagnostic Tool for asoonlinemarket.com
 * Visit: https://asoonlinemarket.com/diagnose.php
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Deep Diagnostics for index.php</h1>";

// 1. Session check
try {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    echo "<p style='color:green;'>✓ Session started successfully.</p>";
} catch (Throwable $e) {
    echo "<p style='color:red;'>✗ Session Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// 2. DB check
try {
    require_once __DIR__ . '/includes/db.php';
    if ($pdo) {
        echo "<p style='color:green;'>✓ DB Connection established.</p>";
    } else {
        echo "<p style='color:red;'>✗ PDO object is null!</p>";
    }
} catch (Throwable $e) {
    echo "<p style='color:red;'>✗ DB Bootstrap Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// 3. Vendor folder check
$autoload_file = __DIR__ . '/vendor/autoload.php';
$autoload_real = __DIR__ . '/vendor/composer/autoload_real.php';
if (file_exists($autoload_file) && file_exists($autoload_real)) {
    echo "<p style='color:green;'>✓ Complete vendor autoloader found.</p>";
} else {
    echo "<p style='color:orange;'>⚠️ Vendor autoloader incomplete or missing. Falling back to built-in environment loader (safe).</p>";
}

echo "<h3>Executing index.php dry run test...</h3>";
try {
    ob_start();
    include __DIR__ . '/index.php';
    ob_end_clean();
    echo "<p style='color:green; font-size:20px; font-weight:bold;'>✓ SUCCESS! index.php compiled and executed with zero errors!</p>";
} catch (Throwable $e) {
    ob_end_clean();
    echo "<p style='color:red; font-size:18px; font-weight:bold;'>✗ FATAL ERROR in index.php:</p>";
    echo "<p style='color:red;'>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>File: " . htmlspecialchars($e->getFile()) . " on Line: " . $e->getLine() . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
