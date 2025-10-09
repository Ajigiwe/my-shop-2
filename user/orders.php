: Undefined array key "status" in
C:\xampp\htdocs\My Shop2\admin\order_details.php
on line
458<?php
/**
 * User: My Orders
 * - Requires login; shows the authenticated user's orders with counts and totals
 * - Provides quick links to view details and continue shopping
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

// Set page title
$page_title = 'My Orders';

// Get user's orders (with item count per order)
$orders = [];
$debug_info = [];

try {
    // First, let's check if the user has any orders at all
    $stmt = $pdo->prepare("SELECT COUNT(*) as order_count FROM orders WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $order_count = $stmt->fetch()['order_count'];
    $debug_info['total_orders'] = $order_count;
    
    // Now get the detailed orders
    $stmt = $pdo->prepare("SELECT o.*, COUNT(oi.order_item_id) as item_count 
                          FROM orders o 
                          LEFT JOIN order_items oi ON o.order_id = oi.order_id 
                          WHERE o.user_id = ? 
                          GROUP BY o.order_id 
                          ORDER BY o.order_date DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $orders = $stmt->fetchAll();
    $debug_info['fetched_orders'] = count($orders);
    
    // Debug: Log the first order if any
    if (!empty($orders)) {
        $debug_info['first_order'] = $orders[0];
    }
    
} catch(PDOException $e) {
    error_log("Error fetching orders: " . $e->getMessage());
    $debug_info['error'] = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) : 'My Orders'; ?> - ASO Online Market</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">

    <!-- Custom Navbar Styles -->
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
</head>
<body>
<?php include '../includes/navbar.php'; ?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>My Orders (<?php echo count($orders); ?>)</h2>
                <a href="../shop.php" class="btn btn-primary">
                    <i class="fas fa-shopping-bag me-2"></i>Continue Shopping
                </a>
            </div>
            
            <?php if (!empty($debug_info)): ?>
                <div class="alert alert-info">
                    <h6>Debug Information:</h6>
                    <p><strong>User ID:</strong> <?php echo $_SESSION['user_id']; ?></p>
                    <p><strong>Total Orders in DB:</strong> <?php echo $debug_info['total_orders'] ?? 'Unknown'; ?></p>
                    <p><strong>Fetched Orders:</strong> <?php echo $debug_info['fetched_orders'] ?? 'Unknown'; ?></p>
                    <?php if (isset($debug_info['error'])): ?>
                        <p><strong>Error:</strong> <?php echo htmlspecialchars($debug_info['error']); ?></p>
                    <?php endif; ?>
                    <?php if (isset($debug_info['first_order'])): ?>
                        <p><strong>First Order Sample:</strong></p>
                        <pre><?php print_r($debug_info['first_order']); ?></pre>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php if (empty($orders)): ?>
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-shopping-bag fa-3x text-muted mb-3"></i>
                        <h5>No Orders Found</h5>
                        <p class="text-muted">You haven't placed any orders yet. Start shopping to see your orders here!</p>
                        <a href="../shop.php" class="btn btn-primary">Start Shopping</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Order #</th>
                                        <th>Date</th>
                                        <th>Items</th>
                                        <th>Status</th>
                                        <th>Total</th>
                                        <th>Payment</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td>
                                                <strong>#<?php echo str_pad($order['order_id'], 6, '0', STR_PAD_LEFT); ?></strong>
                                            </td>
                                            <td><?php echo date('M j, Y g:i A', strtotime($order['order_date'])); ?></td>
                                            <td><?php echo $order['item_count']; ?> items</td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    $status = $order['status'] ?? $order['order_status'] ?? 'pending';
                                                    echo match($status) {
                                                        'pending' => 'warning',
                                                        'processing' => 'info',
                                                        'shipped' => 'primary',
                                                        'delivered' => 'success',
                                                        'cancelled' => 'danger',
                                                        default => 'secondary'
                                                    };
                                                ?>">
                                                    <?php echo ucfirst($status); ?>
                                                </span>
                                            </td>
                                            <td><?php echo '₵' . number_format($order['total_amount'], 2); ?></td>
                                            <td>
                                                <?php
                                                $payment_methods = [
                                                    'cash_on_delivery' => 'COD',
                                                    'paypal' => 'PayPal',
                                                ];
                                                $payment_method = $order['payment_method'] ?? 'cash_on_delivery';
                                                echo htmlspecialchars($payment_methods[$payment_method] ?? $payment_method);
                                                ?>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="order_details.php?id=<?php echo $order['order_id']; ?>"
                                                       class="btn btn-sm btn-outline-primary">View</a>
                                                    <a href="invoice.php?order_id=<?php echo $order['order_id']; ?>"
                                                       class="btn btn-sm btn-outline-info" target="_blank">Invoice</a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                    </div>
                </div>
                
                <!-- Order Status Legend -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">Order Status Legend</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 mb-2">
                                <span class="badge bg-warning me-2">Pending</span>
                                <small>Order received, processing soon</small>
                            </div>
                            <div class="col-md-3 mb-2">
                                <span class="badge bg-info me-2">Processing</span>
                                <small>Order is being prepared</small>
                            </div>
                            <div class="col-md-3 mb-2">
                                <span class="badge bg-primary me-2">Shipped</span>
                                <small>Order is on the way</small>
                            </div>
                            <div class="col-md-3 mb-2">
                                <span class="badge bg-success me-2">Delivered</span>
                                <small>Order has been delivered</small>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<!-- Bootstrap JS (for any interactive elements) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

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
