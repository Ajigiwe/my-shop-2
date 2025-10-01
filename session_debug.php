<?php
// Session debug test
session_start();

echo "<h1>🔍 Session Debug Test</h1>\n";

echo "<h2>Current Request:</h2>\n";
echo "<pre>";
echo "Session ID: " . session_id() . "\n";
echo "User Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'NOT SET') . "\n";
echo "Request Method: " . $_SERVER['REQUEST_METHOD'] . "\n";
echo "Request URI: " . $_SERVER['REQUEST_URI'] . "\n";
echo "</pre>";

echo "<h2>Session Data:</h2>\n";
echo "<pre>";
echo "User ID: " . ($_SESSION['user_id'] ?? 'NOT SET') . "\n";
echo "User Email: " . ($_SESSION['user_email'] ?? 'NOT SET') . "\n";
echo "User Name: " . ($_SESSION['user_name'] ?? 'NOT SET') . "\n";
echo "Pending Order: " . (isset($_SESSION['pending_order']) ? 'EXISTS' : 'MISSING') . "\n";
if (isset($_SESSION['pending_order'])) {
    echo "Order Total: " . $_SESSION['pending_order']['total_amount'] . "\n";
    echo "Cart Items: " . count($_SESSION['pending_order']['cart_items']) . "\n";
}
echo "</pre>";

if (isset($_POST['set_session'])) {
    $_SESSION['user_id'] = 3;
    $_SESSION['user_email'] = 'webtest@example.com';
    $_SESSION['user_name'] = 'Web Test User';
    $_SESSION['pending_order'] = [
        'user_id' => 3,
        'total_amount' => 250.00,
        'payment_method' => 'paystack',
        'shipping_address' => 'Test Address',
        'billing_address' => 'Test Address',
        'cart_items' => [['name' => 'Test Product', 'quantity' => 1, 'price' => 250.00]],
        'created_at' => date('Y-m-d H:i:s')
    ];

    echo "<h2>✅ Session Data Set!</h2>\n";
    echo "<p><a href='?'>Refresh to check persistence</a></p>\n";
    echo "<p><a href='paystack_payment.php'>Go to Paystack Payment</a></p>\n";
} else {
    echo "<h2>Set Session Data:</h2>\n";
    echo "<form method='POST' action=''>\n";
    echo "<input type='hidden' name='set_session' value='1'>\n";
    echo "<button type='submit'>Set Test Session Data</button>\n";
    echo "</form>\n";
}

echo "<h2>Test Links:</h2>\n";
echo "<ul>\n";
echo "<li><a href='checkout.php'>Go to Checkout</a></li>\n";
echo "<li><a href='cart.php'>Check Cart</a></li>\n";
echo "</ul>\n";
?>
