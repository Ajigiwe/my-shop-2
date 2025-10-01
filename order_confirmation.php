<?php
/**
 * Storefront: Order Confirmation
 * - Requires login; shows details for a single order placed by the current user
 * - Displays items, totals, addresses, and next steps
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

// Get order ID from URL
$order_id = (int)($_GET['order_id'] ?? 0);

if ($order_id <= 0) {
    header('Location: index.php');
    exit();
}

// Get order details (ensures the order belongs to the current user)
try {
    $stmt = $pdo->prepare("SELECT o.*, u.name, u.email FROM orders o 
                          JOIN users u ON o.user_id = u.user_id 
                          WHERE o.order_id = ? AND o.user_id = ?");
    $stmt->execute([$order_id, $_SESSION['user_id']]);
    $order = $stmt->fetch();
    
    if (!$order) {
        header('Location: index.php');
        exit();
    }
} catch(PDOException $e) {
    error_log("Error fetching order: " . $e->getMessage());
    header('Location: index.php');
    exit();
}

// Get order items with product name/image for summary list
$order_items = [];
try {
    $stmt = $pdo->prepare("SELECT oi.*, p.name, p.image FROM order_items oi 
                          JOIN products p ON oi.product_id = p.product_id 
                          WHERE oi.order_id = ?");
    $stmt->execute([$order_id]);
    $order_items = $stmt->fetchAll();
} catch(PDOException $e) {
    error_log("Error fetching order items: " . $e->getMessage());
}

// Set page title
$page_title = 'Order Confirmation';
?>

<?php include 'includes/header.php'; ?>

<div class="container py-5">
    <!-- Order Confirmation Header -->
    <div class="text-center mb-5">
        <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
        <h1 class="text-success">Order Confirmed!</h1>
        <p class="lead">Thank you for your order. We'll send you shipping updates at <?php echo htmlspecialchars($order['email']); ?></p>
    </div>
    
    <div class="row">
        <!-- Order Details -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h4 class="mb-0">Order Details</h4>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-sm-4"><strong>Order Number:</strong></div>
                        <div class="col-sm-8">#<?php echo str_pad($order_id, 6, '0', STR_PAD_LEFT); ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4"><strong>Order Date:</strong></div>
                        <div class="col-sm-8"><?php echo date('F j, Y \a\t g:i A', strtotime($order['order_date'])); ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4"><strong>Payment Method:</strong></div>
                        <div class="col-sm-8">
                            <?php
                            $payment_methods = [
                                'cash_on_delivery' => 'Cash on Delivery',
                                'paypal' => 'PayPal',
                                'paystack' => 'Paystack'
                            ];
                            echo htmlspecialchars($payment_methods[$order['payment_method']] ?? $order['payment_method']);
                            ?>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4"><strong>Status:</strong></div>
                        <div class="col-sm-8">
                            <span class="badge bg-<?php 
                                echo match($order['status']) {
                                    'pending' => 'warning',
                                    'processing' => 'info',
                                    'shipped' => 'primary',
                                    'delivered' => 'success',
                                    'cancelled' => 'danger',
                                    default => 'secondary'
                                };
                            ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Shipping & Billing Addresses -->
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Shipping Address</h5>
                        </div>
                        <div class="card-body">
                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Billing Address</h5>
                        </div>
                        <div class="card-body">
                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($order['billing_address'])); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Order Summary -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Order Summary</h5>
                </div>
                <div class="card-body">
                    <?php foreach ($order_items as $item): ?>
                        <div class="d-flex align-items-center mb-3">
                            <img src="assets/images/<?php echo htmlspecialchars($item['image'] ?? 'placeholder.jpg'); ?>" 
                                 class="rounded me-3" width="60" height="60" alt="<?php echo htmlspecialchars($item['name']); ?>">
                            <div class="flex-grow-1">
                                <h6 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h6>
                                <small class="text-muted">Qty: <?php echo $item['quantity']; ?> × <?php echo formatCurrency($item['price']); ?></small>
                            </div>
                            <div class="text-end">
                                <strong><?php echo formatCurrency($item['quantity'] * $item['price']); ?></strong>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal</span>
                        <span><?php echo formatCurrency($order['total_amount']); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Shipping</span>
                        <span class="text-success">Free</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-3">
                        <strong>Total</strong>
                        <strong class="text-primary"><?php echo formatCurrency($order['total_amount']); ?></strong>
                    </div>
                    
                    <?php if ($order['payment_method'] === 'cash_on_delivery'): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            You will pay <?php echo formatCurrency($order['total_amount']); ?> when your order is delivered.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Next Steps -->
            <div class="card mt-4">
                <div class="card-body text-center">
                    <h6>What's Next?</h6>
                    <p class="small text-muted mb-3">We'll process your order and send you updates via email.</p>
                    
                    <div class="d-grid gap-2">
                        <a href="user/orders.php" class="btn btn-primary">
                            <i class="fas fa-list me-2"></i>View Order History
                        </a>
                        <a href="shop.php" class="btn btn-outline-primary">
                            <i class="fas fa-shopping-bag me-2"></i>Continue Shopping
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
