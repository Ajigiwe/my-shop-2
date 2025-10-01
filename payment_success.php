<?php
// Payment success callback - Robust version with debugging
require_once 'includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Debug: Log all requests to this file
error_log('Payment callback accessed at: ' . date('Y-m-d H:i:s'));
error_log('Request URI: ' . $_SERVER['REQUEST_URI']);
error_log('Request method: ' . $_SERVER['REQUEST_METHOD']);
error_log('Query string: ' . $_SERVER['QUERY_STRING']);
error_log('All GET params: ' . print_r($_GET, true));

$reference = $_GET['reference'] ?? '';

if (empty($reference)) {
    error_log('No reference parameter provided');
    echo "<h1>Payment Callback</h1>\n";
    echo "<p>No payment reference provided.</p>\n";
    echo "<p><a href='index.php'>Return to Home</a></p>\n";
    exit();
}

error_log('Processing payment reference: ' . $reference);

require_once 'includes/paystack_config.php';

try {
    $verification_response = verifyPaystackPayment($reference);
    error_log('Verification response: ' . print_r($verification_response, true));

    if (!$verification_response || !isset($verification_response['data'])) {
        error_log('Payment verification failed - no response data');
        $payment_status = 'error';
        $payment_message = 'Payment verification failed. Please contact support if amount was debited.';
    } else {
        $payment_data = $verification_response['data'];
        $status = strtolower($payment_data['status'] ?? '');
        $amount_kobo = (int)($payment_data['amount'] ?? 0);
        $currency = strtoupper($payment_data['currency'] ?? '');

        error_log("Payment status: $status, Amount: $amount_kobo, Currency: $currency");

        if ($status === 'success' && $currency === 'GHS' && $amount_kobo > 0) {
            try {
                $pdo->beginTransaction();
                error_log('Starting database transaction for successful payment');

                // Update transaction status
                $stmt = $pdo->prepare("UPDATE transactions SET status = 'completed' WHERE reference = ?");
                $stmt->execute([$reference]);
                error_log('Updated transaction status');

                // Update order status
                $stmt = $pdo->prepare("UPDATE orders SET payment_status = 'completed', status = 'paid' WHERE payment_reference = ?");
                $stmt->execute([$reference]);
                error_log('Updated order status');

                // Get order ID for cart clearing
                $stmt = $pdo->prepare("SELECT order_id FROM orders WHERE payment_reference = ?");
                $stmt->execute([$reference]);
                $order = $stmt->fetch();

                if ($order && isset($_SESSION['user_id'])) {
                    error_log('Clearing cart for user: ' . $_SESSION['user_id']);
                    $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                }

                $pdo->commit();
                error_log('Database transaction committed successfully');

                // Clear session data
                unset($_SESSION['pending_order']);
                unset($_SESSION['payment_reference']);

                $payment_status = 'success';
                $payment_message = 'Your payment has been processed successfully. Thank you for your order!';
                $order_id = $order['order_id'] ?? null;

            } catch(PDOException $e) {
                $pdo->rollBack();
                error_log("Error updating payment success: " . $e->getMessage());
                $payment_status = 'error';
                $payment_message = 'Payment successful but order update failed. Please contact support.';
            }
        } else {
            error_log("Payment not successful - Status: $status, Currency: $currency, Amount: $amount_kobo");
            $payment_status = 'failed';
            $payment_message = 'Payment was not completed successfully. Please try again.';
        }
    }
} catch(Exception $e) {
    error_log('Exception in payment verification: ' . $e->getMessage());
    $payment_status = 'error';
    $payment_message = 'Payment verification encountered an error. Please contact support.';
}

// Show result page
include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body text-center p-5">
                    <?php if ($payment_status === 'success'): ?>
                        <div class="mb-4">
                            <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                            <h2 class="text-success">Payment Successful!</h2>
                            <p class="lead"><?php echo htmlspecialchars($payment_message); ?></p>
                        </div>

                        <?php if (isset($order_id)): ?>
                            <div class="alert alert-success">
                                <strong>Order ID:</strong> #<?php echo str_pad($order_id, 6, '0', STR_PAD_LEFT); ?>
                            </div>
                        <?php endif; ?>

                        <div class="mt-4">
                            <a href="order_details.php?order_id=<?php echo $order_id; ?>" class="btn btn-primary btn-lg me-2">
                                <i class="fas fa-eye me-2"></i>View Order Details
                            </a>
                            <a href="shop.php" class="btn btn-outline-primary">
                                <i class="fas fa-shopping-bag me-2"></i>Continue Shopping
                            </a>
                        </div>

                    <?php elseif ($payment_status === 'failed'): ?>
                        <div class="mb-4">
                            <i class="fas fa-times-circle fa-4x text-danger mb-3"></i>
                            <h2 class="text-danger">Payment Failed</h2>
                            <p class="lead"><?php echo htmlspecialchars($payment_message); ?></p>
                        </div>

                        <div class="mt-4">
                            <a href="checkout.php" class="btn btn-primary btn-lg me-2">
                                <i class="fas fa-credit-card me-2"></i>Try Again
                            </a>
                            <a href="cart.php" class="btn btn-outline-secondary">
                                <i class="fas fa-shopping-cart me-2"></i>Back to Cart
                            </a>
                        </div>

                    <?php else: ?>
                        <div class="mb-4">
                            <i class="fas fa-exclamation-triangle fa-4x text-warning mb-3"></i>
                            <h2 class="text-warning">Payment Verification Error</h2>
                            <p class="lead"><?php echo htmlspecialchars($payment_message); ?></p>
                        </div>

                        <div class="mt-4">
                            <a href="checkout.php" class="btn btn-primary btn-lg me-2">
                                <i class="fas fa-home me-2"></i>Back to Checkout
                            </a>
                            <a href="user/orders.php" class="btn btn-outline-secondary">
                                <i class="fas fa-list me-2"></i>View Orders
                            </a>
                        </div>
                    <?php endif; ?>

                    <!-- Debug information -->
                    <div class="mt-5">
                        <button class="btn btn-outline-info btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#debugInfo">
                            <i class="fas fa-bug me-2"></i>Debug Info
                        </button>

                        <div class="collapse mt-3" id="debugInfo">
                            <div class="card card-body bg-light">
                                <h6>Payment Reference:</h6>
                                <p class="small mb-2"><?php echo htmlspecialchars($reference); ?></p>
                                <h6>Verification Response:</h6>
                                <pre class="small"><?php echo htmlspecialchars(print_r($verification_response, true)); ?></pre>
                                <h6>Session ID:</h6>
                                <p class="small"><?php echo session_id(); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
