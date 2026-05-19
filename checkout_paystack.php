<?php
/**
 * Paystack Checkout Page
 * Handles Paystack payment initialization and processing
 */

// Include database connection and Paystack configuration
require_once 'includes/db.php';
require_once 'vendor/autoload.php';
require_once 'includes/paystack_config.php';

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$order_id = $_GET['order_id'] ?? null;

// Validate order ID
if (!$order_id) {
    header('Location: cart.php');
    exit();
}

// Get order details
try {
    // Check if this is a session-based order (fallback)
    if (isset($_SESSION['pending_order']) && $_SESSION['pending_order']['order_id'] === $order_id) {
        // Use session data for Paystack (fallback mode)
        $pending_order = $_SESSION['pending_order'];
        $order = [
            'order_id' => $order_id,
            'total_amount' => $pending_order['amount'],
            'email' => $pending_order['email'],
            'first_name' => $_SESSION['user_name'] ?? 'Customer',
            'last_name' => '',
            'phone' => $pending_order['phone']
        ];
    } else {
        // Normal database query
        $stmt = $pdo->prepare("
            SELECT o.*, u.name, u.email, u.phone
            FROM orders o
            JOIN users u ON o.user_id = u.user_id
            WHERE o.order_id = ? AND o.user_id = ?
        ");
        $stmt->execute([$order_id, $user_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($order) {
            // Split name if needed, or just use as is
            $order['first_name'] = $order['name'];
            $order['last_name'] = '';
        } else {
            header('Location: cart.php');
            exit();
        }
    }
    
    // Get order items
    if (isset($_SESSION['pending_order']) && $_SESSION['pending_order']['order_id'] === $order_id) {
        // Use session order items (fallback mode)
        $order_items = [];
        foreach ($pending_order['cart_items'] as $item) {
            $order_items[] = [
                'name' => $item['name'],
                'image' => $item['image'] ?? 'default.jpg',
                'quantity' => $item['quantity'],
                'price' => $item['price']
            ];
        }
    } else {
        // Normal database query
        $stmt = $pdo->prepare("
            SELECT oi.*, p.name, p.image
            FROM order_items oi
            JOIN products p ON oi.product_id = p.product_id
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$order_id]);
        $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
} catch (PDOException $e) {
    error_log("Error fetching order details: " . $e->getMessage());
    header('Location: cart.php');
    exit();
}

// Initialize Paystack payment
$payment_data = [
    'amount' => formatAmountForPaystack($order['total_amount']),
    'email' => $order['email'],
    'reference' => generateTransactionReference(),
    'callback_url' => SITE_URL . 'verify_payment.php',
    'metadata' => [
        'order_id' => $order_id,
        'user_id' => $user_id,
        'customer_name' => $order['first_name'] . ' ' . $order['last_name']
    ]
];

// Debug: Log payment data
error_log("Paystack payment data: " . json_encode($payment_data));

try {
    $paystack_response = initializePaystackPayment($payment_data);
    
    // Debug: Log response
    error_log("Paystack response: " . json_encode($paystack_response));
    
    if ($paystack_response->status) {
        $authorization_url = $paystack_response->data->authorization_url;
        $reference = $paystack_response->data->reference;
        
        // Update database or session with Paystack reference
        if (isset($_SESSION['pending_order'])) {
            // Fallback mode - update session
            $_SESSION['pending_order']['payment_reference'] = $reference;
        } else {
            // Normal mode - update database
            try {
                $stmt = $pdo->prepare("UPDATE orders SET payment_reference = ? WHERE order_id = ?");
                $stmt->execute([$reference, $order_id]);
            } catch (Exception $e) {
                error_log('Failed to update payment reference: ' . $e->getMessage());
            }
        }
        
        // Debug: Log redirect URL
        error_log("Redirecting to: " . $authorization_url);
        
        // Redirect to Paystack payment page
        header('Location: ' . $authorization_url);
        exit();
    } else {
        $error_message = 'Payment initialization failed: ' . ($paystack_response->message ?? 'Unknown error');
        error_log('Paystack initialization failed: ' . $error_message);
    }
} catch (Exception $e) {
    error_log('Paystack payment error: ' . $e->getMessage());
    $error_message = 'Payment initialization failed: ' . $e->getMessage();
}

// Set page title
$page_title = 'Paystack Payment';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">
                            <i class="fas fa-credit-card me-2"></i>
                            Paystack Payment
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error_message)): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?php echo htmlspecialchars($error_message); ?>
                            </div>
                            <div class="alert alert-info">
                                <h6>Debug Information:</h6>
                                <p><strong>Order ID:</strong> <?php echo htmlspecialchars($order_id); ?></p>
                                <p><strong>Amount:</strong> <?php echo htmlspecialchars($order['total_amount']); ?></p>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($order['email']); ?></p>
                                <p><strong>Payment Data:</strong> <pre><?php echo json_encode($payment_data, JSON_PRETTY_PRINT); ?></pre></p>
                            </div>
                            <div class="text-center">
                                <a href="checkout.php" class="btn btn-primary">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Checkout
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="text-center">
                                <div class="spinner-border text-[#1A1A1A] mb-3" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <h5>Redirecting to Paystack...</h5>
                                <p class="text-muted">Please wait while we redirect you to the payment page.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
