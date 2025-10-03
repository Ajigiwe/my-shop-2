<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Checkout Page
 * - Handles order placement for Pay on Delivery payment method
 * - Creates order record and order items
 * - Redirects to appropriate payment processor or confirmation page
 */

// Include database connection and functions
require_once 'includes/db.php';
require 'vendor/autoload.php'; // Load Composer's autoloader
require_once 'includes/functions.php';

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = 'checkout.php';
    header('Location: login.php');
    exit();
}

// Get user ID and cart items
$user_id = $_SESSION['user_id'];
$errors = [];

// Get cart items
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
    $errors[] = "Unable to load your cart. Please try again.";
}

// If cart is empty, redirect to cart page
if (empty($cart_items)) {
    header('Location: cart.php');
    exit();
}

// Calculate total
$total = 0;
foreach ($cart_items as $item) {
    $total += $item['price'] * $item['quantity'];
}

// Initialize form variables
$phone = '';
$shipping_address = '';
$billing_address = '';
$email = '';
$order_notes = '';
$payment_method = 'paystack'; // Default payment method

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'process_order') {
    // Check if this is an AJAX request
    $is_ajax = isset($_POST['is_ajax']) && $_POST['is_ajax'] === '1';
    // Get form data
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $shipping_address = sanitizeInput($_POST['shipping_address'] ?? '');
    $billing_address = sanitizeInput($_POST['billing_address'] ?? $shipping_address);
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $order_notes = sanitizeInput($_POST['order_notes'] ?? '');
    $payment_method = $_POST['payment_method'] ?? 'paystack';
    
    // Validate form data
    if (empty($phone)) {
        $errors[] = 'Phone number is required';
    }
    if (empty($shipping_address)) {
        $errors[] = 'Shipping address is required';
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email address is required';
    }
    
    // Log the start of order processing
    error_log("Starting order processing. User ID: $user_id, Payment Method: " . ($payment_method ?? 'not set'));
    
    // If no validation errors, process the order
    if (empty($errors)) {
        try {
            // Calculate total amount from cart items
            $total = 0;
            foreach ($cart_items as $item) {
                $total += $item['price'] * $item['quantity'];
            }
            
            // Log the start of order processing
            
            // Start transaction
            $pdo->beginTransaction();
            
            // Create order with payment method
            $order_number = 'POD' . date('Ymd') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            $stmt = $pdo->prepare("
                INSERT INTO orders (
                    user_id, order_number, total_amount, status, 
                    payment_method, shipping_address, billing_address, 
                    phone, email, notes, created_at, updated_at
                ) VALUES (?, ?, ?, 'pending', ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            
            $stmt->execute([
                $user_id,
                $order_number,
                $total,
                $payment_method,
                $shipping_address,
                $billing_address,
                $phone,
                $email,
                $order_notes
            ]);
            
            $order_id = $pdo->lastInsertId();
            
            // Insert order items
            $stmt = $pdo->prepare("
                INSERT INTO order_items (
                    order_id, product_id, product_name, product_price, 
                    quantity, price, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            
            foreach ($cart_items as $item) {
                // Get product details
                $productStmt = $pdo->prepare("SELECT name, price FROM products WHERE product_id = ?");
                $productStmt->execute([$item['product_id']]);
                $product = $productStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($product) {
                    $stmt->execute([
                        $order_id,
                        $item['product_id'],
                        $product['name'],
                        $product['price'],
                        $item['quantity'],
                        $item['price'] * $item['quantity']
                    ]);
                    
                    // Update product stock
                    $update_stock = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE product_id = ?");
                    $update_stock->execute([$item['quantity'], $item['product_id']]);
                }
            }
            
            // Clear the cart after successful order
            $clear_cart = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
            $clear_cart->execute([$user_id]);
            
            // Commit transaction
            $pdo->commit();
            
            // Store order ID in session for payment processing
            $_SESSION['current_order_id'] = $order_id;
            
            // Send order confirmation email
            try {
                $to = $email;
                $subject = "Order Confirmation #$order_number";
                $message = "
                    <h2>Thank you for your order!</h2>
                    <p>Your order #$order_number has been received and is being processed.</p>
                    <p><strong>Order Details:</strong></p>
                    <p>Order Number: $order_number</p>
                    <p>Order Total: " . formatCurrency($total) . "</p>
                    <p>Payment Method: " . ucwords(str_replace('_', ' ', $payment_method)) . "</p>
                    <p>We'll notify you once your order ships. Thank you for shopping with us!</p>
                ";
                
                // Send email using PHPMailer
                $mail = new PHPMailer(true);
                
                try {
                    // Server settings
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'minatoflash82@gmail.com';
                    $mail->Password = 'negp ydit srrh gveq';
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;
                    
                    // Recipients
                    $mail->setFrom('minatoflash82@gmail.com', 'ASO Online Market');
                    $mail->addAddress($to);
                    
                    // Content
                    $mail->isHTML(true);
                    $mail->Subject = $subject;
                    $mail->Body    = $message;
                    
                    $mail->send();
                    error_log("Order confirmation email sent to: $to");
                    
                } catch (Exception $e) {
                    error_log("Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
                }
                
            } catch (Exception $e) {
                // Log email error but don't fail the order
                error_log("Failed to send order confirmation email: " . $e->getMessage());
            }
            
            // Return JSON response for AJAX
            header('Content-Type: application/json');
            $response = [
                'success' => true,
                'redirect' => 'order_confirmation.php?order_id=' . $order_id
            ];
            
            // Set redirect URL based on payment method
            if ($payment_method === 'paystack') {
                $response['redirect'] = 'checkout_paystack.php?order_id=' . $order_id;
            } else {
                $response['redirect'] = 'order_confirmation.php?order_id=' . $order_id;
            }
            
            // Always return JSON response
            header('Content-Type: application/json');
            echo json_encode($response);
            exit();
            
        } catch (PDOException $e) {
            // Rollback transaction on error
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            
            // Log detailed error information
            $errorDetails = [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'post_data' => $_POST,
                'user_id' => $user_id,
                'payment_method' => $payment_method ?? 'not set'
            ];
            
            error_log('Order processing error: ' . print_r($errorDetails, true));
            
            // Return detailed error in development, generic in production
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                http_response_code(500);
                $errorMessage = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || $_SERVER['SERVER_NAME'] === 'localhost')
                    ? 'Database Error: ' . $e->getMessage()
                    : 'An error occurred while processing your order. Please try again.';
                
                echo json_encode([
                    'success' => false,
                    'message' => $errorMessage,
                    'debug' => (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) ? $errorDetails : null
                ]);
                exit();
            }
            
            $errors[] = 'An error occurred while processing your order. Please try again.';
        }
    }
}

// Set page title
$page_title = 'Checkout - ' . ($payment_method === 'paystack' ? 'Pay with Paystack' : 'Pay on Delivery');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - ASO Online Market</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Other CSS and JS includes -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    
    <style>
        .checkout-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem 15px;
        }
        
        .checkout-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            height: 100%;
        }
        
        .checkout-header {
            background-color: var(--primary-color);
            color: white;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .checkout-header h1 {
            margin: 0;
            font-size: 2rem;
            font-weight: 700;
        }
        
        .form-label {
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: var(--gray-700);
        }
        
        .form-control, .form-select {
            padding: 0.75rem 1rem;
            border: 1px solid var(--gray-300);
            border-radius: 8px;
            transition: var(--transition);
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(58, 90, 64, 0.25);
        }
        
        .btn-checkout {
            background-color: var(--primary-color);
            border: none;
            color: white;
            padding: 1rem 2rem;
            font-weight: 600;
            border-radius: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: var(--transition);
            width: 100%;
        }
        
        .btn-checkout:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .order-summary-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            height: 100%;
        }
        
        .order-summary-header {
            background-color: var(--primary-color);
            color: white;
            padding: 1.25rem 1.5rem;
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .order-item {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--gray-200);
        }
        
        .order-item:last-child {
            border-bottom: none;
        }
        
        .order-total {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .product-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid var(--gray-200);
        }
        
        .quantity-badge {
            background-color: var(--primary-color);
            color: white;
            font-weight: 600;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <?php include 'includes/navbar.php'; ?>

    <div class="checkout-header">
        <div class="container">
            <h1>Checkout</h1>
            <p class="mb-0">Complete your purchase</p>
        </div>
    </div>

    <div class="container checkout-container">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Billing & Shipping Details -->
            <div class="col-lg-8">
                <div class="checkout-card h-100">
                    <div class="p-4">
                        <h2 class="h4 mb-4"><i class="fas fa-shipping-fast me-2"></i>Billing & Shipping Details</h2>
                        <form method="POST" id="checkout-form" action="process_checkout_simple.php">
                            <input type="hidden" name="payment_method" id="payment_method" value="paystack">
                            <input type="hidden" name="is_ajax" id="is_ajax" value="0">
                            <input type="hidden" name="action" id="action" value="process_order">
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="email" name="email" required 
                                           value="<?php echo htmlspecialchars($email); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                                    <input type="tel" class="form-control" id="phone" name="phone" required
                                           value="<?php echo htmlspecialchars($phone); ?>">
                                </div>
                                <div class="col-12">
                                    <label for="shipping_address" class="form-label">Shipping Address <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="shipping_address" name="shipping_address" 
                                              rows="3" required><?php echo htmlspecialchars($shipping_address); ?></textarea>
                                </div>
                                <div class="col-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="same-billing-address" checked>
                                        <label class="form-check-label" for="same-billing-address">
                                            Billing address is the same as shipping address
                                        </label>
                                    </div>
                                </div>
                                <div class="col-12" id="billing-address-container" style="display: none;">
                                    <label for="billing_address" class="form-label">Billing Address</label>
                                    <textarea class="form-control" id="billing_address" name="billing_address" 
                                              rows="3"><?php echo htmlspecialchars($billing_address); ?></textarea>
                                </div>
                                <div class="col-12">
                                    <label for="order_notes" class="form-label">Order Notes (Optional)</label>
                                    <textarea class="form-control" id="order_notes" name="order_notes" 
                                              placeholder="Notes about your order, e.g. special delivery instructions"
                                              rows="3"><?php echo htmlspecialchars($order_notes); ?></textarea>
                                </div>
                            </div>

                            <hr class="my-4">

                            <h5 class="mb-3"><i class="fas fa-credit-card me-2"></i>Payment Method</h5>
                            <div class="payment-methods mb-4">
                                <div class="form-check mb-3 p-3 border rounded">
                                    <input class="form-check-input payment-method" type="radio" name="payment_method_radio" 
                                           id="paystack" value="paystack" checked>
                                    <label class="form-check-label d-flex align-items-center ms-2" for="paystack">
                                        <i class="fas fa-credit-card fa-lg me-2 text-primary"></i>
                                        <div>
                                            <div class="fw-semibold">Pay with Paystack</div>
                                            <small class="text-muted">Pay securely using your credit/debit card</small>
                                        </div>
                                    </label>
                                </div>
                                <div class="form-check p-3 border rounded">
                                    <input class="form-check-input payment-method" type="radio" name="payment_method_radio" 
                                           id="cod" value="cod">
                                    <label class="form-check-label d-flex align-items-center ms-2" for="cod">
                                        <i class="fas fa-truck fa-lg me-2 text-primary"></i>
                                        <div>
                                            <div class="fw-semibold">Pay on Delivery</div>
                                            <small class="text-muted">Pay with cash upon delivery</small>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-checkout" id="submit-btn">
                                <i class="fas fa-lock me-2"></i>Place Order
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Order Summary -->
            <div class="col-lg-4">
                <div class="order-summary-card">
                    <div class="order-summary-header">
                        <i class="fas fa-receipt me-2"></i>Order Summary
                    </div>
                    <div class="order-items">
                        <?php 
                        $subtotal = 0;
                        foreach ($cart_items as $item): 
                            $item_total = $item['price'] * $item['quantity'];
                            $subtotal += $item_total;
                        ?>
                            <div class="order-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="d-flex">
                                        <img src="assets/images/<?php echo htmlspecialchars($item['image']); ?>" 
                                             alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                             class="product-img me-3">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h6>
                                            <?php if (!empty($item['size']) || !empty($item['color'])): ?>
                                                <p class="text-muted small mb-0">
                                                    <?php 
                                                        echo (!empty($item['size']) ? 'Size: ' . htmlspecialchars($item['size']) : '') . 
                                                             (!empty($item['size']) && !empty($item['color']) ? ' • ' : '') .
                                                             (!empty($item['color']) ? 'Color: ' . htmlspecialchars($item['color']) : '');
                                                    ?>
                                                </p>
                                            <?php endif; ?>
                                            <div class="mt-2">
                                                <span class="quantity-badge">Qty: <?php echo $item['quantity']; ?></span>
                                                <span class="ms-2 fw-medium"><?php echo formatCurrency($item['price']); ?> each</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-semibold"><?php echo formatCurrency($item_total); ?></div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="p-4 border-top">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Subtotal:</span>
                            <span class="fw-medium"><?php echo formatCurrency($subtotal); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-muted">Shipping:</span>
                            <span class="text-success fw-medium">Free</span>
                        </div>
                        <div class="d-flex justify-content-between border-top pt-3">
                            <span class="fw-bold">Total:</span>
                            <span class="order-total"><?php echo formatCurrency($subtotal); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
    $(document).ready(function() {
        let isSubmitting = false;

        // Function to show error messages
        function showError(message) {
            const errorAlert = `
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
            
            // Remove any existing alerts
            $('.alert.alert-danger').remove();
            
            // Prepend the error alert to the container
            $('.checkout-container').prepend(errorAlert);
            
            // Scroll to the top to show the error
            $('html, body').animate({
                scrollTop: $('.checkout-container').offset().top - 50
            }, 300);
        }

        // Handle form submission - ALWAYS prevent default
        $('#checkout-form').on('submit', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const paymentMethod = $('input[name="payment_method_radio"]:checked').val();
            const isPaystack = paymentMethod === 'paystack';
            
            // Set payment method in hidden field
            $('#payment_method').val(isPaystack ? 'paystack' : 'cash_on_delivery');
            
            // Set is_ajax flag
            $('#is_ajax').val('1');
            
            // Debug: Log the payment method being sent
            console.log('Payment method being sent:', $('#payment_method').val());
            console.log('Is AJAX:', $('#is_ajax').val());
            
            // Prevent multiple submissions
            if (isSubmitting) {
                console.log('Form submission already in progress');
                return false;
            }
            
            isSubmitting = true;
            const submitBtn = $('#submit-btn');
            const originalBtnText = submitBtn.html();
            
            // Show loading state
            submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Processing...');
            
            // Get form data
            const formData = $(this).serialize();
            
            // Log the form data for debugging
            console.log('Submitting form:', formData);
            
            // Submit the form via AJAX
            console.log('Starting AJAX request to process_checkout.php');
            $.ajax({
                url: 'process_checkout_simple.php',
                method: 'POST',
                data: formData,
                dataType: 'json',
                timeout: 30000, // 30 second timeout
                beforeSend: function(xhr) {
                    console.log('AJAX request being sent...');
                },
                success: function(response, status, xhr) {
                    console.log('AJAX Success - Raw Response:', response);
                    console.log('Status:', status);
                    console.log('Response Type:', typeof response);
                    console.log('Response Length:', response ? response.length : 'null');
                    
                    // Re-enable the submit button
                    isSubmitting = false;
                    submitBtn.prop('disabled', false).html(originalBtnText);
                    
                    try {
                        // If response is a string, try to parse it as JSON
                        let jsonResponse = response;
                        if (typeof response === 'string') {
                            console.log('Parsing string response as JSON');
                            jsonResponse = JSON.parse(response);
                        }
                        
                        console.log('Processed Response:', jsonResponse);
                        console.log('Response Success:', jsonResponse ? jsonResponse.success : 'null');
                        console.log('Response Redirect:', jsonResponse ? jsonResponse.redirect : 'null');
                        
                        if (jsonResponse && jsonResponse.success) {
                            // Store order ID in session storage
                            if (jsonResponse.order_id) {
                                console.log('Storing order ID in session:', jsonResponse.order_id);
                                sessionStorage.setItem('last_order_id', jsonResponse.order_id);
                            }
                            
                            // Determine redirect URL
                            let redirectUrl = jsonResponse.redirect || 
                                           (jsonResponse.order_id ? 'order_confirmation.php?order_id=' + jsonResponse.order_id : 'order_confirmation.php');
                            
                            console.log('Preparing to redirect to:', redirectUrl);
                            console.log('Response redirect field:', jsonResponse.redirect);
                            console.log('Payment method selected:', paymentMethod);
                            
                            // Force a hard redirect with delay to see console
                            console.log('Redirecting in 3 seconds to:', redirectUrl);
                            
                            // Show redirect message on page
                            $('body').prepend('<div id="redirect-message" style="position: fixed; top: 0; left: 0; width: 100%; background: #28a745; color: white; padding: 10px; text-align: center; z-index: 9999;">Redirecting to Paystack in 3 seconds... <button onclick="clearTimeout(window.redirectTimeout); document.getElementById(\'redirect-message\').remove();" style="margin-left: 10px; background: white; color: #28a745; border: none; padding: 5px 10px; border-radius: 3px;">Cancel</button></div>');
                            
                            window.redirectTimeout = setTimeout(function() {
                                window.location.replace(redirectUrl);
                            }, 3000);
                            return false;
                        } else {
                            // Handle error response
                            const errorMessage = (jsonResponse && jsonResponse.message) || 'An unknown error occurred. Please try again.';
                            console.error('Checkout error:', errorMessage);
                            showError(errorMessage);
                        }
                        
                    } catch (e) {
                        console.error('Error processing response:', e);
                        console.error('Raw response text:', xhr.responseText);
                        showError('An error occurred while processing your order. Please try again.');
                    }
                    
                    // Scroll to the top to show any error message
                    $('html, body').animate({
                        scrollTop: $('.checkout-container').offset().top - 50
                    }, 500);
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', status, error);
                    console.error('Response text:', xhr.responseText);
                    console.error('Status code:', xhr.status);
                    console.error('Ready state:', xhr.readyState);
                    
                    // Re-enable the submit button
                    isSubmitting = false;
                    submitBtn.prop('disabled', false).html(originalBtnText);
                    
                    let errorMessage = 'An error occurred while processing your request. Please try again.';
                    
                    // Try to parse error response
                    try {
                        const errorResponse = JSON.parse(xhr.responseText);
                        if (errorResponse && errorResponse.message) {
                            errorMessage = errorResponse.message;
                        }
                    } catch (e) {
                        console.error('Error parsing error response:', e);
                    }
                    
                    showError(errorMessage);
                    return false;
                },
                complete: function(xhr, status) {
                    console.log('AJAX request completed with status:', status);
                    console.log('HTTP Status:', xhr.status);
                }
            });
            
            // Always prevent form submission
            return false;
        });
    });
        
        // Function to show error messages
        function showError(message) {
            const errorAlert = `
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
            
            // Remove any existing alerts
            $('.alert.alert-danger').remove();
            
            // Prepend the error alert to the container
            $('.checkout-container').prepend(errorAlert);
            
            // Scroll to the top to show the error
            $('html, body').animate({
                scrollTop: $('.checkout-container').offset().top - 50
            }, 300);
        }
    </script>
</body>
</html>
