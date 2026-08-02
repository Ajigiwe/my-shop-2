<?php
/**
 * Admin Dashboard — Avazonia style (Performance Insights)
 * Port of Avazonia admin/index.php layout wired to ASO queries.
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
    $stats['low_stock'] = $pdo->query("SELECT COUNT(*) FROM products WHERE stock_quantity > 0 AND stock_quantity <= COALESCE(low_stock_threshold, 5)")->fetchColumn();
    
    // Recent orders
    $recent_orders = $pdo->query("SELECT o.*, u.name FROM orders o JOIN users u ON o.user_id = u.user_id ORDER BY o.order_date DESC LIMIT 6")->fetchAll();
} catch(PDOException $e) {
    error_log("Dashboard stats error: " . $e->getMessage());
}

// Revenue growth (this month vs last month)
$cur_month_rev = 0;
$last_month_rev = 0;
try {
    $cur_month_rev = (float)$pdo->query("SELECT IFNULL(SUM(total_amount),0) FROM orders WHERE order_status NOT IN ('cancelled','failed','refunded') AND order_date >= DATE_FORMAT(NOW(), '%Y-%m-01')")->fetchColumn();
    $last_month_rev = (float)$pdo->query("SELECT IFNULL(SUM(total_amount),0) FROM orders WHERE order_status NOT IN ('cancelled','failed','refunded') AND order_date >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH), '%Y-%m-01') AND order_date < DATE_FORMAT(NOW(), '%Y-%m-01')")->fetchColumn();
} catch (PDOException $e) {
    error_log("Dashboard growth error: " . $e->getMessage());
}
$rev_growth = $last_month_rev > 0 ? (($cur_month_rev - $last_month_rev) / $last_month_rev) * 100 : 0;

// Order growth MoM
$cur_month_orders = 0;
$last_month_orders = 0;
try {
    $cur_month_orders = (float)$pdo->query("SELECT COUNT(*) FROM orders WHERE order_date >= DATE_FORMAT(NOW(), '%Y-%m-01')")->fetchColumn();
    $last_month_orders = (float)$pdo->query("SELECT COUNT(*) FROM orders WHERE order_date >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH), '%Y-%m-01') AND order_date < DATE_FORMAT(NOW(), '%Y-%m-01')")->fetchColumn();
} catch (PDOException $e) {
    error_log("Dashboard order growth error: " . $e->getMessage());
}
$order_growth = $last_month_orders > 0 ? (($cur_month_orders - $last_month_orders) / $last_month_orders) * 100 : 0;

// AOV
$aov = $stats['total_orders'] > 0 ? $stats['total_revenue'] / $stats['total_orders'] : 0;

// Revenue Trends chart data (last 14 days)
$revenue_dates = [];
$revenue_totals = [];
try {
    $revenue_rows = $pdo->query("SELECT DATE(order_date) d, IFNULL(SUM(total_amount),0) total FROM orders WHERE order_status NOT IN ('cancelled','failed','refunded') AND order_date >= DATE_SUB(CURDATE(), INTERVAL 13 DAY) GROUP BY d")->fetchAll();
    $revenue_map = [];
    foreach ($revenue_rows as $row) {
        $revenue_map[$row['d']] = (float)$row['total'];
    }
    for ($i = 13; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-$i days"));
        $revenue_dates[] = date('M j', strtotime($d));
        $revenue_totals[] = $revenue_map[$d] ?? 0;
    }
} catch (PDOException $e) {
    error_log("Dashboard revenue chart error: " . $e->getMessage());
}

// Sales by Category chart data
$category_labels = [];
$category_totals = [];
try {
    $category_rows = $pdo->query("SELECT c.category_name, SUM(oi.price * oi.quantity) total FROM order_items oi JOIN products p ON p.product_id = oi.product_id JOIN categories c ON c.category_id = p.category_id JOIN orders o ON o.order_id = oi.order_id WHERE o.order_status NOT IN ('cancelled','failed','refunded') GROUP BY c.category_name ORDER BY total DESC LIMIT 5")->fetchAll();
    foreach ($category_rows as $row) {
        $category_labels[] = $row['category_name'];
        $category_totals[] = (float)$row['total'];
    }
} catch (PDOException $e) {
    error_log("Dashboard category chart error: " . $e->getMessage());
}

// Monthly Revenue Goal
$monthly_goal = 50000;
$monthly_revenue = 0;
try {
    $stmt = $pdo->query("SELECT setting_value FROM site_settings WHERE setting_key = 'monthly_revenue_goal'");
    $goal_val = $stmt->fetchColumn();
    if ($goal_val !== false && is_numeric($goal_val)) {
        $monthly_goal = (float)$goal_val;
    }
    $monthly_revenue = (float)$pdo->query("SELECT IFNULL(SUM(total_amount),0) FROM orders WHERE order_status NOT IN ('cancelled','failed','refunded') AND MONTH(order_date)=MONTH(NOW()) AND YEAR(order_date)=YEAR(NOW())")->fetchColumn();
} catch (PDOException $e) {
    error_log("Dashboard monthly goal error: " . $e->getMessage());
}
$goal_pct = $monthly_goal > 0 ? (int)min(100, round(($monthly_revenue / $monthly_goal) * 100)) : 0;

include 'includes/avazonia_header.php';
?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="admin-header" style="margin-bottom: 48px;">
    <div style="display: flex; flex-direction: column; gap: 8px;">
        <h1 style="font-size: clamp(38px, 8vw, 64px); line-height: 0.9; margin: 0; letter-spacing: -0.04em;">Performance<br>Insights</h1>
        <div style="font-family: var(--f-mono); font-size: 11px; color: var(--mid-gray); margin-top: 12px;">Unified Intelligence Engine • Active Tracking</div>
    </div>
</div>

<div class="analytics-grid">
    <!-- STAT 01: REVENUE -->
    <div class="stat-card-bold">
        <span class="label">Total Revenue</span>
        <span class="value"><?php echo formatCurrency($stats['total_revenue']); ?></span>
        <div class="trend-indicator <?php echo $rev_growth >= 0 ? 'trend-up' : 'trend-down'; ?>">
            <?php echo $rev_growth >= 0 ? '▲' : '▼'; ?> <?php echo abs(round($rev_growth, 1)); ?>%
            <span style="opacity: 0.5; color: var(--ink);">vs last month</span>
        </div>
    </div>

    <!-- STAT 02: ORDERS -->
    <div class="stat-card-bold">
        <span class="label">Total Orders</span>
        <span class="value"><?php echo number_format($stats['total_orders']); ?></span>
        <div class="trend-indicator <?php echo $order_growth >= 0 ? 'trend-up' : 'trend-down'; ?>">
            <?php echo $order_growth >= 0 ? '▲' : '▼'; ?> <?php echo abs(round($order_growth, 1)); ?>%
            <span style="opacity: 0.5; color: var(--ink);">MoM Velocity</span>
        </div>
    </div>

    <!-- STAT 03: AVERAGE BASKET -->
    <div class="stat-card-bold">
        <span class="label">Avg Order Value (AOV)</span>
        <span class="value"><?php echo formatCurrency($aov); ?></span>
        <div style="font-family: var(--f-mono); font-size: 10px; color: var(--mid-gray);">BASKET EFFICIENCY</div>
    </div>

    <!-- STAT 04: GROWTH TARGET -->
    <div class="stat-card-bold" style="background: var(--ink); color: #fff; border: none;">
        <span class="label" style="color: rgba(255,255,255,0.6);">Monthly Revenue Goal</span>
        <span class="value" style="font-size: 40px;"><?php echo $goal_pct; ?>%</span>
        <div style="height: 6px; background: rgba(255,255,255,0.1); margin-top: 12px; border-radius: 0; overflow: hidden;">
            <div style="width: <?php echo $goal_pct; ?>%; height: 100%; background: #00a854;"></div>
        </div>
        <div style="font-family: var(--f-mono); font-size: 10px; color: rgba(255,255,255,0.6); margin-top: 8px;">
            <?php echo formatCurrency($monthly_revenue); ?> of <?php echo formatCurrency($monthly_goal); ?> • <?php echo $goal_pct >= 100 ? 'Goal reached!' : 'On track'; ?>
        </div>
    </div>
</div>

<div style="margin-bottom: 40px;">
    <div class="panel">
        <div class="panel-header">
            <div class="panel-title">Revenue Trends (Last 14 Days)</div>
        </div>
        <div style="padding: 32px; height: 350px;">
            <canvas id="revenueTrendChart"></canvas>
        </div>
    </div>
</div>

<div class="dashboard-layout">
    <div style="display: flex; flex-direction: column; gap: 40px; min-width: 0;">
        <!-- CATEGORY SALES -->
        <div class="panel">
            <div class="panel-header">
                <div class="panel-title">Sales by Category</div>
            </div>
            <div style="padding: 40px; display: flex; justify-content: center; align-items: center; min-height: 400px;">
                <div style="width: 100%; max-width: 350px;">
                    <canvas id="categorySalesChart"></canvas>
                </div>
            </div>
        </div>

        <!-- RECENT ACTIVITY -->
        <div class="panel">
            <div class="panel-header">
                <div class="panel-title">Recent Transactions</div>
                <a href="manage_orders.php" class="nav-link" style="font-size: 10px; color: var(--red);">Full Ledger →</a>
            </div>
            <div class="table-container">
                <table class="admin-table">
                    <thead>
                        <tr><th>Ref</th><th>Customer</th><th>Amount</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_orders as $order): ?>
                        <tr>
                            <td>
                                <a href="order_details.php?id=<?php echo (int)$order['order_id']; ?>" style="font-family: var(--f-mono); font-size: 11px;"><?php echo htmlspecialchars($order['order_number'] ?? '#' . str_pad($order['order_id'], 5, '0', STR_PAD_LEFT)); ?></a>
                            </td>
                            <td>
                                <div style="font-weight: 700;"><?php echo htmlspecialchars($order['name'] ?? ''); ?></div>
                                <div style="font-size: 10px; opacity: 0.5;"><?php echo htmlspecialchars($order['email'] ?? ''); ?></div>
                            </td>
                            <td style="font-weight: 800;"><?php echo formatCurrency($order['total_amount']); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo htmlspecialchars($order['order_status']); ?>"><?php echo htmlspecialchars($order['order_status']); ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recent_orders)): ?>
                        <tr><td colspan="4" style="text-align: center; padding: 40px; color: var(--mid-gray);">No transactions yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div style="display: flex; flex-direction: column; gap: 40px;">
        <div class="panel">
            <div class="panel-header"><div class="panel-title">Strategic Actions</div></div>
            <div style="padding: 32px; display: flex; flex-direction: column; gap: 16px;">
                <a href="product_editor.php" class="btn-ink" style="width: 100%; justify-content: center; height: 50px; font-weight: 900; border-radius: 0;">DEPLOY NEW DROP</a>
                <a href="manage_products.php" class="btn-ink" style="width: 100%; justify-content: center; height: 50px; font-weight: 900; border-radius: 0; background: transparent; color: var(--ink); border: 2px solid var(--ink);">INVENTORY CONTROL</a>
                <a href="manage_hero.php" class="btn-ink" style="width: 100%; justify-content: center; height: 50px; font-weight: 900; border-radius: 0; background: transparent; color: var(--ink); border: 2px solid var(--ink);">EDIT HERO SLIDER</a>
            </div>
        </div>

        <?php if ($stats['low_stock'] > 0): ?>
        <div class="panel" style="background: var(--ink); color: #fff; border-color: var(--ink);">
            <div class="panel-header" style="border-bottom-color: rgba(255,255,255,0.1);">
                <div class="panel-title" style="color: #fff;">Low Stock Alerts</div>
                <a href="manage_products.php?stock=lowstock" class="nav-link" style="font-size: 10px; color: var(--red);">View All →</a>
            </div>
            <div class="table-container" style="border: none; background: transparent;">
                <table class="admin-table" style="background: var(--ink);">
                    <thead>
                        <tr>
                            <th style="background: rgba(255,255,255,0.05); color: rgba(255,255,255,0.6);">Product</th>
                            <th style="background: rgba(255,255,255,0.05); color: rgba(255,255,255,0.6);">Stock</th>
                            <th style="background: rgba(255,255,255,0.05); color: rgba(255,255,255,0.6);">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $lowStockProducts = $pdo->query("SELECT product_id, name, stock_quantity, low_stock_threshold FROM products WHERE stock_quantity > 0 AND stock_quantity <= COALESCE(low_stock_threshold, 5) ORDER BY stock_quantity ASC LIMIT 6")->fetchAll();
                        foreach ($lowStockProducts as $lsp):
                        ?>
                        <tr>
                            <td style="color: #fff;">
                                <a href="product_editor.php?id=<?php echo (int)$lsp['product_id']; ?>" style="color: #fff; font-weight: 700;"><?php echo htmlspecialchars($lsp['name']); ?></a>
                            </td>
                            <td style="color: #fff; font-family: var(--f-mono);"><?php echo (int)$lsp['stock_quantity']; ?> <span style="opacity: 0.5;">/ thr <?php echo (int)($lsp['low_stock_threshold'] ?? 5); ?></span></td>
                            <td><span class="status-badge status-pending">low</span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/avazonia_footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const fontStack = "'Inter', system-ui, -apple-system, sans-serif";
    const revenueLabels = <?php echo json_encode($revenue_dates); ?>;
    const revenueData = <?php echo json_encode($revenue_totals); ?>;

    new Chart(document.getElementById('revenueTrendChart'), {
        type: 'line',
        data: {
            labels: revenueLabels,
            datasets: [{
                label: 'Revenue',
                data: revenueData,
                borderColor: '#0B3D2E',
                backgroundColor: 'rgba(11,61,46,0.06)',
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointBackgroundColor: '#0B3D2E',
                borderWidth: 3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: '#f0f0f0' }, ticks: { font: { family: fontStack, size: 10 } } },
                x: { grid: { display: false }, ticks: { font: { family: fontStack, size: 10 } } }
            }
        }
    });

    const categoryLabels = <?php echo json_encode($category_labels); ?>;
    const categoryData = <?php echo json_encode($category_totals); ?>;

    new Chart(document.getElementById('categorySalesChart'), {
        type: 'doughnut',
        data: {
            labels: categoryLabels,
            datasets: [{
                data: categoryData,
                backgroundColor: ['#0B3D2E', '#00A854', '#4CAF50', '#8BC34A', '#C5E1A5'],
                borderWidth: 0,
                hoverOffset: 20
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { font: { family: fontStack, size: 10, weight: '700' }, boxWidth: 12, padding: 20 } }
            },
            cutout: '70%'
        }
    });
});
</script>
