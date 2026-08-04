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

// Site URL configuration
if (!defined('SITE_URL')) {
    define('SITE_URL', rtrim($_ENV['SITE_URL'] ?? 'http://localhost/my-shop-2-main/', '/') . '/');
}

// Per-install HMAC secret for "remember me" cookies
if (!defined('REMEMBER_SECRET')) {
    define('REMEMBER_SECRET', hash('sha256', ($_ENV['DB_PASS'] ?? '') . '|' . SITE_URL . '|aso-remember'));
}

// Global Session Initialization (ensures sessions persist across root and subfolders)
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    $is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
             || ($_SERVER['SERVER_PORT'] ?? 80) == 443
             || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '', // Host-only cookie prevents browser rejection across subfolders/redirects
        'secure'   => $is_https,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

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
        die("Database connection failed. Please try again later.<br>Error Details: " . htmlspecialchars($e->getMessage()));
    }
}

/**
 * Sanitize input function
 *
 * @param string $data Input data to sanitize
 * @return string Sanitized data
 */
function sanitizeInput($data) {
    // Basic trimming and slash stripping for safe DB storage
    // htmlspecialchars should ONLY be used on output to the template
    $data = trim($data);
    $data = stripslashes($data);
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

if (!function_exists('getProductImage')) {
    /**
     * Resolve product image path dynamically with fallback checks
     * @param string|null $filename
     * @return string Relative URL path to image
     */
    function getProductImage($filename) {
        if (empty($filename)) {
            return 'assets/images/placeholder.jpg';
        }
        
        $cleanName = basename($filename);
        $baseDir = dirname(__DIR__);
        
        // 1. Direct path check in assets/images/
        if (file_exists($baseDir . '/assets/images/' . $cleanName)) {
            return 'assets/images/' . $cleanName;
        }
        
        // 2. Check in assets/images/products/
        if (file_exists($baseDir . '/assets/images/products/' . $cleanName)) {
            return 'assets/images/products/' . $cleanName;
        }

        // 2b. Check in assets/images/categories/ (used by category tiles)
        if (file_exists($baseDir . '/assets/images/categories/' . $cleanName)) {
            return 'assets/images/categories/' . $cleanName;
        }

        // 2c. Check in assets/images/promo/ (used by promo popup)
        if (file_exists($baseDir . '/assets/images/promo/' . $cleanName)) {
            return 'assets/images/promo/' . $cleanName;
        }

        // 3. Check full raw path if provided
        $trimmed = ltrim($filename, '/');
        if (file_exists($baseDir . '/' . $trimmed)) {
            return $trimmed;
        }
        
        // 4. Default fallback placeholder
        return 'assets/images/placeholder.jpg';
    }
}

if (!function_exists('loadSiteSettings')) {
    /**
     * Load site settings into an associative array.
     * Safe to call multiple times; used before header() in POST handlers.
     * @param PDO|null $pdo
     * @return array
     */
    function loadSiteSettings($pdo) {
        $settings = [];
        if ($pdo) {
            try {
                $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $settings[$row['setting_key']] = $row['setting_value'];
                }
            } catch (PDOException $e) {
                // Fallback if table doesn't exist
            }
        }
        return $settings;
    }
}

if (!function_exists('createAdminNotification')) {
    /**
     * Record an admin notification (bell in the admin header).
     * @param string $type info|order|contact|newsletter
     * @param string $message Short message
     * @param string|null $link Relative admin URL to open on click
     */
    function createAdminNotification($type, $message, $link = null) {
        global $pdo;
        if (!$pdo) return;
        try {
            $stmt = $pdo->prepare("INSERT INTO admin_notifications (type, message, link) VALUES (?, ?, ?)");
            $stmt->execute([$type, $message, $link]);
        } catch (PDOException $e) {
            error_log("Notification insert error: " . $e->getMessage());
        }
    }
}

/* ── Guest cart (session-backed, merged on login/register) ─────────────── */

if (!function_exists('asoGuestCart')) {
    /**
     * Current guest cart: [product_id => quantity]. Logged-in users use DB.
     * @return array
     */
    function asoGuestCart() {
        $cart = $_SESSION['cart'] ?? [];
        return is_array($cart) ? $cart : [];
    }
}

if (!function_exists('asoGuestCartCount')) {
    /**
     * Total quantity across the session guest cart.
     * @return int
     */
    function asoGuestCartCount() {
        return array_sum(asoGuestCart());
    }
}

if (!function_exists('asoCartCount')) {
    /**
     * Total cart quantity for the current visitor (DB for logged-in, session for guests).
     * @param PDO|null $pdo
     * @return int
     */
    function asoCartCount($pdo = null) {
        if (isset($_SESSION['user_id'])) {
            if (!$pdo) return 0;
            try {
                $stmt = $pdo->prepare("SELECT COALESCE(SUM(quantity),0) FROM cart WHERE user_id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                return (int)$stmt->fetchColumn();
            } catch (PDOException $e) {
                return 0;
            }
        }
        return asoGuestCartCount();
    }
}

if (!function_exists('asoMergeGuestCart')) {
    /**
     * Move the session guest cart into a user's DB cart, then clear the session cart.
     * @param PDO|null $pdo
     * @param int $user_id
     */
    function asoMergeGuestCart($pdo, $user_id) {
        if (!$pdo) return;
        $cart = asoGuestCart();
        if (empty($cart)) return;
        try {
            $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)
                                   ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)");
            foreach ($cart as $pid => $qty) {
                $stmt->execute([$user_id, (int)$pid, max(1, (int)$qty)]);
            }
            unset($_SESSION['cart']);
        } catch (PDOException $e) {
            error_log("Merge guest cart error: " . $e->getMessage());
        }
    }
}

if (!function_exists('asoGetCartItems')) {
    /**
     * Normalized cart rows for the current visitor (DB for logged-in, session for guests).
     * Each row: product_id, quantity, name, price, image, stock_quantity, category_name, all_images.
     * @param PDO|null $pdo
     * @return array
     */
    function asoGetCartItems($pdo = null) {
        $items = [];
        $base = "SELECT p.product_id, p.name, p.price, p.image, p.stock_quantity, cat.category_name,
                        (SELECT GROUP_CONCAT(image_path ORDER BY is_primary DESC, image_id ASC)
                         FROM product_images WHERE product_id = p.product_id) as all_images
                FROM products p
                LEFT JOIN categories cat ON p.category_id = cat.category_id";
        try {
            if (isset($_SESSION['user_id'])) {
                if (!$pdo) return [];
                $stmt = $pdo->prepare("SELECT c.product_id, c.quantity, p.name, p.price, p.image, p.stock_quantity, cat.category_name,
                                       (SELECT GROUP_CONCAT(image_path ORDER BY is_primary DESC, image_id ASC)
                                        FROM product_images WHERE product_id = p.product_id) as all_images
                                       FROM cart c
                                       JOIN products p ON c.product_id = p.product_id
                                       LEFT JOIN categories cat ON p.category_id = cat.category_id
                                       WHERE c.user_id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $items = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            } else {
                $cart = asoGuestCart();
                if (empty($cart) || !$pdo) return [];
                $ids = array_keys($cart);
                $in = implode(',', array_fill(0, count($ids), '?'));
                $stmt = $pdo->prepare("$base WHERE p.product_id IN ($in)");
                $stmt->execute($ids);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
                foreach ($rows as $row) {
                    $row['quantity'] = (int)$cart[$row['product_id']];
                    $items[] = $row;
                }
            }
        } catch (PDOException $e) {
            error_log("Cart items error: " . $e->getMessage());
        }
        return $items;
    }
}
?>
