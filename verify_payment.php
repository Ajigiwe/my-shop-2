<?php
// Payment verification - Enhanced session handling
require_once 'includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Debug logging (keeping for server-side debugging, but removing from UI)
error_log('=== PAYMENT VERIFICATION DEBUG ===');
error_log('Session ID: ' . session_id());
error_log('Session status: ' . session_status());
error_log('Request URI: ' . $_SERVER['REQUEST_URI']);
error_log('GET params: ' . print_r($_GET, true));
error_log('Session data before verification: ' . print_r($_SESSION, true));

$reference = $_GET['reference'] ?? '';

if (empty($reference)) {
    error_log('No reference parameter provided');
    header('Location: index.php');
    exit();
}

error_log('Processing payment reference: ' . $reference);

require_once 'includes/paystack_config.php';

try {
    $verification_response = verifyPaystackPayment($reference);
    error_log('Paystack verification response: ' . print_r($verification_response, true));

    if (!$verification_response || !isset($verification_response['data'])) {
        error_log('Payment verification failed - no response data');
        $payment_status = 'error';
        $payment_message = 'Payment verification failed. Please contact support if amount was debited.';
    } else {
        $payment_data = $verification_response['data'];
        $status = strtolower($payment_data['status'] ?? '');
        $amount_kobo = (int)($payment_data['amount'] ?? 0);
        $currency = strtoupper($payment_data['currency'] ?? '');

        error_log("Payment verification - Status: $status, Amount: $amount_kobo, Currency: $currency");

        if ($status === 'success' && $currency === 'GHS' && $amount_kobo > 0) {
            try {
                $pdo->beginTransaction();
                error_log('Starting database transaction for successful payment');

                // Update transaction status
                $stmt = $pdo->prepare("UPDATE transactions SET status = 'completed' WHERE reference = ?");
                $stmt->execute([$reference]);
                error_log('Transaction updated to completed');

                // Update order status
                $stmt = $pdo->prepare("UPDATE orders SET payment_status = 'completed', status = 'paid' WHERE payment_reference = ?");
                $stmt->execute([$reference]);
                error_log('Order updated to paid');

                // Get order ID for redirect
                $stmt = $pdo->prepare("SELECT order_id FROM orders WHERE payment_reference = ?");
                $stmt->execute([$reference]);
                $order = $stmt->fetch();

                if ($order && isset($_SESSION['user_id'])) {
                    error_log('Clearing cart for user: ' . $_SESSION['user_id']);
                    $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    error_log('Cart cleared');
                }

                $pdo->commit();
                error_log('Database transaction committed successfully');

                // Clear session data
                unset($_SESSION['pending_order']);
                unset($_SESSION['payment_reference']);

                $payment_status = 'success';
                $payment_message = 'Your payment has been processed successfully. Thank you for your order!';
                $order_id = $order['order_id'] ?? null;
                error_log('Payment processed successfully, order_id: ' . $order_id);

            } catch(PDOException $e) {
                $pdo->rollBack();
                error_log("Database error during payment processing: " . $e->getMessage());
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
    error_log('Exception during payment verification: ' . $e->getMessage());
    $payment_status = 'error';
    $payment_message = 'Payment verification encountered an error. Please contact support.';
}

// Show result page
include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-lg">
                <div class="card-body text-center p-5">
                    <?php if ($payment_status === 'success'): ?>
                        <div class="mb-4">
                            <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                            <h2 class="text-success">Payment Successful!</h2>
                            <p class="lead"><?php echo htmlspecialchars($payment_message); ?></p>
                        </div>

                        <?php if (isset($order_id) && $order_id > 0): ?>
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
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
