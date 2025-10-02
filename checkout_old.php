<?php
/**
 * Checkout Page
 * - Handles order placement for both Pay on Delivery and Paystack payment methods
 * - Creates order record and order items
 * - Redirects to appropriate payment processor or confirmation page
 */

// Include database connection and functions
require_once 'includes/db.php';
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
        SELECT c.*, p.name, p.price, p.image, p.stock_quantity 
        FROM cart c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Error fetching cart items: " . $e->getMessage());
    $cart_items = [];
    $errors[] = "Unable to load your cart. Please try again.";
}

// Calculate totals
$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

$tax = 0; // No tax
$total = $subtotal; // Total is same as subtotal without tax

// Handle form submission
$phone = '';
$shipping_address = '';
$billing_address = '';
$email = '';
$order_notes = '';
$payment_method = 'paystack'; // Initialize variables

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $shipping_address = sanitizeInput($_POST['shipping_address'] ?? '');
    $billing_address = sanitizeInput($_POST['billing_address'] ?? $shipping_address);
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $order_notes = sanitizeInput($_POST['order_notes'] ?? '');
            ");
            
            $stmt->execute([
                $user_id,
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
                INSERT INTO order_items (order_id, product_id, quantity, price, size, color)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            foreach ($cart_items as $item) {
                try {
                    $stmt->execute([
                        $order_id,
                        $item['product_id'],
                        $item['quantity'],
                        $item['price'],
                        $item['size'] ?? null,
                        $item['color'] ?? null
                    ]);
                } catch (PDOException $e) {
                    error_log("Error inserting order item: " . $e->getMessage());
                    $errors[] = 'Failed to insert order item. Please try again.';
                }
            }
            
            // Clear the cart after successful order
            $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->execute([$user_id]);
            
            // Commit transaction
            $pdo->commit();
            
            // Redirect based on payment method
            if ($payment_method === 'paystack') {
                header('Location: checkout_paystack.php?order_id=' . $order_id);
                exit();
            } else {
                header('Location: order_confirmation.php?order_id=' . $order_id);
                exit();
            }
            
        } catch (PDOException $e) {
            // Rollback transaction on error
            $pdo->rollBack();
            $errors[] = 'An error occurred while processing your order. Please try again.';
            error_log('Order processing error: ' . $e->getMessage());
        }
    }
            ");
            $stmt->execute([
                $user_id,
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
            foreach ($cart_items as $item) {
                $subtotal = $item['price'] * $item['quantity'];
                $stmt = $pdo->prepare("
                    INSERT INTO order_items (order_id, product_id, product_name, product_price, quantity, subtotal)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$order_id, $item['product_id'], $item['name'], $item['price'], $item['quantity'], $subtotal]);
            }

            // Insert status update
            $stmt = $pdo->prepare("
                INSERT INTO order_status_updates (order_id, new_status, updated_by, update_notes)
                VALUES (?, 'pending', ?, 'Order placed - Pay on Delivery')
            ");
            $stmt->execute([$order_id, $user_id]);

            // Clear cart
            $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->execute([$user_id]);

            // Commit transaction
            $pdo->commit();

            // Redirect to order confirmation
            $_SESSION['last_order_id'] = $order_id;
            header('Location: order_confirmation.php');
            exit();

        } catch(PDOException $e) {
            // Roll back only if a transaction is active
            try { if ($pdo->inTransaction()) { $pdo->rollBack(); } } catch (Exception $__) {}
            error_log("Error creating order: " . $e->getMessage());
            // Surface the actual database error to aid debugging (remove in production)
            $errors[] = 'Failed to place order. Please try again.';
            $errors[] = 'Database error: ' . htmlspecialchars($e->getMessage());
        }
    }
}

// Get user information for pre-filling form
try {
    $stmt = $pdo->prepare("SELECT name, email, phone FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $user = ['name' => '', 'email' => '', 'phone' => ''];
}

// Ensure variables exist to avoid undefined notices on initial GET
$phone = $phone ?? '';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - My Shop</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <!-- Checkout Section -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-8">
                    <h2 class="mb-4">Checkout</h2>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>

                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Order Information</h5>

                            <form method="POST" action="" id="checkout-form">
                                <input type="hidden" name="payment_method" id="payment_method" value="paystack">
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone Number *</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" required
                                           value="<?php echo htmlspecialchars((($phone ?? '') !== '' ? ($phone ?? '') : ($user['phone'] ?? '')); ?>"
{{ ... }}

                                <!-- Payment Method Selection -->
                                <div class="mb-4">
                                    <label class="form-label fw-bold">Payment Method *</label>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input payment-method" type="radio" name="payment_method" id="paystack" value="paystack" checked>
                                        <label class="form-check-label d-flex align-items-center" for="paystack">
                                            <i class="fas fa-credit-card me-2"></i> Pay with Paystack (Card)
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input payment-method" type="radio" name="payment_method" id="cod" value="cod">
                                        <label class="form-check-label d-flex align-items-center" for="cod">
                                            <i class="fas fa-truck me-2"></i> Pay on Delivery (Cash)
                                        </label>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-success btn-lg w-100" id="submit-btn">
                                    <i class="fas fa-credit-card me-2"></i>Proceed to Payment
                                </button>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Order Summary</h5>

                            <?php if (empty($cart_items)): ?>
                                <p class="text-muted">Your cart is empty</p>
                            <?php else: ?>
                                <div class="mb-3">
                                    <?php foreach ($cart_items as $item): ?>
                                        <div class="d-flex justify-content-between mb-2">
                                            <div>
                                                <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                                                <br>
                                                <small class="text-muted">Qty: <?php echo $item['quantity']; ?></small>
                                            </div>
                                            <div class="text-end">
                                                <strong>₵<?php echo number_format($item['price'] * $item['quantity'], 2); ?></strong>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <hr>

                                <div class="d-flex justify-content-between mb-2">
                                    <strong>Subtotal:</strong>
                                    <strong>₵<?php echo number_format($subtotal, 2); ?></strong>
                                </div>

                                <div class="d-flex justify-content-between mb-2">
                                    <strong>Tax:</strong>
                                    <strong>₵<?php echo number_format($tax, 2); ?></strong>
                                </div>

                                <div class="d-flex justify-content-between mb-3">
                                    <strong>Total:</strong>
                                    <strong class="text-success">₵<?php echo number_format($total, 2); ?></strong>
                                </div>

                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Pay on Delivery:</strong> You will pay when your order is delivered.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const paymentMethods = document.querySelectorAll('.payment-method');
        const submitBtn = document.getElementById('submit-btn');
        const paymentMethodInput = document.getElementById('payment_method');
        const form = document.getElementById('checkout-form');
        
        // Update button text and hidden input when payment method changes
        paymentMethods.forEach(method => {
            method.addEventListener('change', function() {
                paymentMethodInput.value = this.value;
                if (this.value === 'cod') {
                    submitBtn.innerHTML = '<i class="fas fa-truck me-2"></i>Place Order - Pay on Delivery';
                } else {
                    submitBtn.innerHTML = '<i class="fas fa-credit-card me-2"></i>Proceed to Payment';
                }
            });
        });
        
        // Handle form submission
        form.addEventListener('submit', function(e) {
            const selectedMethod = document.querySelector('input[name="payment_method"]:checked').value;
            
            if (selectedMethod === 'paystack') {
                e.preventDefault();
                
                // Update the form action and submit method
                form.action = 'checkout_paystack.php';
                form.target = '_self';
                form.submit();
                return false;
            }
            // If COD, let the form submit normally to process_order.php
            form.action = 'process_order.php';
        });
    });
    </script>
</body>
</html>
