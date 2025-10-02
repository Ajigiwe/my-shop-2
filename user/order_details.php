<?php
/**
 * User: Order Details
 * - Shows detailed view of a specific order for the logged-in user
 * - Displays order items, shipping/billing addresses, status timeline
 * - Allows users to track order progress and see order history
 */

// Include database connection
require_once '../includes/db.php';

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Get order ID from URL
$order_id = (int)($_GET['id'] ?? 0);
if ($order_id <= 0) {
    header('Location: orders.php');
    exit();
}

// Get order details with user info
$order = null;
try {
    $stmt = $pdo->prepare("SELECT o.*, u.name, u.email, u.phone
                          FROM orders o
                          JOIN users u ON u.user_id = o.user_id
                          WHERE o.order_id = ? AND o.user_id = ?");
    $stmt->execute([$order_id, $_SESSION['user_id']]);
    $order = $stmt->fetch();

    if (!$order) {
        header('Location: orders.php');
        exit();
    }
} catch(PDOException $e) {
    error_log("Error fetching order: " . $e->getMessage());
    $order = null;
}

// Get order items
$order_items = [];
if ($order) {
    try {
        $stmt = $pdo->prepare("SELECT oi.*, p.name, p.image, oi.product_price as price, (oi.product_price * oi.quantity) as total_price
                              FROM order_items oi
                              JOIN products p ON p.product_id = oi.product_id
                              WHERE oi.order_id = ?
                              ORDER BY oi.order_item_id");
        $stmt->execute([$order_id]);
        $order_items = $stmt->fetchAll();
    } catch(PDOException $e) {
        error_log("Error fetching order items: " . $e->getMessage());
    }
}

// Set page title
$page_title = 'Order #' . str_pad($order_id, 6, '0', STR_PAD_LEFT);
?>

<?php include '../includes/header.php'; ?>

<!-- Navbar Styles (for dropdown functionality) -->
<style>
.dropdown-container {
    position: relative;
}

.dropdown-menu-custom {
    display: none;
    position: absolute;
    top: 100%;
    left: 0;
    min-width: 200px;
    background-color: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    z-index: 1000;
    padding: 8px 0;
}

.dropdown-menu-custom.show {
    display: block;
}

.dropdown-item-custom {
    display: block;
    padding: 10px 16px;
    color: #333;
    text-decoration: none;
    transition: background-color 0.2s;
}

.dropdown-item-custom:hover {
    background-color: #f8f9fa;
    color: #007bff;
    text-decoration: none;
}

.dropdown-divider-custom {
    height: 1px;
    margin: 8px 0;
    background-color: #e9ecef;
}
</style>

<div class="container py-4">


    <?php if ($order): ?>
        <div class="row">
            <div class="col-12">
                <!-- Order Header -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h4 class="mb-0">
                                    <i class="fas fa-receipt me-2"></i>
                                    Order #<?php echo str_pad($order_id, 6, '0', STR_PAD_LEFT); ?>
                                </h4>
                            </div>
                            <div class="col-md-6 text-md-end">
                                <span class="badge fs-6 p-2 <?php
                                    echo match($order['status']) {
                                        'pending' => 'bg-warning',
                                        'processing' => 'bg-info',
                                        'shipped' => 'bg-primary',
                                        'delivered' => 'bg-success',
                                        'cancelled' => 'bg-danger',
                                        default => 'bg-secondary'
                                    };
                                ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Order Date:</strong></p>
                                <p class="text-muted"><?php echo date('F j, Y \a\t g:i A', strtotime($order['order_date'])); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Payment Method:</strong></p>
                                <p class="text-muted"><?php
                                    $payment_methods = [
                                        'cash_on_delivery' => 'Cash on Delivery',
                                        'paypal' => 'PayPal',
                                        'paystack' => 'Paystack'
                                    ];
                                    echo htmlspecialchars($payment_methods[$order['payment_method']] ?? $order['payment_method']);
                                ?></p>
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
                                <?php if (empty($order_items)): ?>
                                    <p class="text-muted text-center py-4">No items found in this order.</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-borderless">
                                            <thead>
                                                <tr>
                                                    <th>Product</th>
                                                    <th>Price</th>
                                                    <th>Quantity</th>
                                                    <th>Total</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($order_items as $item): ?>
                                                    <tr>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <img src="../assets/images/<?php echo htmlspecialchars($item['image'] ?? 'placeholder.jpg'); ?>"
                                                                     alt="<?php echo htmlspecialchars($item['name']); ?>"
                                                                     class="me-3 rounded" style="width: 60px; height: 60px; object-fit: cover;">
                                                                <div>
                                                                    <h6 class="mb-0"><?php echo htmlspecialchars($item['name']); ?></h6>
                                                                    <small class="text-muted">Order Item #<?php echo $item['order_item_id']; ?></small>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td><?php echo formatCurrency($item['price']); ?></td>
                                                        <td><?php echo $item['quantity']; ?></td>
                                                        <td><strong><?php echo formatCurrency($item['price'] * $item['quantity']); ?></strong></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                            <tfoot>
                                                <tr class="border-top">
                                                    <td colspan="3"><strong>Order Total</strong></td>
                                                    <td><strong><?php echo formatCurrency($order['total_amount']); ?></strong></td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Order Status & Addresses -->
                    <div class="col-lg-4">
                        <!-- Order Status Timeline -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Order Status</h5>
                            </div>
                            <div class="card-body">
                                <div class="status-timeline">
                                    <?php
                                    $statuses = [
                                        'pending' => ['icon' => 'fa-clock', 'label' => 'Order Placed', 'color' => 'warning'],
                                        'processing' => ['icon' => 'fa-cog', 'label' => 'Processing', 'color' => 'info'],
                                        'shipped' => ['icon' => 'fa-truck', 'label' => 'Shipped', 'color' => 'primary'],
                                        'delivered' => ['icon' => 'fa-check-circle', 'label' => 'Delivered', 'color' => 'success'],
                                        'cancelled' => ['icon' => 'fa-times-circle', 'label' => 'Cancelled', 'color' => 'danger']
                                    ];

                                    $current_status = $order['status'];
                                    $status_keys = array_keys($statuses);
                                    $current_index = array_search($current_status, $status_keys);
                                    ?>

                                    <?php foreach ($statuses as $status_key => $status_info): ?>
                                        <?php
                                        $is_active = ($status_key === $current_status);
                                        $is_completed = $current_index !== false && array_search($status_key, $status_keys) <= $current_index;
                                        ?>
                                        <div class="status-item mb-3 <?php echo $is_completed ? 'completed' : ''; ?>">
                                            <div class="d-flex align-items-center">
                                                <div class="status-icon me-3 <?php echo $is_active ? 'active' : ''; ?>">
                                                    <i class="fas <?php echo $status_info['icon']; ?> fa-lg"></i>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-0"><?php echo $status_info['label']; ?></h6>
                                                    <?php if ($is_active): ?>
                                                        <small class="text-muted">Current status</small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Shipping Address -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Shipping Address</h5>
                            </div>
                            <div class="card-body">
                                <address class="mb-0">
                                    <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?>
                                </address>
                            </div>
                        </div>

                        <!-- Billing Address -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Billing Address</h5>
                            </div>
                            <div class="card-body">
                                <address class="mb-0">
                                    <?php echo nl2br(htmlspecialchars($order['billing_address'])); ?>
                                </address>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="card mt-4">
                    <div class="card-body text-center">
                        <div class="row">
                            <div class="col-md-4">
                                <a href="orders.php" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Orders
                                </a>
                            </div>
                            <div class="col-md-4">
                                <a href="../shop.php" class="btn btn-primary w-100">
                                    <i class="fas fa-shopping-bag me-2"></i>Continue Shopping
                                </a>
                            </div>
                            <div class="col-md-4">
                                <a href="invoice.php?order_id=<?php echo $order_id; ?>" class="btn btn-outline-info w-100" target="_blank">
                                    <i class="fas fa-file-invoice me-2"></i>View Invoice
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i>
            Order not found or you don't have permission to view it.
        </div>
        <div class="text-center mt-4">
            <a href="orders.php" class="btn btn-primary">Back to My Orders</a>
        </div>
    <?php endif; ?>
</div>

<style>
.status-timeline {
    position: relative;
    padding-left: 30px;
}

.status-timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e9ecef;
}

.status-item {
    position: relative;
    margin-bottom: 1rem;
}

.status-icon {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #e9ecef;
    color: #6c757d;
    transition: all 0.3s ease;
}

.status-item.completed .status-icon {
    background: var(--success-color);
    color: white;
}

.status-item.active .status-icon {
    background: var(--primary-color);
    color: white;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
}

address {
    font-style: normal;
    line-height: 1.6;
}
</style>

<?php include '../includes/footer.php'; ?>

<!-- Navbar Dropdown JavaScript -->
<script>
function toggleCustomDropdown(event, menuId) {
    event.preventDefault();

    // Close all other dropdowns first
    document.querySelectorAll('.dropdown-menu-custom').forEach(menu => {
        if (menu.id !== menuId) {
            menu.classList.remove('show');
        }
    });

    // Toggle the clicked dropdown
    const menu = document.getElementById(menuId);
    if (menu) {
        menu.classList.toggle('show');
    }

    // Close dropdown when clicking outside
    setTimeout(() => {
        document.addEventListener('click', function closeDropdown(e) {
            if (!e.target.closest('.dropdown-container')) {
                menu.classList.remove('show');
                document.removeEventListener('click', closeDropdown);
            }
        });
    }, 100);
}
</script>
</body>
</html>
