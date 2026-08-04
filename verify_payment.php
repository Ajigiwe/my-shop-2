<?php
/**
 * Paystack Payment Verification
 * Handles payment verification callback from Paystack
 */

// Include database connection and Paystack configuration
require_once 'includes/db.php';
if (file_exists('vendor/autoload.php') && file_exists('vendor/composer/autoload_real.php')) {
    require_once 'vendor/autoload.php';
}
require_once 'includes/paystack_config.php';

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Get payment reference from URL
$reference = $_GET['reference'] ?? '';

// Debug logging
error_log("Payment verification called with reference: " . $reference);
error_log("GET parameters: " . print_r($_GET, true));

if (empty($reference)) {
    error_log("No payment reference provided, redirecting to cart");
    header('Location: cart.php?error=invalid_reference');
    exit();
}

try {
    // Verify payment with Paystack
    $verification_response = verifyPaystackPayment($reference);
    
    if ($verification_response->status && $verification_response->data->status === 'success') {
        $payment_data = $verification_response->data;
        
        // Get order by payment reference
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE payment_reference = ?");
        $stmt->execute([$reference]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        error_log("Order lookup result: " . print_r($order, true));
        
        if ($order) {
            // Update order status to paid
            $stmt = $pdo->prepare("UPDATE orders SET order_status = 'confirmed', payment_status = 'completed' WHERE order_id = ?");
            $stmt->execute([$order['order_id']]);
            error_log("Order status updated to confirmed for order: " . $order['order_id']);
            
            // Clear user's cart
            $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->execute([$order['user_id']]);
            error_log("Cart cleared for user: " . $order['user_id']);
            
            // Send order confirmation email now that payment succeeded
            if (!isset($_SESSION['email_sent_for_order']) || $_SESSION['email_sent_for_order'] != $order['order_id']) {
                try {
                    $stmt = $pdo->prepare("SELECT oi.*, p.name as product_name, oi.price as product_price
                                           FROM order_items oi
                                           LEFT JOIN products p ON oi.product_id = p.product_id
                                           WHERE oi.order_id = ?");
                    $stmt->execute([$order['order_id']]);
                    $email_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $email_details = [
                        'items' => $email_items,
                        'shipping_address' => $order['shipping_address'],
                        'payment_method' => 'paystack',
                        'order_date' => $order['order_date'],
                        'total' => $order['total_amount'],
                    ];
                    if (sendOrderConfirmationEmail($order['email'], $order['first_name'] ?? 'Customer', $order['order_number'] ?? $order['order_id'], $email_details)) {
                        $_SESSION['email_sent_for_order'] = $order['order_id'];
                    }
                } catch (Exception $e) {
                    error_log("Confirmation email failed on verify: " . $e->getMessage());
                }
            }
            
            // Redirect to success page
            $redirect_url = 'order_confirmation.php?order_id=' . $order['order_id'] . '&payment=success';
            error_log("Redirecting to: " . $redirect_url);
            header('Location: ' . $redirect_url);
            exit();
        } else {
            // Order not found
            header('Location: cart.php?error=order_not_found');
            exit();
        }
    } else {
        // Payment failed
        $order_id = null;
        
        // Try to get order by reference
        $stmt = $pdo->prepare("SELECT order_id FROM orders WHERE payment_reference = ?");
        $stmt->execute([$reference]);
        $order_result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($order_result) {
            $order_id = $order_result['order_id'];
            
            // Update order status to cancelled
            $stmt = $pdo->prepare("UPDATE orders SET order_status = 'cancelled', payment_status = 'failed' WHERE order_id = ?");
            $stmt->execute([$order_id]);
        }
        
        // Redirect to failure page
        $redirect_url = 'cart.php?error=payment_failed';
        if ($order_id) {
            $redirect_url .= '&order_id=' . $order_id;
        }
        header('Location: ' . $redirect_url);
        exit();
    }
} catch (Exception $e) {
    error_log('Payment verification error: ' . $e->getMessage());
    header('Location: cart.php?error=verification_failed');
    exit();
}
?>
