<?php
// Debug version to see what's happening
ob_start();
session_start();

echo "<!-- DEBUG: paystack_payment.php loaded -->\n";
echo "<!-- DEBUG: Session ID: " . session_id() . " -->\n";

if (!isset($_SESSION['user_id'])) {
    echo "<!-- DEBUG: No user session -->\n";
    header('Location: login.php');
    exit();
}

if (!isset($_SESSION['pending_order'])) {
    echo "<!-- DEBUG: No pending order found -->\n";
    echo "<!-- DEBUG: Session keys: " . implode(', ', array_keys($_SESSION)) . " -->\n";
    header('Location: checkout.php');
    exit();
}

echo "<!-- DEBUG: Session data found -->\n";
echo "<!-- DEBUG: User ID: " . $_SESSION['user_id'] . " -->\n";
echo "<!-- DEBUG: Order Total: " . $_SESSION['pending_order']['total_amount'] . " -->\n";

$order_data = $_SESSION['pending_order'];
$user_id = $_SESSION['user_id'];

// Database operations
require_once 'includes/db.php';

try {
    echo "<!-- DEBUG: Starting database transaction -->\n";
    $pdo->beginTransaction();

    $payment_reference = 'ORD_' . time() . '_' . uniqid();
    echo "<!-- DEBUG: Payment reference: $payment_reference -->\n";

    $stmt = $pdo->prepare("INSERT INTO transactions (reference, user_id, amount, currency, status, payment_method) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $payment_reference,
        $user_id,
        $order_data['total_amount'],
        'GHS',
        'pending',
        'paystack'
    ]);
    echo "<!-- DEBUG: Transaction created -->\n";

    $pdo->commit();
    echo "<!-- DEBUG: Transaction committed -->\n";

} catch(PDOException $e) {
    echo "<!-- DEBUG: Database error: " . $e->getMessage() . " -->\n";
    $pdo->rollBack();
    header('Location: checkout.php?error=payment_failed');
    exit();
}

require_once 'includes/paystack_config.php';

$payment_data = [
    'amount' => (int)($order_data['total_amount'] * 100),
    'email' => 'test@example.com',
    'reference' => $payment_reference,
    'callback_url' => 'http://localhost/MyShop2/payment_success.php',
    'currency' => 'GHS'
];

echo "<!-- DEBUG: Payment data prepared -->\n";

$payment_response = initializePaystackPayment($payment_data);
echo "<!-- DEBUG: Paystack response received -->\n";

if ($payment_response && isset($payment_response['data']['authorization_url'])) {
    echo "<!-- DEBUG: Paystack success - redirecting -->\n";
    $_SESSION['payment_reference'] = $payment_reference;
    header('Location: ' . $payment_response['data']['authorization_url']);
    exit();
} else {
    echo "<!-- DEBUG: Paystack failed -->\n";
    header('Location: checkout.php?error=payment_init_failed');
    exit();
}

ob_end_flush();
?>
