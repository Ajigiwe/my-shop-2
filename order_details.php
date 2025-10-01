<?php
// Order Details Page - Phone extracted from shipping address
require_once 'includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Debug session information
error_log('=== ORDER DETAILS DEBUG ===');
error_log('Session ID: ' . session_id());
error_log('Session status: ' . session_status());
error_log('User ID in session: ' . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NOT SET'));
error_log('Request URI: ' . $_SERVER['REQUEST_URI']);
error_log('GET params: ' . print_r($_GET, true));

// Check if user is logged in
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    error_log('SESSION ISSUE: No user_id found - redirecting to login');
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$order_id = (int)($_GET['order_id'] ?? 0);

error_log('User ID: ' . $user_id);
error_log('Order ID: ' . $order_id);

if ($order_id <= 0) {
    error_log('Invalid order ID - redirecting to user orders');
    header('Location: user/orders.php');
    exit();
}

// Get order details with better error handling
try {
    error_log('Querying database for order: ' . $order_id . ' by user: ' . $user_id);

    // First check if order exists at all
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE order_id = ?");
    $stmt->execute([$order_id]);
    $order_exists = $stmt->fetch()['count'];

    error_log('Order exists check: ' . $order_exists);

    if ($order_exists === 0) {
        error_log('Order does not exist - redirecting to user orders');
        header('Location: user/orders.php');
        exit();
    }

    // Now check if order belongs to current user
    $stmt = $pdo->prepare("
        SELECT o.*, u.first_name, u.last_name, u.email
        FROM orders o
        JOIN users u ON o.user_id = u.user_id
        WHERE o.order_id = ? AND o.user_id = ?
    ");
    $stmt->execute([$order_id, $user_id]);
    $order = $stmt->fetch();

    error_log('Order belongs to user check: ' . ($order ? 'YES' : 'NO'));

    if (!$order) {
        error_log('Order does not belong to current user - redirecting to user orders');
        header('Location: user/orders.php');
        exit();
    }

    // Get order items
    $stmt = $pdo->prepare("
        SELECT oi.*, p.name, p.image
        FROM order_items oi
        JOIN products p ON oi.product_id = p.product_id
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$order_id]);
    $order_items = $stmt->fetchAll();

    error_log('Order items found: ' . count($order_items));

    // Calculate subtotal from order items
    $subtotal = 0;
    foreach ($order_items as $item) {
        $subtotal += $item['price'] * $item['quantity'];
    }

    // Extract phone number and clean address from shipping_address
    $phone_number = 'Not provided';
    $clean_shipping_address = $order['shipping_address'];

    if (!empty($order['shipping_address'])) {
        // Look for phone number in shipping address (format: "Address\n\nPhone: +233...")
        if (preg_match('/Phone:\s*([^\n\r]+)/i', $order['shipping_address'], $matches)) {
            $phone_number = trim($matches[1]);
            // Remove phone number from shipping address for display
            $clean_shipping_address = preg_replace('/\s*Phone:\s*[^\n\r]*/i', '', $order['shipping_address']);
            $clean_shipping_address = trim($clean_shipping_address);
        }
    }

    error_log('Order details loaded successfully');

} catch(PDOException $e) {
    error_log("DATABASE ERROR: " . $e->getMessage());
    error_log('Error code: ' . $e->getCode());
    header('Location: user/orders.php');
    exit();
}

// Update page title
$page_title = "Order #" . str_pad($order_id, 6, '0', STR_PAD_LEFT);

include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <!-- Order Header -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h2 class="mb-1">Order #<?php echo str_pad($order_id, 6, '0', STR_PAD_LEFT); ?></h2>
                            <p class="text-muted mb-0">Placed on <?php echo date('F j, Y \a\t g:i A', strtotime($order['created_at'])); ?></p>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <span class="badge fs-6 <?php
                                echo match($order['status']) {
                                    'pending' => 'bg-warning',
                                    'paid' => 'bg-success',
                                    'shipped' => 'bg-info',
                                    'delivered' => 'bg-success',
                                    'cancelled' => 'bg-danger',
                                    default => 'bg-secondary'
                                };
                            ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                            <div class="mt-2">
                                <strong class="text-success" style="font-size: 1.3em;"><?php echo formatCurrency($order['total_amount']); ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Order Items -->
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Order Items</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($order_items)): ?>
                                <?php foreach ($order_items as $item): ?>
                                    <div class="d-flex align-items-center mb-4">
                                        <img src="assets/images/<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" style="width: 80px; height: 80px; object-fit: cover;" class="me-4">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h6>
                                            <p class="text-muted mb-1">Quantity: <?php echo $item['quantity']; ?></p>
                                            <p class="text-muted mb-0">Unit Price: <?php echo formatCurrency($item['price']); ?></p>
                                        </div>
                                        <div class="text-end">
                                            <strong><?php echo formatCurrency($item['price'] * $item['quantity']); ?></strong>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted">No items found in this order.</p>
                            <?php endif; ?>

                            <hr>

                            <div class="row">
                                <div class="col-sm-6">
                                    <h6>Payment Method</h6>
                                    <p class="text-muted mb-0"><?php echo ucfirst(str_replace('_', ' ', $order['payment_method'])); ?></p>
                                </div>
                                <?php if ($order['payment_reference']): ?>
                                    <div class="col-sm-6">
                                        <h6>Payment Reference</h6>
                                        <p class="text-muted mb-0" style="font-family: monospace; font-size: 0.9em;"><?php echo htmlspecialchars($order['payment_reference']); ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Summary & Contact Info -->
                <div class="col-lg-4">
                    <!-- Contact Information -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-address-book me-2"></i>Contact Information
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <h6>Phone Number</h6>
                                <p class="mb-0">
                                    <i class="fas fa-phone me-2 text-muted"></i>
                                    <?php echo htmlspecialchars($phone_number); ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Order Summary -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Order Summary</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal</span>
                                <span><?php echo formatCurrency($subtotal); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Shipping</span>
                                <span class="text-success">Free</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Tax</span>
                                <span>Included</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between">
                                <strong>Total</strong>
                                <strong class="text-success" style="font-size: 1.2em;"><?php echo formatCurrency($order['total_amount']); ?></strong>
                            </div>
                        </div>
                    </div>

                    <!-- Shipping Address -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Shipping Address</h5>
                        </div>
                        <div class="card-body">
                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($clean_shipping_address)); ?></p>
                        </div>
                    </div>

                    <!-- Billing Address -->
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

            <!-- Action Buttons -->
            <div class="card mt-4">
                <div class="card-body text-center">
                    <a href="user/orders.php" class="btn btn-primary me-3">
                        <i class="fas fa-list me-2"></i>View All Orders
                    </a>
                    <a href="shop.php" class="btn btn-outline-primary">
                        <i class="fas fa-shopping-bag me-2"></i>Continue Shopping
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
