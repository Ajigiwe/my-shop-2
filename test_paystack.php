<?php
// Clean test_paystack.php - Following minimal_paystack.php pattern
session_start();

// Set test session data (same as minimal_paystack.php)
$_SESSION['user_id'] = 3;
$_SESSION['user_email'] = 'test@example.com';
$_SESSION['user_name'] = 'Test User';

require_once 'includes/db.php';

echo "<h1>🧪 Clean Paystack Test</h1>\n";

try {
    // Get cart data
    $stmt = $pdo->prepare('SELECT c.*, p.name, p.price, p.image FROM cart c JOIN products p ON c.product_id = p.product_id WHERE c.user_id = ?');
    $stmt->execute([3]);
    $cart_items = $stmt->fetchAll();

    if (empty($cart_items)) {
        echo "<p>❌ No items in cart. Please add items to cart first.</p>\n";
        echo "<p><a href='shop.php'>Go to Shop</a></p>\n";
        exit();
    }

    $total = 0;
    foreach ($cart_items as $item) {
        $total += $item['price'] * $item['quantity'];
    }

    echo "<h2>✅ Cart Summary</h2>\n";
    echo "<p>Total: " . $total . " GHS</p>\n";
    echo "<p>Items: " . count($cart_items) . "</p>\n";

    // Set session data for payment (EXACTLY like minimal_paystack.php)
    $_SESSION['pending_order'] = [
        'user_id' => 3,
        'total_amount' => $total,
        'payment_method' => 'paystack',
        'shipping_address' => 'Test Address',
        'billing_address' => 'Test Address',
        'cart_items' => $cart_items,
        'created_at' => date('Y-m-d H:i:s')
    ];

    echo "<h2>✅ Session Data Set</h2>\n";
    echo "<p>Session ID: " . session_id() . "</p>\n";
    echo "<p>Order Total: " . $_SESSION['pending_order']['total_amount'] . "</p>\n";

    echo "<div style='margin: 20px 0;'>\n";
    echo "<a href='paystack_payment.php' style='background: #00a651; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-weight: bold;'>💳 Proceed to Paystack Payment</a>\n";
    echo "</div>\n";

} catch(Exception $e) {
    echo "<h2>❌ Error</h2>\n";
    echo "<p>" . $e->getMessage() . "</p>\n";
}
?>
