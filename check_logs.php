<?php
/**
 * Check Error Logs
 * Shows the latest error log entries
 */

echo "<h2>Error Log Check</h2>";

// Check if error log file exists
$error_log_file = 'logs/email_2025-10-03.log';
if (file_exists($error_log_file)) {
    echo "<p><strong>Reading error log:</strong> $error_log_file</p>";
    
    $log_content = file_get_contents($error_log_file);
    $lines = explode("\n", $log_content);
    
    // Show last 20 lines
    $recent_lines = array_slice($lines, -20);
    
    echo "<h3>Recent Log Entries:</h3>";
    echo "<pre>";
    foreach ($recent_lines as $line) {
        if (!empty(trim($line))) {
            echo htmlspecialchars($line) . "\n";
        }
    }
    echo "</pre>";
} else {
    echo "<p><strong>Error log file not found:</strong> $error_log_file</p>";
}

// Also check PHP error log
echo "<h3>PHP Error Log:</h3>";
echo "<p><strong>PHP Error Log Location:</strong> " . ini_get('error_log') . "</p>";

// Test error logging
error_log("Test error log entry from check_logs.php");
echo "<p><strong>Test error log entry added.</strong></p>";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error Log Check</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Error Log Check</h4>
                    </div>
                    <div class="card-body">
                        <p>This page shows recent error log entries to help debug the checkout process.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
