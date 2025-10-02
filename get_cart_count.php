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
    // Check if user is logged in
    if (isset($_SESSION['user_id'])) {
        // Get cart count from database for logged-in users
        $stmt = $pdo->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $result = $stmt->fetch();
        $response['count'] = (int)($result['total'] ?? 0);
    } else if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        // Get cart count from session for guests
        $response['count'] = array_sum(array_column($_SESSION['cart'], 'quantity'));
    }
    
    $response['success'] = true;
} catch (Exception $e) {
    // Log error but don't expose it to the client
    error_log("Error getting cart count: " . $e->getMessage());
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
