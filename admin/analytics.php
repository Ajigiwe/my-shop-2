<?php
/**
 * Modern Admin Analytics
 */
require_once '../includes/db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$page_title = 'Analytics & Insights';

// Fetch Data
$stats = [];
$chart_data = ['months' => [], 'revenues' => [], 'status_labels' => [], 'status_counts' => [], 'top_products' => []];

try {
    // Basic Metrics
    $stats['total_orders'] = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    $stats['total_revenue'] = $pdo->query("SELECT SUM(total_amount) FROM orders")->fetchColumn() ?? 0;
    $stats['avg_order'] = $pdo->query("SELECT AVG(total_amount) FROM orders")->fetchColumn() ?? 0;
    $stats['active_customers'] = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();

    // Monthly Revenue (Last 6 Months)
    $stmt = $pdo->query("SELECT DATE_FORMAT(order_date, '%b %Y') as month, SUM(total_amount) as revenue 
                        FROM orders WHERE order_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH) 
                        GROUP BY month ORDER BY order_date ASC");
    while($row = $stmt->fetch()) {
        $chart_data['months'][] = $row['month'];
        $chart_data['revenues'][] = (float)$row['revenue'];
    }

    // Status Distribution
    $stmt = $pdo->query("SELECT order_status, COUNT(*) as count FROM orders GROUP BY order_status");
    while($row = $stmt->fetch()) {
        $chart_data['status_labels'][] = ucfirst($row['order_status']);
        $chart_data['status_counts'][] = (int)$row['count'];
    }

    // Top Products
    $chart_data['top_products'] = $pdo->query("SELECT p.name, SUM(oi.quantity) as total_sold 
                                             FROM order_items oi JOIN products p ON oi.product_id = p.product_id 
                                             GROUP BY p.product_id ORDER BY total_sold DESC LIMIT 5")->fetchAll();

    // Prepare arrays for best sellers pie chart
    $chart_data['product_labels'] = [];
    $chart_data['product_counts'] = [];
    foreach($chart_data['top_products'] as $prod) {
        $chart_data['product_labels'][] = $prod['name'];
        $chart_data['product_counts'][] = (int)$prod['total_sold'];
    }

} catch(PDOException $e) {
    error_log("Analytics error: " . $e->getMessage());
}

include 'includes/avazonia_header.php';
?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="analytics-grid">
    <div class="stat-card-bold">
        <span class="label">Net Revenue</span>
        <span class="value"><?php echo formatCurrency($stats['total_revenue']); ?></span>
        <div class="trend-indicator trend-up">▲ 12.0% <span style="opacity: 0.5; color: var(--ink);">vs last month</span></div>
    </div>
    <div class="stat-card-bold">
        <span class="label">Avg. Order Value</span>
        <span class="value"><?php echo formatCurrency($stats['avg_order']); ?></span>
        <div style="font-family: var(--f-mono); font-size: 10px; color: var(--mid-gray);">PER TRANSACTION</div>
    </div>
    <div class="stat-card-bold">
        <span class="label">Total Transactions</span>
        <span class="value"><?php echo number_format($stats['total_orders']); ?></span>
        <div style="font-family: var(--f-mono); font-size: 10px; color: var(--mid-gray);">LIVE ORDERS</div>
    </div>
    <div class="stat-card-bold">
        <span class="label">Customer Base</span>
        <span class="value"><?php echo number_format($stats['active_customers']); ?></span>
        <div style="font-family: var(--f-mono); font-size: 10px; color: var(--mid-gray);">REGISTERED ACCOUNTS</div>
    </div>
</div>

<div style="margin-bottom: 40px;">
    <div class="panel">
        <div class="panel-header">
            <div class="panel-title">Revenue Growth <span style="opacity: 0.4;">(Last 6 Months)</span></div>
        </div>
        <div style="padding: 32px; height: 350px;">
            <canvas id="revenueGrowthChart"></canvas>
        </div>
    </div>
</div>

<div class="dashboard-layout">
    <div class="panel" style="margin-bottom: 0;">
        <div class="panel-header"><div class="panel-title">Best Selling Products</div></div>
        <div style="padding: 40px; display: flex; justify-content: center; align-items: center; min-height: 250px;">
            <div style="width: 100%; max-width: 280px;">
                <canvas id="bestSellersPieChart"></canvas>
            </div>
        </div>
        <div style="padding: 0 32px 32px;">
            <?php foreach($chart_data['product_labels'] as $i => $label): ?>
                <div class="d-flex justify-content-between align-items-center" style="margin-bottom: 8px;">
                    <div style="font-size: 12px; font-weight: 700; color: var(--mid-gray); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; padding-right: 8px;" title="<?php echo htmlspecialchars($label); ?>">
                        <?php echo htmlspecialchars($label); ?>
                    </div>
                    <span style="font-weight: 900; flex-shrink: 0;"><?php echo $chart_data['product_counts'][$i]; ?> units</span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="panel" style="margin-bottom: 0;">
        <div class="panel-header"><div class="panel-title">Bestselling Products</div></div>
        <div class="table-container" style="border: none; margin-bottom: 0; border-radius: 0;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Product Name</th>
                        <th>Units Sold</th>
                        <th>Market Share</th>
                        <th style="text-align: right;">Growth</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($chart_data['top_products'] as $prod): ?>
                    <tr>
                        <td style="font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 200px;" title="<?php echo htmlspecialchars($prod['name']); ?>"><?php echo htmlspecialchars($prod['name']); ?></td>
                        <td style="font-weight: 900;"><?php echo $prod['total_sold']; ?> units</td>
                        <td>
                            <div style="width: 150px; height: 6px; background: var(--light-gray); border-radius: 0;">
                                <div style="width: <?php echo min(100, $prod['total_sold'] * 2); ?>%; height: 100%; background: var(--ink);"></div>
                            </div>
                        </td>
                        <td style="text-align: right; font-weight: 700; color: #00a854;">+4.2%</td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($chart_data['top_products'])): ?>
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 48px; color: var(--mid-gray);">No sales data yet.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/avazonia_footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const fontStack = "'Inter', system-ui, -apple-system, sans-serif";

    // Revenue Chart
    const revCtx = document.getElementById('revenueGrowthChart').getContext('2d');
    new Chart(revCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($chart_data['months']); ?>,
            datasets: [{
                label: 'Revenue',
                data: <?php echo json_encode($chart_data['revenues']); ?>,
                borderColor: '#0B3D2E',
                backgroundColor: 'rgba(11,61,46,0.06)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointBackgroundColor: '#0B3D2E',
                pointBorderColor: '#FFFFFF',
                pointBorderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: '#f0f0f0' }, ticks: { font: { family: fontStack, size: 10, weight: '700' } } },
                x: { grid: { display: false }, ticks: { font: { family: fontStack, size: 10, weight: '700' } } }
            }
        }
    });

    // Best Sellers Pie Chart
    const pieCtx = document.getElementById('bestSellersPieChart').getContext('2d');
    new Chart(pieCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($chart_data['product_labels']); ?>,
            datasets: [{
                data: <?php echo json_encode($chart_data['product_counts']); ?>,
                backgroundColor: ['#0B3D2E', '#00A854', '#4CAF50', '#8BC34A', '#C5E1A5'],
                borderWidth: 0,
                hoverOffset: 10
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
