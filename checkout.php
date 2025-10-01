<?php
/**
 * Checkout Page - Pay on Delivery
 * - Handles order placement for Pay on Delivery payment method
 * - Creates order record and order items
 * - Redirects to order confirmation
 */

// Include database connection
require_once 'includes/db.php';

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

// Set page title
$page_title = 'Checkout - Pay on Delivery';

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

$tax = $subtotal * 0.1; // 10% tax
$total = $subtotal + $tax;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $shipping_address = sanitizeInput($_POST['shipping_address'] ?? '');
    $billing_address = sanitizeInput($_POST['billing_address'] ?? $shipping_address);
    $order_notes = sanitizeInput($_POST['order_notes'] ?? '');

    // Validate required fields
    $errors = [];
    if (empty($phone)) {
        $errors[] = 'Phone number is required';
    }
    if (empty($shipping_address)) {
        $errors[] = 'Shipping address is required';
    }
    if (empty($cart_items)) {
        $errors[] = 'Your cart is empty';
    }

    if (empty($errors)) {
        try {
            // Generate unique order number
            $order_number = 'POD' . date('Ymd') . rand(1000, 9999);

            // Begin transaction
            $pdo->beginTransaction();

            // Insert order (align with actual schema: notes, tax_amount, status enum)
            // Combine phone into notes so it appears on invoice
            $notes_to_save = 'Phone: ' . $phone;
            if (!empty($order_notes)) {
                $notes_to_save .= "\n" . $order_notes;
            }

            $stmt = $pdo->prepare("
                INSERT INTO orders (
                    user_id,
                    order_number,
                    total_amount,
                    tax_amount,
                    payment_method,
                    status,
                    shipping_address,
                    billing_address,
                    notes
                ) VALUES (
                    ?, ?, ?, ?, 'Pay on Delivery', 'pending', ?, ?, ?
                )
            ");
            $stmt->execute([
                $user_id,
                $order_number,
                $total,
                $tax,
                $shipping_address,
                $billing_address,
                $notes_to_save
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
    $stmt = $pdo->prepare("SELECT name, email FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $user = ['name' => '', 'email' => ''];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - My Shop</title>
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
                    <h2 class="mb-4">Checkout - Pay on Delivery</h2>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Order Information</h5>

                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="shipping_address" class="form-label">Shipping Address *</label>
                                    <textarea class="form-control" id="shipping_address" name="shipping_address" rows="3" required><?php echo htmlspecialchars($shipping_address ?? ''); ?></textarea>
                                </div>

                                <div class="mb-3">
                                    <label for="billing_address" class="form-label">Billing Address</label>
                                    <textarea class="form-control" id="billing_address" name="billing_address" rows="3"><?php echo htmlspecialchars($billing_address ?? ''); ?></textarea>
                                    <div class="form-text">Leave blank to use shipping address</div>
                                </div>

                                <div class="mb-3">
                                    <label for="order_notes" class="form-label">Order Notes</label>
                                    <textarea class="form-control" id="order_notes" name="order_notes" rows="3" placeholder="Any special delivery instructions..."><?php echo htmlspecialchars($order_notes ?? ''); ?></textarea>
                                </div>

                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="fas fa-truck me-2"></i>Place Order - Pay on Delivery
                                </button>
                            </form>
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
                                    <strong>Tax (10%):</strong>
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
</body>
</html>
