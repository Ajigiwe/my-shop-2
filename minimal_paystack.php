<?php
// Minimal Paystack test
session_start();

echo "<h1>🔍 Minimal Paystack Test</h1>\n";

if (!isset($_SESSION['simple_test'])) {
    echo "<p>❌ No session data found</p>\n";
    echo "<p><a href='simple_session.php'>Set Session Data First</a></p>\n";
    exit();
}

echo "<p>✅ Session data found: " . $_SESSION['simple_test'] . "</p>\n";

// Test database connection
require_once 'includes/db.php';

try {
    $stmt = $pdo->prepare('SELECT 1 as test');
    $stmt->execute();
    $result = $stmt->fetch();
    echo "<p>✅ Database connection: " . ($result['test'] == 1 ? 'WORKING' : 'FAILED') . "</p>\n";
} catch(Exception $e) {
    echo "<p>❌ Database error: " . $e->getMessage() . "</p>\n";
    exit();
}

// Test Paystack API
require_once 'includes/paystack_config.php';

$payment_data = [
    'amount' => 10000, // 100 GHS
    'email' => 'test@example.com',
    'reference' => 'TEST_' . time(),
    'callback_url' => 'http://localhost/MyShop2/payment_success.php',
    'currency' => 'GHS'
];

try {
    $response = initializePaystackPayment($payment_data);

    if ($response && isset($response['data']['authorization_url'])) {
        echo "<p>✅ Paystack API: WORKING</p>\n";
        echo "<p>Auth URL: " . htmlspecialchars($response['data']['authorization_url']) . "</p>\n";

        echo "<h2>Testing redirect...</h2>\n";
        header('Location: ' . $response['data']['authorization_url']);
        exit();
    } else {
        echo "<p>❌ Paystack API: FAILED</p>\n";
        echo "<pre>" . print_r($response, true) . "</pre>\n";
    }
} catch(Exception $e) {
    echo "<p>❌ Paystack error: " . $e->getMessage() . "</p>\n";
}
?>
