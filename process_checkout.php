<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Start output buffering to catch any unexpected output
ob_start();

session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';
require 'vendor/autoload.php'; // Load Composer's autoloader

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'order_id' => null,
    'redirect' => null
];

try {
    // Check if this is an AJAX request
    $is_ajax = isset($_POST['is_ajax']) && $_POST['is_ajax'] === '1';
    
    // Test database connection first
    if (!isset($pdo) || !$pdo) {
        error_log("Database connection failed, using fallback mode");
        
        // In fallback mode, just redirect to Paystack without processing order
        $payment_method = $_POST['payment_method'] ?? 'paystack';
        if ($payment_method === 'paystack') {
            // Redirect directly to Paystack checkout page
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Redirecting to Paystack...',
                'redirect' => 'checkout_paystack.php?order_id=fallback_' . time()
            ]);
            exit();
        } else {
            // For COD, show error since we can't process without database
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Database connection failed. Please try again later.'
            ]);
            exit();
        }
    }
    
    // Get form data
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $shipping_address = sanitizeInput($_POST['shipping_address'] ?? '');
    $billing_address = sanitizeInput($_POST['billing_address'] ?? $shipping_address);
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $order_notes = sanitizeInput($_POST['order_notes'] ?? '');
    $payment_method = $_POST['payment_method'] ?? 'paystack';
    
    // Debug: Log the received payment method
    error_log("Received payment method: " . $payment_method);
    error_log("Is AJAX request: " . ($is_ajax ? 'Yes' : 'No'));
    
    // Get user ID from session
    $user_id = $_SESSION['user_id'] ?? 0;
    
    // Get cart items from database
    $cart_items = [];
    if ($user_id) {
        $stmt = $pdo->prepare("
            SELECT c.*, p.name, p.price, p.image, p.stock_quantity, p.product_id 
            FROM cart c 
            JOIN products p ON c.product_id = p.product_id 
            WHERE c.user_id = ?
        ");
        $stmt->execute([$user_id]);
        $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Validate form data
    $errors = [];
    if (empty($phone)) {
        $errors[] = 'Phone number is required';
    }
    if (empty($shipping_address)) {
        $errors[] = 'Shipping address is required';
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email address is required';
    }
    if (empty($cart_items)) {
        $errors[] = 'Your cart is empty';
    }
    
    // If there are validation errors, return them
    if (!empty($errors)) {
        $response['message'] = implode('<br>', $errors);
        echo json_encode($response);
        exit();
    }
    
    // Calculate order total
    $subtotal = 0;
    foreach ($cart_items as $item) {
        $subtotal += $item['price'] * $item['quantity'];
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Generate order number
        $order_number = 'ORD-' . strtoupper(uniqid());
        
        // Insert order with all fields
        $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_amount, payment_method, shipping_address, billing_address, order_notes, email, phone) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $user_id,
            $subtotal,
            $payment_method === 'paystack' ? 'paystack' : 'cash_on_delivery',
            $shipping_address,
            $billing_address ?: $shipping_address,
            $order_notes,
            $email,
            $phone
        ]);
        
        // Get the order ID
        $order_id = $pdo->lastInsertId();
        // Update the order with the order number
        $order_number = 'ORD-' . str_pad($order_id, 6, '0', STR_PAD_LEFT);
        $pdo->prepare("UPDATE orders SET order_number = ? WHERE order_id = ?")->execute([$order_number, $order_id]);
        
        // Insert order items
        $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, price, quantity) 
                              VALUES (?, ?, ?, ?)");
        
        foreach ($cart_items as $item) {
            $stmt->execute([
                $order_id,
                $item['product_id'],
                $item['price'],
                $item['quantity']
            ]);
            
            // Update product stock
            $update_stock = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE product_id = ?");
            $update_stock->execute([$item['quantity'], $item['product_id']]);
        }
        
        // Clear the cart from database
        $clear_cart = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
        $clear_cart->execute([$user_id]);
        
        // Also clear cart from session
        unset($_SESSION['cart']);
        
        // Commit transaction
        $pdo->commit();
        
        // Set success response
        $response['success'] = true;
        $response['order_id'] = $order_id;
        $response['order_number'] = $order_number;
        
        // Set redirect URL based on payment method
        if ($payment_method === 'paystack') {
            $response['redirect'] = 'checkout_paystack.php?order_id=' . $order_id;
            error_log("Paystack redirect set: " . $response['redirect']);
        } else {
            $response['redirect'] = 'order_confirmation.php?order_id=' . $order_id;
            error_log("COD redirect set: " . $response['redirect']);
        }
        
        // Debug: Log the complete response
        error_log("Process checkout response: " . json_encode($response));
        
        // Send order confirmation email using PHPMailer
        try {
            $mail = new PHPMailer(true);
            
            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com'; // Your SMTP server
                $mail->SMTPAuth = true;
                $mail->Username = 'minatoflash82@gmail.com'; // SMTP username
                $mail->Password = 'negp ydit srrh gveq'; // SMTP password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Enable TLS encryption
                $mail->Port = 587; // TCP port to connect to
                
                // Recipients
                $mail->setFrom('minatoflash82@gmail.com', 'ASO Online Market');
                $mail->addAddress($email); // Add a recipient
                
                // Content
                $mail->isHTML(true);
                $mail->Subject = "Order Confirmation - $order_number";
                $mail->Body    = "
                    <h2>Thank you for your order!</h2>
                    <p>Your order #$order_number has been received and is being processed.</p>
                    <p><strong>Order Details:</strong></p>
                    <p>Order Number: $order_number</p>
                    <p>Order Total: " . formatCurrency($subtotal) . "</p>
                    <p>Payment Method: " . ucwords(str_replace('_', ' ', $payment_method)) . "</p>
                    <p>We'll notify you once your order ships. Thank you for shopping with us!</p>
                ";
                
                $mail->send();
                error_log("Order confirmation email sent to: $email");
                
            } catch (Exception $e) {
                error_log("Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
                // Continue with order processing even if email fails
            }
            
        } catch (Exception $e) {
            // Log email error but don't fail the order
            error_log("Failed to send order confirmation email: " . $e->getMessage());
        }
        
    } catch (Exception $e) {
        // Rollback transaction on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
    
} catch (PDOException $e) {
    $response['message'] = 'Database error: ' . $e->getMessage();
    error_log('Checkout error: ' . $e->getMessage());
} catch (Exception $e) {
    $response['message'] = 'An error occurred: ' . $e->getMessage();
    error_log('Checkout error: ' . $e->getMessage());
}

// Clean any previous output
while (ob_get_level()) {
    ob_end_clean();
}

// Set JSON header
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Debug: Log the final response being sent
$json_response = json_encode($response);
error_log("Final JSON response: " . $json_response);

// Check if JSON encoding was successful
if (json_last_error() !== JSON_ERROR_NONE) {
    error_log("JSON encoding error: " . json_last_error_msg());
    $response = ['success' => false, 'message' => 'Server error: Invalid response format'];
    $json_response = json_encode($response);
}

// Output the JSON response
echo $json_response;
exit();
