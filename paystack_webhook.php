<?php
require_once 'includes/config.php';
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
    $amount = $event['data']['amount'] / 100; // Convert from kobo to currency
    
    try {
        $pdo = getDBConnection();
        
        // Start transaction
        $pdo->beginTransaction();
        
        // Find the transaction by reference
        $stmt = $pdo->prepare("SELECT * FROM transactions WHERE reference = ? FOR UPDATE");
        $stmt->execute([$reference]);
        $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$transaction) {
            throw new Exception("Transaction not found: " . $reference);
        }
        
        // Update transaction status
        $updateTrans = $pdo->prepare("
            UPDATE transactions 
            SET status = ?, 
                gateway_response = ?, 
                updated_at = NOW() 
            WHERE reference = ?
        ");
        $updateTrans->execute([
            $status,
            json_encode($event['data']),
            $reference
        ]);
        
        // Update order status
        if (in_array($status, ['success', 'successful'])) {
            $orderStatus = 'paid';
        } else if ($status === 'failed') {
            $orderStatus = 'failed';
        } else {
            $orderStatus = 'pending';
        }
        
        $updateOrder = $pdo->prepare("
            UPDATE orders 
            SET payment_status = ?, 
                payment_reference = ?, 
                updated_at = NOW() 
            WHERE order_id = ?
        ");
        $updateOrder->execute([
            $orderStatus,
            $reference,
            $transaction['order_id']
        ]);
        
        // Commit the transaction
        $pdo->commit();
        
        http_response_code(200);
        echo json_encode(['status' => true, 'message' => 'Webhook processed']);
        
    } catch (Exception $e) {
        // Rollback the transaction on error
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        // Log the error
        error_log("Paystack webhook error: " . $e->getMessage());
        
        http_response_code(500);
        echo json_encode([
            'status' => false, 
            'message' => 'Error processing webhook',
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
    
    $computedSignature = hash_hmac('sha512', $payload, getenv('PAYSTACK_SECRET_KEY'));
    
    return hash_equals($signature, $computedSignature);
}
