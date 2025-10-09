<?php
/**
 * Admin Dashboard
 * - Protects access to admins only
 * - Aggregates key store metrics (users, products, orders, revenue)
 * - Shows recent orders and quick navigation to admin modules
 */
// Include database connection
require_once '../includes/db.php';

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is admin
// Guard: only admins may access
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Set page title
$page_title = 'Admin Dashboard';

// Get dashboard statistics
$stats = [];
try {
    // Total users (customers only)
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'customer'");
    $stats['total_users'] = $stmt->fetch()['total'];
    
    // Total products
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM products");
    $stats['total_products'] = $stmt->fetch()['total'];
    
    // Total orders
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM orders");
    $stats['total_orders'] = $stmt->fetch()['total'];
    
    // Total revenue (sum of order totals)
    $stmt = $pdo->query("SELECT SUM(total_amount) as total FROM orders");
    $stats['total_revenue'] = $stmt->fetch()['total'] ?? 0;
    
    // Recent orders (limit 5)
    $stmt = $pdo->prepare("SELECT o.*, u.name FROM orders o 
                          JOIN users u ON o.user_id = u.user_id 
                          ORDER BY o.order_date DESC LIMIT 5");
    $stmt->execute();
    $recent_orders = $stmt->fetchAll();
    
    // Low stock products (threshold <= 5)
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM products WHERE stock_quantity <= 5");
    $stats['low_stock'] = $stmt->fetch()['total'];
    
    // Analytics data - Revenue by month (last 12 months)
    $stmt = $pdo->query("SELECT 
                            DATE_FORMAT(order_date, '%Y-%m') as month,
                            SUM(total_amount) as revenue,
                            COUNT(*) as orders_count
                        FROM orders 
                        WHERE order_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                        GROUP BY DATE_FORMAT(order_date, '%Y-%m')
                        ORDER BY month");
    $monthly_data = $stmt->fetchAll();
    
    // Additional analytics
    $stmt = $pdo->query("SELECT AVG(total_amount) as avg_order_value FROM orders WHERE total_amount > 0");
    $stats['avg_order_value'] = $stmt->fetch()['avg_order_value'] ?? 0;
    
    // Recent activity (orders in last 7 days)
    $stmt = $pdo->query("SELECT COUNT(*) as recent_orders FROM orders WHERE order_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $stats['recent_orders_7d'] = $stmt->fetch()['recent_orders'];
    
    // Prepare data for charts
    $months = [];
    $revenues = [];
    $orders_counts = [];
    
    foreach ($monthly_data as $data) {
        $months[] = date('M Y', strtotime($data['month'] . '-01'));
        $revenues[] = (float)$data['revenue'];
        $orders_counts[] = (int)$data['orders_count'];
    }
    
} catch(PDOException $e) {
    error_log("Error fetching dashboard stats: " . $e->getMessage());
}
?>

<?php
/**
 * Admin Dashboard
 * - Protects access to admins only
 * - Aggregates key store metrics (users, products, orders, revenue)
 * - Shows recent orders and quick navigation to admin modules
 */
// Include database connection
require_once '../includes/db.php';

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is admin
// Guard: only admins may access
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Set page title
$page_title = 'Admin Dashboard';
?>

<?php include '../includes/header.php'; ?>

<div class="container-fluid py-4">
   
    
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-2 col-lg-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Revenue</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo formatCurrency($stats['total_revenue']); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-2 col-lg-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Orders</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total_orders']; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-shopping-cart fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-2 col-lg-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Products</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total_products']; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-box fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-2 col-lg-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Total Users</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total_users']; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-2 col-lg-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Avg Order Value</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo formatCurrency($stats['avg_order_value']); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-bar fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-2 col-lg-3 col-md-6 mb-4">
            <a href="analytics.php" class="text-decoration-none">
                <div class="card border-left-info shadow h-100 py-2 analytics-card">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Analytics</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">View Charts</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    
    <div class="row">
        <!-- Recent Orders -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Recent Orders</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_orders)): ?>
                        <p class="text-muted text-center py-4">No orders found.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Order #</th>
                                        <th>Customer</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Total</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_orders as $order): ?>
                                        <tr>
                                            <td>#<?php echo str_pad($order['order_id'], 6, '0', STR_PAD_LEFT); ?></td>
                                            <td><?php echo htmlspecialchars($order['name']); ?></td>
                                            <td><?php echo date('M j, Y', strtotime($order['order_date'])); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    $status = $order['order_status'] ?? 'pending';
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
                                            <td><?php echo formatCurrency($order['total_amount']); ?></td>
                                            <td>
                                                <a href="order_details.php?id=<?php echo $order['order_id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary">View</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-center mt-3">
                            <a href="manage_orders.php" class="btn btn-primary">View All Orders</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions & Alerts -->
        <div class="col-lg-4">
            <!-- Quick Actions -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="manage_products.php" class="btn btn-primary">
                            <i class="fas fa-box me-2"></i>Manage Products
                        </a>
                        <a href="manage_categories.php" class="btn btn-success">
                            <i class="fas fa-tags me-2"></i>Manage Categories
                        </a>
                        <a href="manage_subcategories.php" class="btn btn-success">
                            <i class="fas fa-tag me-2"></i>Manage Subcategories
                        </a>
                        <a href="manage_orders.php" class="btn btn-info">
                            <i class="fas fa-shopping-cart me-2"></i>Manage Orders
                        </a>
                        <a href="manage_users.php" class="btn btn-warning">
                            <i class="fas fa-users me-2"></i>Manage Users
                        </a>
                        <a href="analytics.php" class="btn btn-info">
                            <i class="fas fa-chart-line me-2"></i>View Analytics
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Alerts -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Alerts</h5>
                </div>
                <div class="card-body">
                    <?php if ($stats['low_stock'] > 0): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong><?php echo $stats['low_stock']; ?> products</strong> have low stock (≤5 items)
                        </div>
                    <?php endif; ?>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Dashboard shows overview of your store
                    </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
