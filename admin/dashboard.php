<?php
/**
 * Modern Admin Dashboard
 * - Refined metrics and activity feed
 */
require_once '../includes/db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$page_title = 'Overview';

// Get statistics
$stats = [];
try {
    $stats['total_users'] = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();
    $stats['total_products'] = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $stats['total_orders'] = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    $stats['total_revenue'] = $pdo->query("SELECT SUM(total_amount) FROM orders")->fetchColumn() ?? 0;
    $stats['low_stock'] = $pdo->query("SELECT COUNT(*) FROM products WHERE stock_quantity <= 5")->fetchColumn();
    
    // Recent orders
    $recent_orders = $pdo->query("SELECT o.*, u.name FROM orders o JOIN users u ON o.user_id = u.user_id ORDER BY o.order_date DESC LIMIT 6")->fetchAll();
} catch(PDOException $e) {
    error_log("Dashboard stats error: " . $e->getMessage());
}

include 'includes/header-new.php';
?>

<div class="row g-4 mb-5">
    <!-- Stat Cards -->
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-icon text-[#1A1A1A]">
                <i class="material-symbols-outlined">payments</i>
            </div>
            <div class="stat-label">Total Revenue</div>
            <div class="stat-value"><?php echo formatCurrency($stats['total_revenue']); ?></div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-icon text-success">
                <i class="material-symbols-outlined">shopping_bag</i>
            </div>
            <div class="stat-label">Total Orders</div>
            <div class="stat-value"><?php echo number_format($stats['total_orders']); ?></div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-icon text-info">
                <i class="material-symbols-outlined">inventory_2</i>
            </div>
            <div class="stat-label">Products</div>
            <div class="stat-value"><?php echo number_format($stats['total_products']); ?></div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-icon text-warning">
                <i class="material-symbols-outlined">group</i>
            </div>
            <div class="stat-label">Customers</div>
            <div class="stat-value"><?php echo number_format($stats['total_users']); ?></div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Recent Orders Table -->
    <div class="col-xl-8">
        <div class="admin-card">
            <div class="admin-card-header">
                <h5 class="admin-card-title mb-0">Recent Transactions</h5>
                <a href="manage_orders.php" class="btn-premium-outline btn-sm text-decoration-none">View All</a>
            </div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_orders as $order): ?>
                        <tr>
                            <td class="fw-bold">#<?php echo str_pad($order['order_id'], 5, '0', STR_PAD_LEFT); ?></td>
                            <td><?php echo htmlspecialchars($order['name']); ?></td>
                            <td class="fw-black"><?php echo formatCurrency($order['total_amount']); ?></td>
                            <td>
                                <span class="badge rounded-pill px-3 py-2 bg-<?php 
                                    echo match($order['order_status']) {
                                        'pending' => 'warning',
                                        'delivered' => 'success',
                                        'cancelled' => 'danger',
                                        default => 'primary'
                                    };
                                ?>-subtle text-<?php 
                                    echo match($order['order_status']) {
                                        'pending' => 'warning',
                                        'delivered' => 'success',
                                        'cancelled' => 'danger',
                                        default => 'primary'
                                    };
                                ?>">
                                    <?php echo ucfirst($order['order_status']); ?>
                                </span>
                            </td>
                            <td class="text-muted"><?php echo date('M j, H:i', strtotime($order['order_date'])); ?></td>
                            <td class="text-end">
                                <a href="order_details.php?id=<?php echo $order['order_id']; ?>" class="btn-premium-outline px-3 py-1 text-[12px] text-decoration-none">Details</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="col-xl-4">
        <div class="admin-card mb-4">
            <div class="admin-card-header">
                <h5 class="admin-card-title mb-0">Quick Management</h5>
            </div>
            <div class="card-body p-4">
                <div class="d-flex flex-column gap-3">
                    <a href="manage_products.php" class="btn-premium w-100 text-center text-decoration-none py-3 d-flex align-items-center justify-content-center gap-2">
                        <i class="material-symbols-outlined text-[20px]">add_box</i> Add Product
                    </a>
                    <a href="manage_hero.php" class="btn-premium-outline w-100 text-center text-decoration-none py-3 d-flex align-items-center justify-content-center gap-2">
                        <i class="material-symbols-outlined text-[20px]">view_carousel</i> Edit Slider
                    </a>
                    <a href="manage_users.php" class="btn-premium-outline w-100 text-center text-decoration-none py-3 d-flex align-items-center justify-content-center gap-2">
                        <i class="material-symbols-outlined text-[20px]">group</i> User Control
                    </a>
                </div>
            </div>
        </div>

        <?php if ($stats['low_stock'] > 0): ?>
        <div class="admin-card bg-danger-subtle border-danger/20">
            <div class="card-body p-4 text-center">
                <div class="text-danger mb-2">
                    <i class="material-symbols-outlined text-[48px]">warning</i>
                </div>
                <h6 class="fw-black text-danger text-uppercase mb-1 tracking-widest small">Critical Stock Alert</h6>
                <p class="text-danger/70 small mb-3">You have <strong><?php echo $stats['low_stock']; ?></strong> products running low on inventory.</p>
                <a href="manage_products.php?filter=low_stock" class="btn btn-danger rounded-pill px-4 btn-sm fw-bold">Restock Now</a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer-new.php'; ?>
