<?php
/**
 * Remove from Wishlist AJAX Handler
 */

require_once '../includes/db.php';

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in to manage wishlist']);
    exit();
}

// Check if required data is provided
if (!isset($_POST['product_id']) || empty($_POST['product_id'])) {
    echo json_encode(['success' => false, 'message' => 'Product ID is required']);
    exit();
}

$user_id = $_SESSION['user_id'];
$product_id = (int)$_POST['product_id'];

try {
    // Remove from wishlist
    $stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$user_id, $product_id]);
    
    if ($stmt->rowCount() > 0) {
        // Get updated wishlist count
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM wishlist WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $wishlist_count = $stmt->fetch()['count'];
        
        echo json_encode([
            'success' => true, 
            'message' => 'Product removed from wishlist!',
            'wishlist_count' => $wishlist_count
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Product not found in wishlist']);
    }
    
} catch(PDOException $e) {
    error_log("Remove from wishlist error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>
