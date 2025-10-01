<?php
/**
 * Order Confirmation Page
 * - Shows order details after successful placement
 * - Displays order number and next steps
 */

// Include database connection
require_once 'includes/db.php';

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Check if we have a recent order
if (!isset($_SESSION['last_order_id'])) {
    header('Location: index.php');
    exit();
}

$order_id = $_SESSION['last_order_id'];
$user_id = $_SESSION['user_id'];

// Get order details
try {
    $stmt = $pdo->prepare("
        SELECT o.*, oi.*, p.name as product_name
        FROM orders o
        LEFT JOIN order_items oi ON o.order_id = oi.order_id
        LEFT JOIN products p ON oi.product_id = p.product_id
        WHERE o.order_id = ? AND o.user_id = ?
        ORDER BY oi.order_item_id
    ");
    $stmt->execute([$order_id, $user_id]);
    $order_details = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($order_details)) {
        header('Location: index.php');
        exit();
    }

    $order = $order_details[0];

} catch(PDOException $e) {
    error_log("Error fetching order details: " . $e->getMessage());
    $order_details = [];
    $order = [];
}

// Clear the session order ID after use
unset($_SESSION['last_order_id']);

// Set page title
$page_title = 'Order Confirmation';
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

    <!-- Order Confirmation Section -->
    <section class="py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="text-center mb-4">
                        <i class="fas fa-check-circle text-success fa-4x mb-3"></i>
                        <h2>Order Placed Successfully!</h2>
                        <p class="text-muted">Thank you for your order. We will deliver it soon.</p>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h5>Order Information</h5>
                                    <p><strong>Order Number:</strong> <?php echo htmlspecialchars($order['order_number']); ?></p>
                                    <p><strong>Order Date:</strong> <?php echo date('M d, Y \a\t g:i A', strtotime($order['order_date'])); ?></p>
                                    <p><strong>Payment Method:</strong> <?php echo htmlspecialchars($order['payment_method']); ?></p>
                                    <p><strong>Order Status:</strong>
                                        <span class="badge bg-warning"><?php echo htmlspecialchars($order['status'] ?? ($order['order_status'] ?? 'pending')); ?></span>
                                    </p>
                                </div>

                                <div class="col-md-6">
                                    <h5>Delivery Information</h5>
                                    <p><strong>Shipping Address:</strong></p>
                                    <p class="text-muted"><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
                                    <?php if (!empty(($order['notes'] ?? null) ?: ($order['order_notes'] ?? null))): ?>
                                        <p><strong>Order Notes:</strong></p>
                                        <p class="text-muted"><?php echo nl2br(htmlspecialchars($order['notes'] ?? $order['order_notes'])); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <hr>

                            <h5>Order Items</h5>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Quantity</th>
                                            <th>Price</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $subtotal = 0;
                                        foreach ($order_details as $item):
                                            $item_total = $item['product_price'] * $item['quantity'];
                                            $subtotal += $item_total;
                                        ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                                <td><?php echo $item['quantity']; ?></td>
                                                <td>₵<?php echo number_format($item['product_price'], 2); ?></td>
                                                <td>₵<?php echo number_format($item_total, 2); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th colspan="3">Subtotal:</th>
                                            <th>₵<?php echo number_format($subtotal, 2); ?></th>
                                        </tr>
                                        <tr>
                                            <th colspan="3">Tax (10%):</th>
                                            <th>₵<?php echo number_format($subtotal * 0.1, 2); ?></th>
                                        </tr>
                                        <tr class="table-success">
                                            <th colspan="3">Total:</th>
                                            <th>₵<?php echo number_format($order['total_amount'], 2); ?></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            <div class="alert alert-info mt-4">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Next Steps:</strong>
                                <ul class="mb-0 mt-2">
                                    <li>You will receive a confirmation call from our delivery team</li>
                                    <li>Please ensure someone is available at the delivery address</li>
                                    <li>Payment will be collected upon delivery</li>
                                    <li>You can track your order status in your account</li>
                                </ul>
                            </div>

                            <div class="text-center mt-4">
                                <a href="index.php" class="btn btn-primary me-2">
                                    <i class="fas fa-home me-2"></i>Continue Shopping
                                </a>
                                <a href="user/orders.php" class="btn btn-outline-primary">
                                    <i class="fas fa-list me-2"></i>View My Orders
                                </a>
                            </div>
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
