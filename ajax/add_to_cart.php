<?php
// Include database connection
require_once '../includes/db.php';

// Start session only if not already started
session_start();

// Set JSON response header
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to add items to cart']);
    exit();
}

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
        
        // Add to cart
        $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) 
                              VALUES (?, ?, ?) 
                              ON DUPLICATE KEY UPDATE quantity = quantity + ?");
        $stmt->execute([$_SESSION['user_id'], $product_id, $quantity, $quantity]);
        
        // Get updated cart count
        $stmt = $pdo->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $result = $stmt->fetch();
        $cart_count = $result['total'] ?? 0;
        
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
