<?php
// IMPORTANT: Temporary development utility to reset the admin password.
// Protect with a one-time token in the query string: reset_admin.php?token=YOUR_SECRET
// Delete this file after use.

// Minimal bootstrap
require_once __DIR__ . '/../includes/db.php';

$expectedToken = 'CHANGE_ME_SECURE_TOKEN'; // Set a temporary secret here before running.
$providedToken = $_GET['token'] ?? '';

if (!$expectedToken || $providedToken !== $expectedToken) {
    http_response_code(403);
    echo 'Forbidden. Missing or invalid token.';
    exit;
}

try {
    // Set admin password to 'admin123'
    $newHash = password_hash('admin123', PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("UPDATE users SET password = ?, role = 'admin' WHERE email = ?");
    $stmt->execute([$newHash, 'admin@shop.com']);

    echo 'Admin password reset to admin123 for admin@shop.com. Please DELETE this file now: admin/reset_admin.php';
} catch (PDOException $e) {
    http_response_code(500);
    echo 'Error resetting admin: ' . htmlspecialchars($e->getMessage());
}

