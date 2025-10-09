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
            
            // Generate unique order number
            $order_number = 'ORD-' . time() . '-' . uniqid();
            
            // Start transaction
            $pdo->beginTransaction();
            
            // Insert order into database
            $stmt = $pdo->prepare("
                INSERT INTO orders (user_id, total_amount, payment_method, shipping_address, billing_address, order_notes, status, order_date, order_number) 
                VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW(), ?)
            ");
            $stmt->execute([
                $user_id,
                $total,
                $payment_method,
                $shipping_address,
                $billing_address,
                $order_notes,
                $order_number
            ]);
            
            $order_id = $pdo->lastInsertId();
            
            // Insert order items
            foreach ($cart_items as $item) {
                $stmt = $pdo->prepare("
                    INSERT INTO order_items (order_id, product_id, quantity, price) 
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([
                    $order_id,
                    $item['product_id'],
                    $item['quantity'],
                    $item['price']
                ]);
            }
            
            // Clear cart
            $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->execute([$user_id]);
            
            // Commit transaction
            $pdo->commit();
            
            // Send order confirmation email
            try {
                $to = $email;
                $subject = "Order Confirmation - Order #$order_id";
                $message = "
                    <h2>Thank you for your order!</h2>
                    <p>Your order has been received and is being processed.</p>
                    <p><strong>Order ID:</strong> $order_id</p>
                    <p><strong>Total Amount:</strong> " . formatCurrency($total) . "</p>
                    <p><strong>Payment Method:</strong> " . ucfirst(str_replace('_', ' ', $payment_method)) . "</p>
                ";
                
                $mail = new PHPMailer(true);
                
                // Server settings
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'minatoflash82@gmail.com';
                $mail->Password   = 'your-app-password';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;
                
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
            
            // Return JSON response for AJAX
            header('Content-Type: application/json');
            $response = [
                'success' => true,
                'redirect' => 'order_confirmation.php?order_id=' . $order_id
            ];
            
            // Set redirect URL based on payment method
            if ($payment_method === 'paystack') {
                $response['redirect'] = 'checkout_paystack_fixed.php?order_id=' . $order_id;
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <style>
        .checkout-container {
            background: linear-gradient(135deg, var(--gray-50) 0%, var(--white) 100%);
            min-height: 100vh;
        }
        
        .checkout-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border-radius: 0 0 var(--radius-xl) var(--radius-xl);
            box-shadow: var(--shadow-lg);
        }
        
        .checkout-card {
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
            overflow: hidden;
        }
        
        .checkout-card:hover {
            box-shadow: var(--shadow-md);
        }
        
        .form-section {
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-200);
            overflow: hidden;
        }
        
        .form-section-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 1.5rem;
            font-weight: 600;
        }
        
        .form-control, .form-select {
            border: 1px solid var(--gray-300);
            border-radius: var(--radius);
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(58, 90, 64, 0.25);
        }
        
        .form-label {
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 0.5rem;
        }
        
        .payment-method-card {
            border: 2px solid var(--gray-200);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            transition: all 0.3s ease;
            cursor: pointer;
            background: white;
        }
        
        .payment-method-card:hover {
            border-color: var(--primary-color);
            box-shadow: var(--shadow-sm);
        }
        
        .payment-method-card.selected {
            border-color: var(--primary-color);
            background: var(--gray-50);
            box-shadow: var(--shadow-sm);
        }
        
        .btn-checkout {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
            color: white;
            padding: 1rem 2rem;
            font-weight: 600;
            border-radius: var(--radius);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .btn-checkout:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .btn-checkout:disabled {
            opacity: 0.6;
            transform: none;
        }
        
        .order-summary-card {
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--gray-200);
            overflow: hidden;
        }
        
        .order-summary-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 1.5rem;
            font-weight: 600;
        }
        
        .order-item {
            padding: 1.5rem;
            border-bottom: 1px solid var(--gray-200);
            transition: background-color 0.2s ease;
        }
        
        .order-item:hover {
            background: var(--gray-50);
        }
        
        .order-item:last-child {
            border-bottom: none;
        }
        
        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: var(--radius);
            border: 1px solid var(--gray-200);
        }
        
        .quantity-badge {
            background: var(--primary-color);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: var(--radius);
            font-weight: 600;
            font-size: 0.8rem;
        }
        
        .order-total {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .security-badge {
            background: var(--success-color);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: var(--radius);
            font-size: 0.9rem;
            font-weight: 600;
        }
        
        .step-indicator {
            display: flex;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .step {
            display: flex;
            align-items: center;
            color: var(--gray-500);
        }
        
        .step.active {
            color: var(--primary-color);
        }
        
        .step.completed {
            color: var(--success-color);
        }
        
        .step-number {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--gray-300);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-right: 0.75rem;
        }
        
        .step.active .step-number {
            background: var(--primary-color);
        }
        
        .step.completed .step-number {
            background: var(--success-color);
        }
        
        .step-connector {
            width: 40px;
            height: 2px;
            background: var(--gray-300);
            margin: 0 1rem;
        }
        
        .step.completed + .step-connector {
            background: var(--success-color);
        }
    </style>
</head>
<body class="checkout-container">

<?php include 'includes/navbar.php'; ?>

<!-- Checkout Header -->
<div class="checkout-header">
    <div class="container py-5">
        <div class="row">
            <div class="col-12">
                <h1 class="display-4 fw-bold mb-3">
                    <i class="fas fa-credit-card me-3"></i>Checkout
                </h1>
                <p class="lead mb-0">Complete your purchase securely</p>
            </div>
        </div>
    </div>
</div>

<div class="container py-5">
    <!-- Step Indicator -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="step-indicator justify-content-center">
                <div class="step completed">
                    <div class="step-number">1</div>
                    <span>Cart</span>
                </div>
                <div class="step-connector"></div>
                <div class="step active">
                    <div class="step-number">2</div>
                    <span>Checkout</span>
                </div>
                <div class="step-connector"></div>
                <div class="step">
                    <div class="step-number">3</div>
                    <span>Payment</span>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="alert alert-danger border-0 shadow-sm">
                    <h6 class="alert-heading">
                        <i class="fas fa-exclamation-triangle me-2"></i>Please fix the following errors:
                    </h6>
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Checkout Form -->
        <div class="col-lg-8">
            <div class="form-section">
                <div class="form-section-header">
                    <h4 class="mb-0">
                        <i class="fas fa-shipping-fast me-2"></i>Billing & Shipping Details
                    </h4>
                </div>
                
                <div class="p-4">
                    <form method="POST" id="checkout-form" action="process_checkout_simple.php">
                        <input type="hidden" name="payment_method" id="payment_method" value="paystack">
                        <input type="hidden" name="is_ajax" id="is_ajax" value="0">
                        <input type="hidden" name="action" id="action" value="process_order">
                        
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label for="email" class="form-label">
                                    <i class="fas fa-envelope me-2"></i>Email Address <span class="text-danger">*</span>
                                </label>
                                <input type="email" class="form-control" id="email" name="email" required 
                                       value="<?php echo htmlspecialchars($email); ?>"
                                       placeholder="Enter your email address">
                            </div>
                            
                            <div class="col-md-6">
                                <label for="phone" class="form-label">
                                    <i class="fas fa-phone me-2"></i>Phone Number <span class="text-danger">*</span>
                                </label>
                                <input type="tel" class="form-control" id="phone" name="phone" required
                                       value="<?php echo htmlspecialchars($phone); ?>"
                                       placeholder="Enter your phone number">
                            </div>
                            
                            <div class="col-12">
                                <label for="shipping_address" class="form-label">
                                    <i class="fas fa-map-marker-alt me-2"></i>Shipping Address <span class="text-danger">*</span>
                                </label>
                                <textarea class="form-control" id="shipping_address" name="shipping_address" 
                                          rows="3" required placeholder="Enter your complete shipping address"><?php echo htmlspecialchars($shipping_address); ?></textarea>
                            </div>
                            
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="same-billing-address" checked>
                                    <label class="form-check-label" for="same-billing-address">
                                        <i class="fas fa-check-circle me-2"></i>Billing address is the same as shipping address
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-12" id="billing-address-container" style="display: none;">
                                <label for="billing_address" class="form-label">
                                    <i class="fas fa-building me-2"></i>Billing Address
                                </label>
                                <textarea class="form-control" id="billing_address" name="billing_address" 
                                          rows="3" placeholder="Enter your billing address"><?php echo htmlspecialchars($billing_address); ?></textarea>
                            </div>
                            
                            <div class="col-12">
                                <label for="order_notes" class="form-label">
                                    <i class="fas fa-sticky-note me-2"></i>Order Notes (Optional)
                                </label>
                                <textarea class="form-control" id="order_notes" name="order_notes" 
                                          placeholder="Special delivery instructions or notes about your order"
                                          rows="3"><?php echo htmlspecialchars($order_notes); ?></textarea>
                            </div>
                        </div>

                        <hr class="my-4">

                        <!-- Payment Method Selection -->
                        <h5 class="mb-4">
                            <i class="fas fa-credit-card me-2"></i>Payment Method
                        </h5>
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="payment-method-card" data-payment="paystack">
                                    <div class="form-check">
                                        <input class="form-check-input payment-method" type="radio" name="payment_method_radio" 
                                               id="paystack" value="paystack" checked>
                                        <label class="form-check-label w-100" for="paystack">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-credit-card fa-2x me-3 text-primary"></i>
                                                <div>
                                                    <h6 class="mb-1 fw-bold">Pay with Paystack</h6>
                                                    <small class="text-muted">Secure online payment</small>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="payment-method-card" data-payment="cod">
                                    <div class="form-check">
                                        <input class="form-check-input payment-method" type="radio" name="payment_method_radio" 
                                               id="cod" value="cod">
                                        <label class="form-check-label w-100" for="cod">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-truck fa-2x me-3 text-primary"></i>
                                                <div>
                                                    <h6 class="mb-1 fw-bold">Pay on Delivery</h6>
                                                    <small class="text-muted">Pay with cash upon delivery</small>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-checkout" id="submit-btn">
                                <i class="fas fa-lock me-2"></i>Place Order Securely
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Order Summary -->
        <div class="col-lg-4">
            <div class="order-summary-card">
                <div class="order-summary-header">
                    <h5 class="mb-0">
                        <i class="fas fa-receipt me-2"></i>Order Summary
                    </h5>
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
                                         class="product-image me-3">
                                    <div>
                                        <h6 class="mb-1 fw-bold"><?php echo htmlspecialchars($item['name']); ?></h6>
                                        <div class="mt-2">
                                            <span class="quantity-badge">Qty: <?php echo $item['quantity']; ?></span>
                                            <span class="ms-2 fw-medium text-muted"><?php echo formatCurrency($item['price']); ?> each</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="fw-bold text-success"><?php echo formatCurrency($item_total); ?></div>
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
                        <span class="fw-bold fs-5">Total:</span>
                        <span class="order-total"><?php echo formatCurrency($subtotal); ?></span>
                    </div>
                    
                    <div class="mt-4 text-center">
                        <div class="security-badge">
                            <i class="fas fa-shield-alt me-2"></i>Secure Checkout
                        </div>
                        <p class="small text-muted mt-2 mb-0">
                            Your payment information is encrypted and secure
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>


<script>
$(document).ready(function() {
    let isSubmitting = false;

    // Payment method selection
    $('.payment-method-card').on('click', function() {
        $('.payment-method-card').removeClass('selected');
        $(this).addClass('selected');
        $(this).find('input[type="radio"]').prop('checked', true);
    });

    // Billing address toggle
    $('#same-billing-address').on('change', function() {
        if ($(this).is(':checked')) {
            $('#billing-address-container').slideUp();
        } else {
            $('#billing-address-container').slideDown();
        }
    });

    // Function to show error messages
    function showError(message) {
        const errorAlert = `
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>${message}
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

    // Handle form submission
    $('#checkout-form').on('submit', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const paymentMethod = $('input[name="payment_method_radio"]:checked').val();
        const isPaystack = paymentMethod === 'paystack';
        
        // Set payment method in hidden field
        $('#payment_method').val(isPaystack ? 'paystack' : 'cash_on_delivery');
        
        // Set is_ajax flag
        $('#is_ajax').val('1');
        
        // Prevent multiple submissions
        if (isSubmitting) {
            return false;
        }
        
        isSubmitting = true;
        const submitBtn = $('#submit-btn');
        const originalBtnText = submitBtn.html();
        
        // Show loading state
        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Processing...');
        
        // Get form data
        const formData = $(this).serialize();
        
        // Submit the form via AJAX
        $.ajax({
            url: 'process_checkout.php',
            method: 'POST',
            data: formData,
            dataType: 'json',
            timeout: 30000,
            success: function(response, status, xhr) {
                // Re-enable the submit button
                isSubmitting = false;
                submitBtn.prop('disabled', false).html(originalBtnText);
                
                try {
                    let jsonResponse = response;
                    if (typeof response === 'string') {
                        jsonResponse = JSON.parse(response);
                    }
                    
                    if (jsonResponse && jsonResponse.success) {
                        // Store order ID in session storage
                        if (jsonResponse.order_id) {
                            sessionStorage.setItem('last_order_id', jsonResponse.order_id);
                        }
                        
                        // Determine redirect URL
                        let redirectUrl = jsonResponse.redirect || 
                                       (jsonResponse.order_id ? 'order_confirmation.php?order_id=' + jsonResponse.order_id : 'order_confirmation.php');
                        
                        // Redirect immediately
                        window.location.replace(redirectUrl);
                        return false;
                    } else {
                        const errorMessage = (jsonResponse && jsonResponse.message) || 'An unknown error occurred. Please try again.';
                        showError(errorMessage);
                    }
                    
                } catch (e) {
                    showError('An error occurred while processing your order. Please try again.');
                }
                
                // Scroll to the top to show any error message
                $('html, body').animate({
                    scrollTop: $('.checkout-container').offset().top - 50
                }, 500);
            },
            error: function(xhr, status, error) {
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
                    // Use default error message
                }
                
                showError(errorMessage);
                return false;
            }
        });
        
        return false;
    });
});
</script>

</body>
</html>