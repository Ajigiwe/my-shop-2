<?php
/**
 * Paystack Payment Verification
 * - Handles Paystack payment verification callback
 * - Updates order status based on payment status
 */

// Include database connection and config
require_once 'includes/db.php';
require_once 'includes/paystack_config.php';
require_once 'vendor/autoload.php'; // For Composer autoloading

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Get the order ID from the URL
$order_id = $_GET['order_id'] ?? 0;

if (!$order_id) {
    die('Invalid order ID');
}

try {
    // Get order details
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        die('Order not found');
    }
    
    // Initialize Paystack
    $paystack = new Yabacon\Paystack(PAYSTACK_SECRET_KEY);
    
    // Get the payment reference from the order
    $reference = $order['payment_reference'] ?? '';
    
    if (empty($reference)) {
        throw new Exception('No payment reference found for this order');
    }
    
    // Verify the payment
    $response = $paystack->transaction->verify([
        'reference' => $reference
    ]);
    
    // Check if payment was successful
    if ($response->status && $response->data->status === 'success') {
        // Payment was successful
        $amount_paid = $response->data->amount / 100; // Convert from kobo to currency
        
        // Update order status
        $stmt = $pdo->prepare("
            UPDATE orders 
            SET status = 'paid', 
                payment_status = 'completed',
                payment_reference = ?,
                payment_amount = ?,
                payment_date = NOW()
            WHERE order_id = ?
        ");
        
        $stmt->execute([
            $reference,
            $amount_paid,
            $order_id
        ]);
        
        // Get order details for email
        $stmt = $pdo->prepare("
            SELECT o.*, u.name, u.email 
            FROM orders o 
            JOIN users u ON u.user_id = o.user_id 
            WHERE o.order_id = ?
        ");
        $stmt->execute([$order_id]);
        $order_details = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Send order confirmation email
        if ($order_details && !empty($order_details['email'])) {
            // Get order items for the invoice
            $stmt = $pdo->prepare("
                SELECT oi.*, p.name as product_name, p.price as product_price 
                FROM order_items oi 
                JOIN products p ON oi.product_id = p.product_id 
                WHERE oi.order_id = ?
            ");
            $stmt->execute([$order_id]);
            $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Prepare order details for email
            $email_data = [
                'order_id' => $order_id,
                'items' => $order_items,
                'subtotal' => $order_details['total_amount'],
                'shipping' => 0, // Add shipping if applicable
                'total' => $order_details['total_amount'],
                'order_date' => $order_details['order_date'],
                'status' => 'paid',
                'payment_method' => 'Paystack',
                'payment_reference' => $reference
            ];
            
            // Include email config and send confirmation
            require_once 'includes/email_config.php';
            
            // Send order confirmation email
            sendOrderConfirmationEmail(
                $order_details['email'],
                $order_details['name'],
                $order_id,
                $email_data
            );
            
            // Send invoice email
            sendInvoiceEmail(
                $order_details['email'],
                $order_details['name'],
                $order_id,
                $email_data
            );
        }
        
        // Redirect to success page
        header('Location: order_confirmation.php?order_id=' . $order_id . '&status=success');
        exit();
        
    } else {
        // Payment failed or is pending
        throw new Exception('Payment verification failed: ' . ($response->message ?? 'Unknown error'));
    }
    
} catch (Exception $e) {
    // Log the error
    error_log('Payment verification error: ' . $e->getMessage());
    
    // Update order status to failed
    if (isset($pdo)) {
        $stmt = $pdo->prepare("
            UPDATE orders 
            SET status = 'payment_failed',
                payment_status = 'failed',
                notes = CONCAT(IFNULL(notes, ''), ' Payment failed: ', ?)
            WHERE order_id = ?
        ");
        $stmt->execute([$e->getMessage(), $order_id]);
    }
    
    // Redirect to failure page
    header('Location: order_confirmation.php?order_id=' . $order_id . '&status=failed&error=' . urlencode($e->getMessage()));
    exit();
}
