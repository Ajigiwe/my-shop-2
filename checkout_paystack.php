<?php
/**
 * Checkout Page - Paystack Payment
 * - Handles order placement for Paystack payment method
 * - Initializes Paystack payment
 * - Handles payment verification
 */

// Include database connection and config
require_once 'includes/db.php';
require_once 'includes/paystack_config.php';

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = 'checkout_paystack.php';
    header('Location: login.php');
    exit();
}

// Set page title
$page_title = 'Checkout - Pay with Paystack';

// Get user information
$user_id = $_SESSION['user_id'];

// Get cart items
try {
    $stmt = $pdo->prepare("
        SELECT c.*, p.name, p.price, p.image
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

// Calculate totals
$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$shipping = 0; // Add shipping calculation if needed
$total = $subtotal + $shipping;

// Process form submission
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $shipping_address = sanitizeInput($_POST['shipping_address'] ?? '');
    $billing_address = sanitizeInput($_POST['billing_address'] ?? $shipping_address);
    $notes = sanitizeInput($_POST['notes'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');

    // Validate form data
    if (empty($phone)) {
        $errors[] = 'Phone number is required';
    }
    if (empty($shipping_address)) {
        $errors[] = 'Shipping address is required';
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email is required for payment';
    }

    if (empty($errors)) {
        try {
            // Start transaction
            $pdo->beginTransaction();

            // Create order record
            $stmt = $pdo->prepare("
                INSERT INTO orders (user_id, total_amount, status, payment_method, shipping_address, billing_address, phone, notes)
                VALUES (?, ?, 'pending', 'paystack', ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $user_id,
                $total,
                $shipping_address,
                $billing_address,
                $phone,
                $notes
            ]);
            
            $order_id = $pdo->lastInsertId();
            
            // Add order items
            $stmt = $pdo->prepare("
                INSERT INTO order_items (order_id, product_id, quantity, price)
                VALUES (?, ?, ?, ?)
            ");
            
            foreach ($cart_items as $item) {
                $stmt->execute([
                    $order_id,
                    $item['product_id'],
                    $item['quantity'],
                    $item['price']
                ]);
            }
            
            // Initialize Paystack payment
            $reference = 'ORDER-' . $order_id . '-' . time();
            $amount_in_kobo = $total * 100; // Convert to kobo
            
            $payment_data = [
                'email' => $email,
                'amount' => $amount_in_kobo,
                'reference' => $reference,
                'callback_url' => 'http://' . $_SERVER['HTTP_HOST'] . '/verify_payment.php?order_id=' . $order_id,
                'metadata' => [
                    'order_id' => $order_id,
                    'custom_fields' => [
                        [
                            'display_name' => 'Order ID',
                            'variable_name' => 'order_id',
                            'value' => $order_id
                        ]
                    ]
                ]
            ];
            
            // Save the reference to the order
            $stmt = $pdo->prepare("UPDATE orders SET payment_reference = ? WHERE order_id = ?");
            $stmt->execute([$reference, $order_id]);
            
            // Initialize Paystack payment
            $paystack = new Yabacon\Paystack(PAYSTACK_SECRET_KEY);
            try {
                $response = $paystack->transaction->initialize($payment_data);
                
                if ($response->status) {
                    // Clear the cart
                    $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    
                    // Commit transaction
                    $pdo->commit();
                    
                    // Redirect to Paystack payment page
                    header('Location: ' . $response->data->authorization_url);
                    exit();
                } else {
                    throw new Exception('Failed to initialize payment');
                }
            } catch (Exception $e) {
                $pdo->rollBack();
                $errors[] = 'Payment initialization failed: ' . $e->getMessage();
                error_log('Paystack Error: ' . $e->getMessage());
            }
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'An error occurred while processing your order. Please try again.';
            error_log('Order Error: ' . $e->getMessage());
        }
    }
}

// If we get here, there was an error or the form hasn't been submitted yet
// Get user details to pre-fill the form
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching user details: " . $e->getMessage());
    $user = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - <?php echo STORE_NAME; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <!-- Checkout Section -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-8">
                    <h2 class="mb-4">Checkout - Pay with Paystack</h2>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" class="needs-validation" novalidate>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                                <div class="invalid-feedback">
                                    Please enter a valid email address.
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Phone <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required>
                                <div class="invalid-feedback">
                                    Please enter your phone number.
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="shipping_address" class="form-label">Shipping Address <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="shipping_address" name="shipping_address" rows="3" required><?php 
                                echo htmlspecialchars($user['address'] ?? ''); 
                            ?></textarea>
                            <div class="invalid-feedback">
                                Please enter your shipping address.
                            </div>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="same_as_shipping" checked>
                            <label class="form-check-label" for="same_as_shipping">Billing address is the same as shipping address</label>
                        </div>

                        <div class="mb-3" id="billing_address_group" style="display: none;">
                            <label for="billing_address" class="form-label">Billing Address</label>
                            <textarea class="form-control" id="billing_address" name="billing_address" rows="3"></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Order Notes (Optional)</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                        </div>

                        <h5 class="mt-4 mb-3">Payment Method</h5>
                        <div class="card mb-4">
                            <div class="card-body">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_method" id="paystack" value="paystack" checked>
                                    <label class="form-check-label" for="paystack">
                                        <i class="fas fa-credit-card me-2"></i> Pay with Paystack
                                    </label>
                                    <p class="text-muted small mt-1">Secure payment via Paystack (Cards, Mobile Money, Bank Transfer)</p>
                                </div>
                                <img src="assets/images/paystack.png" alt="Paystack" class="img-fluid mt-2" style="max-height: 30px;">
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-lock me-2"></i> Pay ₵<?php echo number_format($total, 2); ?> with Paystack
                            </button>
                        </div>
                    </form>
                </div>

                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Order Summary</h5>
                            <div class="table-responsive">
                                <table class="table">
                                    <tbody>
                                        <?php foreach ($cart_items as $item): ?>
                                            <tr>
                                                <td class="py-2">
                                                    <?php echo htmlspecialchars($item['name']); ?>
                                                    <div class="text-muted small">Qty: <?php echo $item['quantity']; ?></div>
                                                </td>
                                                <td class="text-end py-2">
                                                    ₵<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th>Subtotal</th>
                                            <td class="text-end">₵<?php echo number_format($subtotal, 2); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Shipping</th>
                                            <td class="text-end">₵<?php echo number_format($shipping, 2); ?></td>
                                        </tr>
                                        <tr class="table-active">
                                            <th>Total</th>
                                            <th class="text-end">₵<?php echo number_format($total, 2); ?></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-body">
                            <h6><i class="fas fa-lock me-2"></i>Secure Payment</h6>
                            <p class="small text-muted mb-0">Your payment information is processed securely. We do not store your credit card details.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script>
    // Form validation
    (function () {
        'use strict'
        
        // Fetch all the forms we want to apply custom Bootstrap validation styles to
        var forms = document.querySelectorAll('.needs-validation')
        
        // Loop over them and prevent submission
        Array.prototype.slice.call(forms)
            .forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    
                    form.classList.add('was-validated')
                }, false)
            })
    })()
    
    // Toggle billing address
    document.getElementById('same_as_shipping').addEventListener('change', function() {
        const billingAddressGroup = document.getElementById('billing_address_group');
        const billingAddress = document.getElementById('billing_address');
        
        if (this.checked) {
            billingAddressGroup.style.display = 'none';
            billingAddress.required = false;
        } else {
            billingAddressGroup.style.display = 'block';
            billingAddress.required = true;
        }
    });
    </script>
</body>
</html>
