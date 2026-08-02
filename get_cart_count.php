<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once 'includes/db.php';

// Initialize response array
$response = [
    'success' => false,
    'count' => 0
];

try {
    // Works for logged-in users (DB cart) and guests (session cart)
    $response['count'] = asoCartCount($pdo);
    $response['success'] = true;
} catch (Exception $e) {
    // Log error but don't expose it to the client
    error_log("Error getting cart count: " . $e->getMessage());
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
