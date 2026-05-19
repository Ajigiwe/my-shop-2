<?php
/**
 * Paystack Webhook Handler
 * Receives payment status notifications from Paystack asynchronously.
 */

// Include database connection and Paystack configuration
require_once 'includes/db.php';
require_once 'vendor/autoload.php';
require_once 'includes/paystack_config.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    exit('Only POST requests are allowed.');
}

// Retrieve the request's body and signature
$input = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] ?? '';

// Debug logging
error_log("Paystack Webhook called.");

if (empty($signature)) {
    error_log("Paystack Webhook error: Missing signature header.");
    http_response_code(400);
    exit('Missing signature header.');
}

// Validate the signature using the secret key
if ($signature !== hash_hmac('sha512', $input, $paystack_secret_key)) {
    error_log("Paystack Webhook error: Signature verification failed.");
    http_response_code(401);
    exit('Invalid signature.');
}

// Signature is valid! Parse the request
$event = json_decode($input);

if (!$event || !isset($event->event)) {
    error_log("Paystack Webhook error: Invalid JSON payload.");
    http_response_code(400);
    exit('Invalid JSON payload.');
}

error_log("Paystack Webhook received valid event: " . $event->event);

// Handle charge.success event
if ($event->event === 'charge.success') {
    $data = $event->data;
    $reference = $data->reference;
    $status = $data->status; // 'success'
    
    error_log("Paystack Webhook processing success event for reference: " . $reference);
    
    try {
        // Find the order with this reference
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE payment_reference = ?");
        $stmt->execute([$reference]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($order) {
            // Check if already confirmed or completed
            if ($order['order_status'] !== 'confirmed' && $order['order_status'] !== 'completed') {
                // Update order status to confirmed and payment to completed
                $stmt = $pdo->prepare("UPDATE orders SET order_status = 'confirmed', payment_status = 'completed' WHERE order_id = ?");
                $stmt->execute([$order['order_id']]);
                error_log("Paystack Webhook: Order {$order['order_id']} successfully confirmed via webhook.");
                
                // Clear the user's cart
                $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
                $stmt->execute([$order['user_id']]);
                error_log("Paystack Webhook: Cart cleared for user {$order['user_id']}.");
            } else {
                error_log("Paystack Webhook: Order {$order['order_id']} is already {$order['order_status']}.");
            }
        } else {
            error_log("Paystack Webhook Warning: Order with reference {$reference} not found in database.");
        }
    } catch (Exception $e) {
        error_log("Paystack Webhook Database Error: " . $e->getMessage());
        http_response_code(500);
        exit('Database error.');
    }
}

// Always respond with 200 OK to Paystack
http_response_code(200);
echo json_encode(['status' => 'success', 'message' => 'Event processed successfully']);
?>
