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

include 'includes/header-new.php';
?>

<div class="row g-4 mb-5">
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-label">Net Revenue</div>
            <div class="stat-value"><?php echo formatCurrency($stats['total_revenue']); ?></div>
            <div class="small text-success mt-2 fw-bold">+12% from last month</div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-label">Avg. Order Value</div>
            <div class="stat-value"><?php echo formatCurrency($stats['avg_order']); ?></div>
            <div class="small text-muted mt-2 fw-bold">Per transaction</div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-label">Total Transactions</div>
            <div class="stat-value"><?php echo number_format($stats['total_orders']); ?></div>
            <div class="small text-[#1A1A1A] mt-2 fw-bold">Live orders</div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-label">Customer Base</div>
            <div class="stat-value"><?php echo number_format($stats['active_customers']); ?></div>
            <div class="small text-muted mt-2 fw-bold">Registered accounts</div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Main Revenue Chart -->
    <div class="col-12">
        <div class="admin-card">
            <div class="admin-card-header">
                <h5 class="admin-card-title mb-0">Revenue Growth</h5>
                <div class="badge bg-light text-dark rounded-pill px-3">Last 6 Months</div>
            </div>
            <div class="card-body p-4">
                <div style="position: relative; height: 350px; width: 100%;">
                    <canvas id="revenueGrowthChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Best Selling Products -->
    <div class="col-xl-4">
        <div class="admin-card">
            <div class="admin-card-header">
                <h5 class="admin-card-title mb-0">Best Selling Products</h5>
            </div>
            <div class="card-body p-4">
                <div style="position: relative; height: 250px; width: 100%;">
                    <canvas id="bestSellersPieChart"></canvas>
                </div>
                <div class="mt-4 pt-2">
                    <?php foreach($chart_data['product_labels'] as $i => $label): ?>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="small fw-bold text-muted text-truncate pe-2" title="<?php echo htmlspecialchars($label); ?>">
                                <?php echo htmlspecialchars($label); ?>
                            </div>
                            <span class="fw-black flex-shrink-0"><?php echo $chart_data['product_counts'][$i]; ?> units</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Bestsellers Table -->
    <div class="col-xl-8">
        <div class="admin-card">
            <div class="admin-card-header">
                <h5 class="admin-card-title mb-0">Bestselling Products</h5>
            </div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>Units Sold</th>
                            <th>Market Share</th>
                            <th class="text-end">Growth</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($chart_data['top_products'] as $prod): ?>
                        <tr>
                            <td class="fw-bold text-truncate" style="max-width: 200px;" title="<?php echo htmlspecialchars($prod['name']); ?>"><?php echo htmlspecialchars($prod['name']); ?></td>
                            <td class="fw-black"><?php echo $prod['total_sold']; ?> units</td>
                            <td>
                                <div class="progress rounded-pill" style="height: 6px; width: 150px;">
                                    <div class="progress-bar bg-dark" style="width: <?php echo min(100, $prod['total_sold'] * 2); ?>%"></div>
                                </div>
                            </td>
                            <td class="text-end text-success fw-bold">+4.2%</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Revenue Chart
    const revCtx = document.getElementById('revenueGrowthChart').getContext('2d');
    new Chart(revCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($chart_data['months']); ?>,
            datasets: [{
                label: 'Revenue',
                data: <?php echo json_encode($chart_data['revenues']); ?>,
                borderColor: '#1A1A1A',
                backgroundColor: 'rgba(26, 26, 26, 0.05)',
                borderWidth: 4,
                fill: true,
                tension: 0.4,
                pointRadius: 6,
                pointBackgroundColor: '#1A1A1A',
                pointBorderColor: '#FFFFFF',
                pointBorderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { grid: { borderDash: [5, 5] }, ticks: { font: { weight: 'bold' } } },
                x: { grid: { display: false }, ticks: { font: { weight: 'bold' } } }
            }
        }
    });

    // Best Sellers Pie Chart
    const pieCtx = document.getElementById('bestSellersPieChart').getContext('2d');
    new Chart(pieCtx, {
        type: 'pie',
        data: {
            labels: <?php echo json_encode($chart_data['product_labels']); ?>,
            datasets: [{
                data: <?php echo json_encode($chart_data['product_counts']); ?>,
                backgroundColor: ['#1A1A1A', '#333333', '#666666', '#999999', '#CCCCCC'],
                borderWidth: 0,
                hoverOffset: 10
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } }
        }
    });
});
</script>

<?php include 'includes/footer-new.php'; ?>