<?php
/**
 * Simple Checkout Processor
 * Uses the same approach as test_paystack_basic.php (no database required)
 */

// Start output buffering
ob_start();

session_start();
require_once 'includes/env_loader.php';
require_once 'includes/db.php';

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
    
    // Get form data
    $payment_method = $_POST['payment_method'] ?? 'paystack';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $shipping_address = $_POST['shipping_address'] ?? '';
    $billing_address = $_POST['billing_address'] ?? $shipping_address;
    $order_notes = $_POST['order_notes'] ?? '';
    
    // Validate required fields
    if (empty($email) || empty($phone) || empty($shipping_address)) {
        throw new Exception('Please fill in all required fields.');
    }
    
    // Get cart items from database (same as main checkout.php)
    $user_id = $_SESSION['user_id'] ?? 1;
    try {
        $stmt = $pdo->prepare("
            SELECT c.*, p.name, p.price, p.image, p.stock_quantity, p.product_id 
            FROM cart c 
            JOIN products p ON c.product_id = p.product_id 
            WHERE c.user_id = ?
        ");
        $stmt->execute([$user_id]);
        $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Error fetching cart items: " . $e->getMessage());
        $cart_items = [];
    }
    
    if (empty($cart_items)) {
        throw new Exception('Your cart is empty.');
    }
    
    // Calculate total
    $subtotal = 0;
    foreach ($cart_items as $item) {
        $subtotal += $item['price'] * $item['quantity'];
    }
    
    // Get Paystack keys
    $paystack_public_key = $_ENV['PAYSTACK_PUBLIC_KEY'] ?? '';
    $paystack_secret_key = $_ENV['PAYSTACK_SECRET_KEY'] ?? '';
    
    if (empty($paystack_secret_key)) {
        throw new Exception('Paystack configuration not found.');
    }
    
    if ($payment_method === 'paystack') {
        // Generate order ID
        $order_id = 'order_' . time() . '_' . uniqid();
        error_log("Starting Paystack payment for order: " . $order_id);
        
        // Prepare payment data (same as working test file)
        $payment_data = [
            'amount' => (int)($subtotal * 100), // Convert to kobo
            'email' => $email,
            'reference' => 'TXN_' . time() . '_' . uniqid(),
            'metadata' => [
                'order_id' => $order_id,
                'user_id' => $_SESSION['user_id'] ?? 1,
                'customer_name' => $_SESSION['user_name'] ?? 'Customer',
                'phone' => $phone,
                'shipping_address' => $shipping_address
            ]
        ];
        
        // Initialize Paystack payment using direct cURL (same as working test)
        $url = 'https://api.paystack.co/transaction/initialize';
        error_log("Making Paystack API call to: " . $url);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payment_data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $paystack_secret_key,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $paystack_response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception('cURL error: ' . $error);
        }
        
        if ($httpCode !== 200) {
            throw new Exception('HTTP error: ' . $httpCode . ' - ' . $paystack_response);
        }
        
        $paystack_result = json_decode($paystack_response);
        
        error_log("Paystack response: " . $paystack_response);
        error_log("Paystack result status: " . ($paystack_result->status ?? 'null'));
        
        if (!$paystack_result) {
            throw new Exception('Invalid JSON response from Paystack');
        }
        
        if ($paystack_result->status) {
            error_log("Paystack initialization successful");
            
            // Try to save order to database
            try {
                $user_id = $_SESSION['user_id'] ?? 1;
                
                // Insert order into database
                $order_number = 'ORD-' . time() . '-' . uniqid();
                $stmt = $pdo->prepare("
                    INSERT INTO orders (user_id, total_amount, payment_method, shipping_address, billing_address, order_notes, payment_reference, status, order_date, order_number) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW(), ?)
                ");
                $stmt->execute([
                    $user_id,
                    $subtotal,
                    'paystack',
                    $shipping_address,
                    $billing_address,
                    $order_notes,
                    $paystack_result->data->reference,
                    $order_number
                ]);
                
                $db_order_id = $pdo->lastInsertId();
                
                // Insert order items
                foreach ($cart_items as $item) {
                    $stmt = $pdo->prepare("
                        INSERT INTO order_items (order_id, product_id, quantity, price) 
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $db_order_id,
                        $item['product_id'],
                        $item['quantity'],
                        $item['price']
                    ]);
                }
                
                // Clear cart only after successful database save
                error_log("Order saved to database successfully, clearing cart");
                $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
                $stmt->execute([$user_id]);
                error_log("Database cart cleared successfully");
                
                $response['success'] = true;
                $response['message'] = 'Payment initialized successfully';
                $response['order_id'] = $db_order_id;
                $response['redirect'] = 'checkout_paystack_fixed.php?order_id=' . $db_order_id;
                
            } catch (Exception $db_error) {
                // If database fails, fall back to session storage
                error_log('Database error: ' . $db_error->getMessage());
                
                $_SESSION['pending_order'] = [
                    'order_id' => $order_id,
                    'payment_reference' => $paystack_result->data->reference,
                    'amount' => $subtotal,
                    'email' => $email,
                    'phone' => $phone,
                    'shipping_address' => $shipping_address,
                    'billing_address' => $billing_address,
                    'order_notes' => $order_notes,
                    'cart_items' => $cart_items,
                    'payment_method' => 'paystack'
                ];
                
                // Clear cart only after successful session storage
                error_log("Order saved to session successfully, clearing cart");
                $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
                $stmt->execute([$user_id]);
                error_log("Database cart cleared successfully");
                
                $response['success'] = true;
                $response['message'] = 'Payment initialized successfully';
                $response['order_id'] = $order_id;
                $response['redirect'] = 'checkout_paystack_fixed.php?order_id=' . $order_id;
            }
            
        } else {
            throw new Exception('Payment initialization failed: ' . ($paystack_result->message ?? 'Unknown error'));
        }
        
    } else {
        // Cash on Delivery - try to save to database first
        try {
            $user_id = $_SESSION['user_id'] ?? 1;
            
            // Insert order into database
            $order_number = 'COD-' . time() . '-' . uniqid();
            $stmt = $pdo->prepare("
                INSERT INTO orders (user_id, total_amount, payment_method, shipping_address, billing_address, order_notes, status, order_date, order_number) 
                VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW(), ?)
            ");
            $stmt->execute([
                $user_id,
                $subtotal,
                'cash_on_delivery',
                $shipping_address,
                $billing_address,
                $order_notes,
                $order_number
            ]);
            
            $db_order_id = $pdo->lastInsertId();
            
            // Insert order items
            foreach ($cart_items as $item) {
                $stmt = $pdo->prepare("
                    INSERT INTO order_items (order_id, product_id, quantity, price) 
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([
                    $db_order_id,
                    $item['product_id'],
                    $item['quantity'],
                    $item['price']
                ]);
            }
            
            // Clear cart only after successful database save
            error_log("COD order saved to database successfully, clearing cart");
            $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->execute([$user_id]);
            error_log("Database cart cleared successfully");
            
            $response['success'] = true;
            $response['message'] = 'Order created successfully';
            $response['order_id'] = $db_order_id;
            $response['redirect'] = 'order_confirmation.php?order_id=' . $db_order_id;
            
        } catch (Exception $db_error) {
            // If database fails, fall back to session storage
            error_log('Database error: ' . $db_error->getMessage());
            
            $order_id = 'cod_' . time() . '_' . uniqid();
            
            $_SESSION['pending_order'] = [
                'order_id' => $order_id,
                'amount' => $subtotal,
                'email' => $email,
                'phone' => $phone,
                'shipping_address' => $shipping_address,
                'billing_address' => $billing_address,
                'order_notes' => $order_notes,
                'cart_items' => $cart_items,
                'payment_method' => 'cash_on_delivery'
            ];
            
            // Clear cart only after successful session storage
            error_log("COD order saved to session successfully, clearing cart");
            $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->execute([$user_id]);
            error_log("Database cart cleared successfully");
            
            $response['success'] = true;
            $response['message'] = 'Order created successfully';
            $response['order_id'] = $order_id;
            $response['redirect'] = 'order_confirmation.php?order_id=' . $order_id;
        }
    }
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    error_log('Checkout error: ' . $e->getMessage());
}

// Clear any output buffer
ob_end_clean();

// Set JSON header
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Output JSON response
echo json_encode($response);
exit();
?>
