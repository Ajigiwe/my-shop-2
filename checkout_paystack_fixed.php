<?php
/**
 * Fixed Paystack Checkout Page
 * Handles Paystack payment with local success handling
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

// Handle payment success (when user returns from Paystack)
if (isset($_GET['payment_success'])) {
    try {
        // Update order status to confirmed
        $stmt = $pdo->prepare("UPDATE orders SET status = 'confirmed', payment_status = 'completed' WHERE order_id = ?");
        $stmt->execute([$order_id]);
        
        // Clear user's cart
        $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt->execute([$user_id]);
        
        // Redirect to success page
        header('Location: order_confirmation.php?order_id=' . $order_id . '&payment=success');
        exit();
        
    } catch (Exception $e) {
        error_log('Error handling payment success: ' . $e->getMessage());
        header('Location: cart.php?error=payment_processing_failed');
        exit();
    }
}

// Get order details
try {
    $stmt = $pdo->prepare("
        SELECT o.*, u.first_name, u.last_name, u.email, u.phone
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.user_id
        WHERE o.order_id = ?
    ");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        header('Location: cart.php');
        exit();
    }
    
    // Get order items
    $stmt = $pdo->prepare("
        SELECT oi.*, p.name, p.price, p.image
        FROM order_items oi
        JOIN products p ON oi.product_id = p.product_id
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$order_id]);
    $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
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
    'callback_url' => 'http://localhost/My%20Shop2/checkout_paystack_fixed.php?order_id=' . $order_id . '&payment_success=1',
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
        
        // Update database with Paystack reference
        try {
            $stmt = $pdo->prepare("UPDATE orders SET payment_reference = ? WHERE order_id = ?");
            $stmt->execute([$reference, $order_id]);
        } catch (Exception $e) {
            error_log('Failed to update payment reference: ' . $e->getMessage());
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
    <script src="https://js.paystack.co/v1/inline.js"></script>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Paystack Payment</h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error_message)): ?>
                            <div class="alert alert-danger">
                                <h5>Payment Error</h5>
                                <p><?php echo htmlspecialchars($error_message); ?></p>
                                <a href="cart.php" class="btn btn-secondary">Return to Cart</a>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <h5>Redirecting to Paystack...</h5>
                                <p>You will be redirected to Paystack to complete your payment.</p>
                                <p>After payment, you will be redirected back to the order confirmation page.</p>
                            </div>
                            
                            <div class="text-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="mt-3">Redirecting to payment page...</p>
                            </div>
                            
                            <script>
                                // Auto-redirect after 3 seconds if not already redirected
                                setTimeout(function() {
                                    window.location.href = '<?php echo $authorization_url ?? 'cart.php'; ?>';
                                }, 3000);
                            </script>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
