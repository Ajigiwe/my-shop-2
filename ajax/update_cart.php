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
    $quantity = (int)($_POST['quantity'] ?? 1);
    
    if ($product_id <= 0 || $quantity <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid product or quantity']);
        exit();
    }
    
    try {
        // Check if product exists and has stock
        $stmt = $pdo->prepare("SELECT stock_quantity FROM products WHERE product_id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();
        
        if (!$product) {
            echo json_encode(['success' => false, 'message' => 'Product not found']);
            exit();
        }
        
        if ($quantity > $product['stock_quantity']) {
            echo json_encode(['success' => false, 'message' => 'Insufficient stock']);
            exit();
        }
        
        if (isset($_SESSION['user_id'])) {
            // Update DB cart for logged-in users
            $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$quantity, $_SESSION['user_id'], $product_id]);

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM cart WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $cart_count = (int)$stmt->fetchColumn();
        } else {
            // Guest: session cart keyed by product_id
            $cart = asoGuestCart();
            $cart[$product_id] = $quantity;
            $_SESSION['cart'] = $cart;
            $cart_count = asoGuestCartCount();
        }

        echo json_encode(['success' => true, 'message' => 'Cart updated successfully', 'cart_count' => $cart_count]);
        
    } catch(PDOException $e) {
        error_log("Update cart error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error updating cart']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
