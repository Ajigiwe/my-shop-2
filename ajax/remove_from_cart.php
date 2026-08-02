<?php
// Include database connection
require_once '../includes/db.php';

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set JSON response header
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = (int)($_POST['product_id'] ?? 0);
    
    if ($product_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid product']);
        exit();
    }
    
    try {
        if (isset($_SESSION['user_id'])) {
            // Remove from DB cart for logged-in users
            $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$_SESSION['user_id'], $product_id]);

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM cart WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $cart_count = (int)$stmt->fetchColumn();
        } else {
            // Guest: session cart keyed by product_id
            $cart = asoGuestCart();
            unset($cart[$product_id]);
            $_SESSION['cart'] = $cart;
            $cart_count = asoGuestCartCount();
        }

        echo json_encode(['success' => true, 'message' => 'Item removed from cart', 'cart_count' => $cart_count]);
        
    } catch(PDOException $e) {
        error_log("Remove from cart error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error removing item from cart']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
