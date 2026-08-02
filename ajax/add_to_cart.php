<?php
// Include database connection
require_once '../includes/db.php';

// Start session only if not already started
session_start();

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
        // Check if product exists, is published, and has stock
        $stmt = $pdo->prepare("SELECT stock_quantity, status FROM products WHERE product_id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();
        
        if (!$product) {
            echo json_encode(['success' => false, 'message' => 'Product not found']);
            exit();
        }
        
        if ($product['status'] !== 'published') {
            echo json_encode(['success' => false, 'message' => 'Product is not available']);
            exit();
        }
        
        if ($quantity > $product['stock_quantity']) {
            echo json_encode(['success' => false, 'message' => 'Insufficient stock']);
            exit();
        }
        
        if (isset($_SESSION['user_id'])) {
            // Add to DB cart for logged-in users
            $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) 
                                  VALUES (?, ?, ?) 
                                  ON DUPLICATE KEY UPDATE quantity = quantity + ?");
            $stmt->execute([$_SESSION['user_id'], $product_id, $quantity, $quantity]);
            
            $stmt = $pdo->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $result = $stmt->fetch();
            $cart_count = $result['total'] ?? 0;
        } else {
            // Guest: session cart keyed by product_id
            $cart = asoGuestCart();
            $cart[$product_id] = (int)($cart[$product_id] ?? 0) + $quantity;
            $_SESSION['cart'] = $cart;
            $cart_count = asoGuestCartCount();
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Product added to cart successfully',
            'cart_count' => $cart_count
        ]);
        
    } catch(PDOException $e) {
        error_log("Add to cart error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error adding product to cart']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
