<?php
/**
 * Database bootstrap and shared helpers
 * - Establishes PDO connection with secure defaults
 * - Provides common sanitization and validation helpers
 * - Exposes password hashing/verification utilities
 * - Defines currency helpers (Ghana Cedis) used across the app
 */
/**
 * Database Connection Configuration
 *
 * This file establishes a secure database connection using PDO
 * with proper error handling and security measures.
 */

// Load environment variables
require_once __DIR__ . '/env_loader.php';

// Database configuration from environment variables
$host = $_ENV['DB_HOST'] ?? 'localhost';
$dbname = $_ENV['DB_NAME'] ?? 'ecommerce_db';
$username = $_ENV['DB_USER'] ?? 'root';
$password = $_ENV['DB_PASS'] ?? ''; // Default XAMPP password is empty

try {
    // Create PDO connection with UTF-8 charset (prevents mojibake)
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);

    // Throw exceptions on DB errors
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Return rows as associative arrays by default
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Use native prepared statements (safer against SQL injection)
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

} catch(PDOException $e) {
    // Log error for debugging (in production, don't show detailed errors)
    error_log("Database connection error: " . $e->getMessage());

    // Instead of dying, set PDO to null for graceful degradation
    $pdo = null;

    // Only die if this is a critical page that absolutely needs database
    // For navbar and other optional components, allow graceful degradation
    if (!defined('ALLOW_DB_GRACEFUL_DEGRADATION') || !ALLOW_DB_GRACEFUL_DEGRADATION) {
        die("Database connection failed. Please try again later.");
    }
}

/**
 * Sanitize input function
 *
 * @param string $data Input data to sanitize
 * @return string Sanitized data
 */
function sanitizeInput($data) {
    // Basic HTML entity encoding and trimming for safe echoing in templates
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Validate email format
 *
 * @param string $email Email to validate
 * @return bool True if valid email
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Validate phone number (basic validation)
 *
 * @param string $phone Phone number to validate
 * @return bool True if valid phone
 */
function validatePhone($phone) {
    // Remove all non-numeric characters
    $phone = preg_replace('/\D/', '', $phone);
    // Check if phone is between 10-15 digits
    return strlen($phone) >= 10 && strlen($phone) <= 15;
}

/**
 * Hash password securely
 *
 * @param string $password Plain text password
 * @return string Hashed password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify password against hash
 *
 * @param string $password Plain text password
 * @param string $hash Hashed password
 * @return bool True if password matches
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Currency helpers (Ghana Cedis)
 */
// Global currency symbol
if (!isset($currency_symbol)) {
    $currency_symbol = 'GH₵';
}

if (!function_exists('formatCurrency')) {
    /**
     * Format numeric amount as Ghana Cedis with 2 decimals
     * @param float|int|string $amount
     * @return string
     */
    function formatCurrency($amount) {
        global $currency_symbol;
        $num = is_numeric($amount) ? (float)$amount : 0.0;
        return $currency_symbol . number_format($num, 2);
    }
}
?>
