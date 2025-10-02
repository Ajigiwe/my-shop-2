<?php
/**
 * Order Confirmation Page
 * - Shows order details after successful placement
 * - Displays order number and next steps
 */

// Include database connection and email configuration
require_once 'includes/db.php';
require_once 'includes/email_config.php';

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get order ID from URL parameter or session
if (isset($_GET['order_id'])) {
    $order_id = (int)$_GET['order_id'];
    // Store in session for page refreshes
    $_SESSION['last_order_id'] = $order_id;
} elseif (isset($_SESSION['last_order_id'])) {
    $order_id = $_SESSION['last_order_id'];
} else {
    // No order ID found, redirect to home
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'] ?? 0;

if (!$user_id) {
    header('Location: login.php');
    exit();
}

// Get order details
try {
    // First, get the basic order info with the email from the order
    $stmt = $pdo->prepare("
        SELECT o.*, u.name as customer_name, 
               COALESCE(o.email, u.email) as customer_email
        FROM orders o
        JOIN users u ON o.user_id = u.user_id
        WHERE o.order_id = ? AND o.user_id = ?
    ");
    $stmt->execute([$order_id, $user_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (empty($order)) {
        header('Location: index.php');
        exit();
    }

    // Then get the order items
    $stmt = $pdo->prepare("
        SELECT oi.*, p.name as product_name, p.price as product_price
        FROM order_items oi
        LEFT JOIN products p ON oi.product_id = p.product_id
        WHERE oi.order_id = ?
        ORDER BY oi.order_item_id
    ");
    $stmt->execute([$order_id]);
    $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Prepare order details for email
    $email_order_details = [
        'items' => [],
        'shipping' => 0, // Update this if you have shipping costs
        'tax' => $order['tax_amount'] ?? 0,
        'shipping_address' => $order['shipping_address'] ?? 'Not specified',
        'billing_address' => $order['billing_address'] ?? ($order['shipping_address'] ?? 'Not specified'),
        'payment_method' => $order['payment_method'] ?? 'Pay on Delivery',
        'order_date' => $order['order_date']
    ];
    
    // Format items for email
    foreach ($order_items as $item) {
        $email_order_details['items'][] = [
            'name' => $item['product_name'],
            'price' => $item['product_price'],
            'quantity' => $item['quantity']
        ];
    }
    
    // Send order confirmation email
    $email_sent = sendOrderConfirmationEmail(
        $order['customer_email'],
        $order['customer_name'],
        $order['order_number'],
        $email_order_details
    );
    
    // Log if email was sent or failed
    if ($email_sent) {
        error_log("Order confirmation email sent for order #" . $order['order_number']);
    } else {
        error_log("Failed to send order confirmation email for order #" . $order['order_number']);
    }
    
    // Set order items for the confirmation page
    $order_details = $order_items;
    $order = $order; // Keep the order info separate

} catch(PDOException $e) {
    error_log("Error processing order confirmation: " . $e->getMessage());
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
                                    <?php
                                        // Prefer dedicated phone column; fallback: extract from notes if present
                                        $phoneDisplay = $order['phone'] ?? '';
                                        if (empty($phoneDisplay) && !empty($order['notes'])) {
                                            if (preg_match('/Phone:\s*(.+)/i', $order['notes'], $m)) {
                                                $phoneDisplay = trim($m[1]);
                                            }
                                        }
                                    ?>
                                    <?php if (!empty($phoneDisplay)): ?>
                                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($phoneDisplay); ?></p>
                                    <?php endif; ?>
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
                                            if (isset($item['product_name']) && isset($item['quantity']) && isset($item['product_price'])) {
                                                $item_total = $item['product_price'] * $item['quantity'];
                                                $subtotal += $item_total;
                                        ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                                <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                                                <td>₵<?php echo number_format($item['product_price'], 2); ?></td>
                                                <td>₵<?php echo number_format($item_total, 2); ?></td>
                                            </tr>
                                        <?php 
                                            }
                                        endforeach; 
                                        ?>
                                    </tbody>
                                    <tfoot>
                                        <tr class="table-success">
                                            <th colspan="3">Total:</th>
                                            <th>₵<?php echo number_format($subtotal, 2); ?></th>
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
