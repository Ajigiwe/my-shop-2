<?php
/**
 * Environment Configuration Loader
 * Loads environment variables from .env file
 */

// Load environment variables from .env file
function loadEnv($filePath = '.env') {
    if (!file_exists($filePath)) {
        return false;
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        // Parse key=value pairs
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Remove quotes if present
            if (preg_match('/^["\'](.*)["\']$/', $value, $matches)) {
                $value = $matches[1];
            }

            // Set environment variable
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }

    return true;
}

// Load environment variables
loadEnv();

// Set default values if environment variables are not set
$_ENV['DB_HOST'] = $_ENV['DB_HOST'] ?? 'localhost';
$_ENV['DB_NAME'] = $_ENV['DB_NAME'] ?? 'ecommerce_db';
$_ENV['DB_USER'] = $_ENV['DB_USER'] ?? 'root';
$_ENV['DB_PASS'] = $_ENV['DB_PASS'] ?? '';
$_ENV['PAYSTACK_PUBLIC_KEY'] = $_ENV['PAYSTACK_PUBLIC_KEY'] ?? '';
$_ENV['PAYSTACK_SECRET_KEY'] = $_ENV['PAYSTACK_SECRET_KEY'] ?? '';
?>
