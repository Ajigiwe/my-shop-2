<?php
// Debug the payment initialization process
session_start();
require_once 'includes/db.php';
require_once 'includes/paystack_config.php';

echo "<h1>🔍 Payment Initialization Debug</h1>\n";

echo "<h2>Session Check:</h2>\n";
echo "<pre>";
echo "User ID: " . ($_SESSION['user_id'] ?? 'NOT SET') . "\n";
echo "User Email: " . ($_SESSION['user_email'] ?? 'NOT SET') . "\n";
echo "Pending Order: " . (isset($_SESSION['pending_order']) ? 'EXISTS' : 'MISSING') . "\n";
if (isset($_SESSION['pending_order'])) {
    echo "Order Data: " . print_r($_SESSION['pending_order'], true) . "\n";
}
echo "</pre>";

// Check database
try {
    $stmt = $pdo->query('SELECT 1');
    echo "<h2>✅ Database: OK</h2>\n";
} catch(Exception $e) {
    echo "<h2>❌ Database: FAILED - " . $e->getMessage() . "</h2>\n";
    exit();
}

// Check Paystack config
echo "<h2>Paystack Config:</h2>\n";
echo "<pre>";
echo "PAYSTACK_SECRET_KEY: " . (defined('PAYSTACK_SECRET_KEY') ? substr(PAYSTACK_SECRET_KEY, 0, 10) . '...' : 'NOT SET') . "\n";
echo "PAYMENT_CALLBACK_URL: " . (defined('PAYMENT_CALLBACK_URL') ? PAYMENT_CALLBACK_URL : 'NOT SET') . "\n";
echo "</pre>";

// Test the exact same logic as paystack_payment.php
if (isset($_SESSION['pending_order'])) {
    $order_data = $_SESSION['pending_order'];

    // Get user email
    try {
        $stmt = $pdo->prepare("SELECT email FROM users WHERE user_id = ?");
        $stmt->execute([$order_data['user_id']]);
        $user = $stmt->fetch();
        $user_email = $user['email'] ?? 'test@example.com';
    } catch(PDOException $e) {
        $user_email = 'test@example.com';
    }

    $payment_data = [
        'amount' => $order_data['total_amount'] * 100,
        'email' => $user_email,
        'reference' => 'DEBUG_' . time(),
        'callback_url' => 'http://localhost/My%20Shop%202/payment_success.php',
        'metadata' => [
            'order_id' => 123,
            'customer_name' => $_SESSION['user_name'] ?? 'Customer'
        ]
    ];

    echo "<h2>Payment Data:</h2>\n";
    echo "<pre>" . print_r($payment_data, true) . "</pre>\n";

    echo "<h2>API Response:</h2>\n";
    $response = initializePaystackPayment($payment_data);

    if ($response && isset($response['data']['authorization_url'])) {
        echo "<h3>✅ SUCCESS!</h3>\n";
        echo "<p>Would redirect to: <a href='" . $response['data']['authorization_url'] . "'>" . $response['data']['authorization_url'] . "</a></p>\n";
        echo "<p><a href='" . $response['data']['authorization_url'] . "' target='_blank'>Test the redirect</a></p>\n";
    } else {
        echo "<h3>❌ FAILED:</h3>\n";
        echo "<pre>" . print_r($response, true) . "</pre>\n";
        echo "<p>Would redirect back to checkout.php with error</p>\n";
    }
} else {
    echo "<h2>❌ No pending order in session</h2>\n";
    echo "<p><a href='checkout.php'>Go to Checkout</a></p>\n";
}

echo "<hr>";
echo "<h2>Actions:</h2>";
echo "<ul>";
echo "<li><a href='checkout.php'>Go to Checkout</a></li>";
echo "<li><a href='cart.php'>Check Cart</a></li>";
echo "</ul>";
?>
