<?php
// Paystack payment - Phone stored in shipping address
ob_start();
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if (!isset($_SESSION['pending_order'])) {
    header('Location: checkout.php');
    exit();
}

$order_data = $_SESSION['pending_order'];
$user_id = $_SESSION['user_id'];

require_once 'includes/db.php';

try {
    $pdo->beginTransaction();

    $payment_reference = 'ORD_' . time() . '_' . uniqid();

    // Store phone number in shipping address with clear formatting
    $shipping_address_with_phone = $order_data['shipping_address'] . "\n\nPhone: " . $order_data['phone_number'];

    // Create order first
    $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_amount, payment_method, payment_reference, payment_status, shipping_address, billing_address, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $user_id,
        $order_data['total_amount'],
        'paystack',
        $payment_reference,
        'pending',
        $shipping_address_with_phone,
        $order_data['billing_address'],
        'pending'
    ]);
    $order_id = $pdo->lastInsertId();

    // Create transaction with order_id
    $stmt = $pdo->prepare("INSERT INTO transactions (reference, order_id, amount, status, payment_method) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $payment_reference,
        $order_id,
        $order_data['total_amount'],
        'pending',
        'paystack'
    ]);

    // Add order items
    $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
    foreach ($order_data['cart_items'] as $item) {
        $stmt->execute([$order_id, $item['product_id'], $item['quantity'], $item['price']]);
    }

    $pdo->commit();

} catch(PDOException $e) {
    echo "<h1>Database Error</h1>\n";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>\n";
    $pdo->rollBack();
    exit();
}

// Get user email
try {
    $stmt = $pdo->prepare("SELECT email FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    $user_email = $user['email'] ?? 'customer@example.com';
} catch(PDOException $e) {
    $user_email = 'customer@example.com';
}

require_once 'includes/paystack_config.php';

$payment_data = [
    'amount' => (int)($order_data['total_amount'] * 100),
    'email' => $user_email,
    'reference' => $payment_reference,
    'callback_url' => 'http://127.0.0.1/My%20Shop%202/verify_payment.php',
    'currency' => 'GHS',
    'metadata' => [
        'order_id' => $order_id,
        'customer_name' => $_SESSION['user_name'] ?? 'Customer',
        'phone_number' => $order_data['phone_number'] ?? ''
    ]
];

$payment_response = initializePaystackPayment($payment_data);

if ($payment_response && isset($payment_response['data']['authorization_url'])) {
    $_SESSION['payment_reference'] = $payment_reference;
    header('Location: ' . $payment_response['data']['authorization_url']);
    exit();
} else {
    echo "<h1>Paystack API Error</h1>\n";
    echo "<pre>" . print_r($payment_response, true) . "</pre>\n";
    exit();
}

ob_end_flush();
?>
