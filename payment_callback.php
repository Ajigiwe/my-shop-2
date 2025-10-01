<?php
/**
 * Paystack Payment Callback/Webhook
 * - Secure webhook with signature verification
 * - Updates order status after successful payment
 * - Creates transaction records for tracking
 */

// Include database connection
require_once 'includes/db.php';
require_once 'includes/paystack_config.php';

// Set content type to JSON
header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get the input as JSON
$input = file_get_contents('php://input');
$event = json_decode($input, true);

// Verify the event is from Paystack
if (!verifyPaystackWebhook($input)) {
    http_response_code(401);
    echo json_encode(['status' => false, 'message' => 'Invalid signature']);
    exit;
}

// Process the event
if ($event['event'] === 'charge.success') {
    $reference = $event['data']['reference'];
    $status = strtolower($event['data']['status']);
    $amount = $event['data']['amount'] / 100; // Convert from kobo to naira
    $currency = $event['data']['currency'] ?? 'NGN';

    try {
        $pdo->beginTransaction();

        // Check if transaction already exists
        $stmt = $pdo->prepare("SELECT transaction_id FROM transactions WHERE reference = ?");
        $stmt->execute([$reference]);
        $existing_transaction = $stmt->fetch();

        if ($existing_transaction) {
            // Update existing transaction
            $stmt = $pdo->prepare("
                UPDATE transactions
                SET status = ?, gateway_response = ?, updated_at = NOW()
                WHERE reference = ?
            ");
            $stmt->execute([$status, json_encode($event['data']), $reference]);
        } else {
            // Create new transaction record
            $stmt = $pdo->prepare("
                INSERT INTO transactions (reference, amount, currency, status, gateway_response, payment_method)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$reference, $amount, $currency, $status, json_encode($event['data']), 'paystack']);
        }

        // Find associated order and update status
        $stmt = $pdo->prepare("
            SELECT order_id, user_id FROM orders
            WHERE payment_reference = ? OR order_id IN (
                SELECT order_id FROM transactions WHERE reference = ?
            )
        ");
        $stmt->execute([$reference, $reference]);
        $order = $stmt->fetch();

        if ($order) {
            $order_id = $order['order_id'];
            $user_id = $order['user_id'];

            // Update transaction with order_id
            $stmt = $pdo->prepare("UPDATE transactions SET order_id = ?, user_id = ? WHERE reference = ?");
            $stmt->execute([$order_id, $user_id, $reference]);

            // Update order status based on payment status
            $order_status = 'pending';
            if (in_array($status, ['success', 'successful'])) {
                $order_status = 'paid';
            } elseif ($status === 'failed') {
                $order_status = 'payment_failed';
            }

            $stmt = $pdo->prepare("
                UPDATE orders
                SET status = ?, payment_status = ?, updated_at = NOW()
                WHERE order_id = ?
            ");
            $stmt->execute([$order_status, $order_status, $order_id]);

            // If payment successful, decrement stock
            if ($order_status === 'paid') {
                $stmt = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
                $stmt->execute([$order_id]);
                $order_items = $stmt->fetchAll();

                foreach ($order_items as $item) {
                    $stmt = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE product_id = ?");
                    $stmt->execute([$item['quantity'], $item['product_id']]);
                }

                // Clear user's cart
                $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
                $stmt->execute([$user_id]);
            }
        }

        $pdo->commit();

        http_response_code(200);
        echo json_encode(['status' => true, 'message' => 'Payment processed successfully']);

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log("Paystack webhook error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'status' => false,
            'message' => 'Error processing payment',
            'error' => $e->getMessage()
        ]);
    }

} else {
    // Log other events for debugging
    error_log("Unhandled Paystack event: " . $event['event']);
    http_response_code(200);
    echo json_encode(['status' => true, 'message' => 'Event received but not processed']);
}

/**
 * Verify Paystack webhook signature
 */
function verifyPaystackWebhook($payload) {
    $signature = $_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] ?? '';

    if (!$signature) {
        error_log('No Paystack signature found in headers');
        return false;
    }

    $computed_signature = hash_hmac('sha512', $payload, PAYSTACK_SECRET_KEY);

    return hash_equals($signature, $computed_signature);
}
?>
