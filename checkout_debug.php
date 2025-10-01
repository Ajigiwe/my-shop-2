<?php
/**
 * Checkout Debug - Shows exactly what's happening with form submission
 */

// Initialize session
session_start();

// Database connection
require_once 'includes/db.php';

// Authentication check
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Check cart
$cart_items = [];
$total = 0;
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM cart WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $cart_count = $stmt->fetch()['count'];

    if ($cart_count === 0) {
        header('Location: cart.php');
        exit();
    }

    // Get cart items
    $stmt = $pdo->prepare("SELECT c.*, p.name, p.price, p.image FROM cart c JOIN products p ON c.product_id = p.product_id WHERE c.user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $cart_items = $stmt->fetchAll();

    foreach ($cart_items as $item) {
        $total += $item['price'] * $item['quantity'];
    }
} catch(PDOException $e) {
    error_log("Error: " . $e->getMessage());
    $cart_items = [];
}

// Get user info
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
} catch(PDOException $e) {
    error_log("Error fetching user: " . $e->getMessage());
    $user = [];
}

// Debug information
$debug_info = [];
$debug_info[] = "REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD'];
$debug_info[] = "POST data count: " . count($_POST);
$debug_info[] = "POST keys: " . implode(', ', array_keys($_POST));
$debug_info[] = "Session user_id: " . ($_SESSION['user_id'] ?? 'NOT SET');

// Handle form submission
$errors = [];
$success = '';
$order_placed = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $debug_info[] = "=== FORM SUBMITTED ===";

    // Get form data
    $shipping_address = $_POST['shipping_address'] ?? '';
    $payment_method = $_POST['payment_method'] ?? '';

    $debug_info[] = "Form data received:";
    $debug_info[] = "- Address: $shipping_address";
    $debug_info[] = "- Payment: $payment_method";

    // Validate inputs
    if (empty($shipping_address)) {
        $errors[] = 'Shipping address is required';
    }
    if (empty($payment_method)) {
        $errors[] = 'Payment method is required';
    }

    // If no errors, process order
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Create order
            $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_amount, payment_method, shipping_address, billing_address, status)
                                 VALUES (?, ?, ?, ?, ?, 'pending')");
            $stmt->execute([$_SESSION['user_id'], $total, $payment_method, $shipping_address, $shipping_address]);

            $order_id = $pdo->lastInsertId();

            // Add order items
            $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) SELECT ?, product_id, quantity, price FROM cart WHERE user_id = ?");
            $stmt->execute([$order_id, $_SESSION['user_id']]);

            // Clear cart
            $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);

            $pdo->commit();

            $debug_info[] = "Order created successfully: $order_id";

            // Handle different payment methods
            if ($payment_method === 'cash_on_delivery') {
                $order_placed = true;
                $success = "Order placed successfully! Order ID: #$order_id";
                $debug_info[] = "Cash on delivery - showing success page";
            } else {
                $_SESSION['pending_order_id'] = $order_id;
                $_SESSION['pending_payment_method'] = $payment_method;
                $_SESSION['pending_amount'] = $total;

                $debug_info[] = "Redirecting to Paystack payment page";
                header('Location: paystack_payment.php');
                exit();
            }

        } catch(PDOException $e) {
            $pdo->rollBack();
            $errors[] = 'An error occurred while placing your order. Please try again.';
            $debug_info[] = "Database error: " . $e->getMessage();
        }
    } else {
        $debug_info[] = "Validation errors: " . implode(', ', $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout Debug - ASO Online Market</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<div class="container py-5">
    <!-- Debug Information -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">🔍 Debug Information</h5>
                </div>
                <div class="card-body">
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; font-family: monospace; font-size: 14px;">
                        <?php foreach ($debug_info as $info): ?>
                            <div><?php echo htmlspecialchars($info); ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($order_placed): ?>
        <!-- Order Success -->
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-check-circle fa-4x text-success mb-4"></i>
                        <h2 class="mb-3">Order Placed Successfully!</h2>
                        <p class="text-muted mb-4"><?php echo htmlspecialchars($success); ?></p>
                        <div class="d-flex gap-3 justify-content-center">
                            <a href="index.php" class="btn btn-primary">
                                <i class="fas fa-home me-2"></i>Back to Home
                            </a>
                            <a href="user/orders.php" class="btn btn-outline-primary">
                                <i class="fas fa-list me-2"></i>View My Orders
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="row">
            <!-- Order Summary -->
            <div class="col-lg-4">
                <div class="card sticky-top" style="top: 100px;">
                    <div class="card-header">
                        <h5 class="mb-0">Order Summary</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($cart_items as $item): ?>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="d-flex align-items-center">
                                    <img src="assets/images/<?php echo htmlspecialchars($item['image'] ?? 'placeholder.jpg'); ?>"
                                         alt="<?php echo htmlspecialchars($item['name']); ?>"
                                         style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px; margin-right: 10px;">
                                    <div>
                                        <small class="fw-bold"><?php echo htmlspecialchars($item['name']); ?></small><br>
                                        <small class="text-muted">Qty: <?php echo $item['quantity']; ?></small>
                                    </div>
                                </div>
                                <span><?php echo formatCurrency($item['price'] * $item['quantity']); ?></span>
                            </div>
                        <?php endforeach; ?>

                        <hr>
                        <div class="d-flex justify-content-between fw-bold">
                            <span>Total</span>
                            <span><?php echo formatCurrency($total); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Checkout Form -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Shipping & Payment Information</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <h6 class="mb-3">Shipping Information</h6>

                            <div class="mb-3">
                                <label for="shipping_address" class="form-label">Shipping Address *</label>
                                <textarea class="form-control" id="shipping_address" name="shipping_address"
                                          rows="3" required>Test Address</textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="payment_method" class="form-label">Payment Method *</label>
                                    <select class="form-select" id="payment_method" name="payment_method" required>
                                        <option value="">Select Payment Method</option>
                                        <option value="paystack">Paystack (Online Payment)</option>
                                        <option value="cash_on_delivery" selected>Cash on Delivery</option>
                                    </select>
                                </div>
                            </div>

                            <h6 class="mb-3">Payment Information</h6>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Paystack:</strong> You will be redirected to complete secure online payment.<br>
                                <strong>Cash on Delivery:</strong> Pay with cash when your order is delivered.
                            </div>

                            <div class="d-flex gap-3">
                                <a href="cart.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Cart
                                </a>
                                <button type="submit" name="place_order" class="btn btn-primary flex-grow-1">
                                    <i class="fas fa-credit-card me-2"></i>Place Order
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
