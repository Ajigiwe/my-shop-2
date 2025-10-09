<?php
/**
 * Admin: Analytics Dashboard (MySQLi Version)
 * - Works without PDO MySQL driver
 * - Uses MySQLi for database connections
 */

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Set page title
$page_title = 'Analytics Dashboard';

// Initialize variables
$stats = [
    'total_orders' => 0,
    'total_revenue' => 0,
    'avg_order_value' => 0,
    'pending_orders' => 0,
    'completed_orders' => 0,
    'total_users' => 0,
    'total_products' => 0
];

$chart_data = [
    'months' => [],
    'revenues' => [],
    'order_counts' => [],
    'payment_methods' => [],
    'payment_totals' => [],
    'status_counts' => [],
    'status_labels' => [],
    'daily_dates' => [],
    'daily_orders' => [],
    'daily_revenue' => [],
    'top_products' => []
];

$error_message = '';

try {
    // Create MySQLi connection
    $mysqli = new mysqli('localhost', 'root', '', 'ecommerce_db');
    
    if ($mysqli->connect_error) {
        throw new Exception('Database connection failed: ' . $mysqli->connect_error);
    }
    
    // Get basic stats
    $query = "SELECT 
                COUNT(*) as total_orders,
                COALESCE(SUM(total_amount), 0) as total_revenue,
                COALESCE(AVG(total_amount), 0) as avg_order_value
              FROM orders";
    
    $result = $mysqli->query($query);
    if ($result && $row = $result->fetch_assoc()) {
        $stats['total_orders'] = $row['total_orders'] ?? 0;
        $stats['total_revenue'] = $row['total_revenue'] ?? 0;
        $stats['avg_order_value'] = $row['avg_order_value'] ?? 0;
    }

    // Get total users
    $query = "SELECT COUNT(*) as total FROM users WHERE role = 'customer'";
    $result = $mysqli->query($query);
    if ($result && $row = $result->fetch_assoc()) {
        $stats['total_users'] = $row['total'] ?? 0;
    }

    // Get total products
    $query = "SELECT COUNT(*) as total FROM products";
    $result = $mysqli->query($query);
    if ($result && $row = $result->fetch_assoc()) {
        $stats['total_products'] = $row['total'] ?? 0;
    }

    // Get order status counts
    $query = "SELECT 
                order_status, 
                COUNT(*) as count,
                SUM(total_amount) as amount
              FROM orders 
              GROUP BY order_status";
    
    $result = $mysqli->query($query);
    
    // Initialize all possible statuses with 0 count
    $statuses = [
        'pending' => 0,
        'confirmed' => 0,
        'processing' => 0,
        'shipped' => 0,
        'delivered' => 0,
        'cancelled' => 0,
        'refunded' => 0
    ];
    
    // Update with actual counts from database
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $status = strtolower($row['order_status']);
            if (array_key_exists($status, $statuses)) {
                $statuses[$status] = (int)$row['count'];
            }
        }
    }
    
    // Prepare data for chart
    foreach ($statuses as $status => $count) {
        if ($count > 0) {
            $chart_data['status_counts'][] = $count;
            $chart_data['status_labels'][] = ucfirst($status);
        }
    }
    
    // Get monthly revenue data
    $query = "SELECT 
                DATE_FORMAT(order_date, '%Y-%m') as month,
                COUNT(*) as orders,
                COALESCE(SUM(total_amount), 0) as revenue
              FROM orders 
              WHERE order_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
              GROUP BY DATE_FORMAT(order_date, '%Y-%m')
              ORDER BY month";
    
    $result = $mysqli->query($query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $chart_data['months'][] = $row['month'];
            $chart_data['revenues'][] = (float)$row['revenue'];
            $chart_data['order_counts'][] = (int)$row['orders'];
        }
    }
    
    // Get payment method data
    $query = "SELECT 
                payment_method,
                COUNT(*) as count,
                SUM(total_amount) as amount
              FROM orders 
              WHERE payment_method IS NOT NULL
              GROUP BY payment_method";
    
    $result = $mysqli->query($query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $chart_data['payment_methods'][] = $row['payment_method'];
            $chart_data['payment_totals'][] = (float)$row['amount'];
        }
    }
    
    // Get daily data for last 30 days
    $query = "SELECT 
                DATE(order_date) as date,
                COUNT(*) as orders,
                COALESCE(SUM(total_amount), 0) as revenue
              FROM orders 
              WHERE order_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
              GROUP BY DATE(order_date)
              ORDER BY date";
    
    $result = $mysqli->query($query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $chart_data['daily_dates'][] = $row['date'];
            $chart_data['daily_orders'][] = (int)$row['orders'];
            $chart_data['daily_revenue'][] = (float)$row['revenue'];
        }
    }
    
    // Get top products
    $query = "SELECT 
                p.name,
                SUM(oi.quantity) as total_sold,
                SUM(oi.quantity * oi.price) as total_revenue
              FROM order_items oi
              JOIN products p ON oi.product_id = p.product_id
              GROUP BY oi.product_id, p.name
              ORDER BY total_sold DESC
              LIMIT 10";
    
    $result = $mysqli->query($query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $chart_data['top_products'][] = [
                'name' => $row['name'],
                'sold' => (int)$row['total_sold'],
                'revenue' => (float)$row['total_revenue']
            ];
        }
    }
    
    $mysqli->close();
    
} catch (Exception $e) {
    $error_message = "Error loading analytics data: " . $e->getMessage();
    error_log("Analytics Error: " . $e->getMessage());
}

// Include header
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Analytics Dashboard</h1>
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-outline-primary" onclick="refreshAnalytics()">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
            </div>

            <?php if ($error_message): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php else: ?>

            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Total Orders</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo number_format($stats['total_orders']); ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-shopping-cart fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Total Revenue</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        ₵<?php echo number_format($stats['total_revenue'], 2); ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                        Average Order Value</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        ₵<?php echo number_format($stats['avg_order_value'], 2); ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                        Total Customers</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo number_format($stats['total_users']); ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-users fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="row">
                <!-- Monthly Revenue Chart -->
                <div class="col-xl-8 col-lg-7">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">Monthly Revenue</h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-area">
                                <canvas id="monthlyRevenueChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Status Pie Chart -->
                <div class="col-xl-4 col-lg-5">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">Order Status</h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-pie pt-4 pb-2">
                                <canvas id="orderStatusChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Second Row -->
            <div class="row">
                <!-- Payment Methods -->
                <div class="col-xl-6 col-lg-6">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">Payment Methods</h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-area">
                                <canvas id="paymentMethodsChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Daily Orders -->
                <div class="col-xl-6 col-lg-6">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">Daily Orders (Last 30 Days)</h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-area">
                                <canvas id="dailyOrdersChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Products Table -->
            <div class="row">
                <div class="col-12">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Top Products</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Product Name</th>
                                            <th>Units Sold</th>
                                            <th>Revenue</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($chart_data['top_products'] as $product): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                                            <td><?php echo number_format($product['sold']); ?></td>
                                            <td>₵<?php echo number_format($product['revenue'], 2); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Chart data from PHP
const chartData = {
    months: <?php echo json_encode($chart_data['months']); ?>,
    revenues: <?php echo json_encode($chart_data['revenues']); ?>,
    orderCounts: <?php echo json_encode($chart_data['order_counts']); ?>,
    paymentMethods: <?php echo json_encode($chart_data['payment_methods']); ?>,
    paymentTotals: <?php echo json_encode($chart_data['payment_totals']); ?>,
    statusCounts: <?php echo json_encode($chart_data['status_counts']); ?>,
    statusLabels: <?php echo json_encode($chart_data['status_labels']); ?>,
    dailyDates: <?php echo json_encode($chart_data['daily_dates']); ?>,
    dailyOrders: <?php echo json_encode($chart_data['daily_orders']); ?>,
    dailyRevenue: <?php echo json_encode($chart_data['daily_revenue']); ?>
};

// Monthly Revenue Chart
const monthlyRevenueCtx = document.getElementById('monthlyRevenueChart').getContext('2d');
new Chart(monthlyRevenueCtx, {
    type: 'line',
    data: {
        labels: chartData.months,
        datasets: [{
            label: 'Revenue (₵)',
            data: chartData.revenues,
            borderColor: 'rgb(75, 192, 192)',
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Order Status Pie Chart
const orderStatusCtx = document.getElementById('orderStatusChart').getContext('2d');
new Chart(orderStatusCtx, {
    type: 'doughnut',
    data: {
        labels: chartData.statusLabels,
        datasets: [{
            data: chartData.statusCounts,
            backgroundColor: [
                '#FF6384',
                '#36A2EB',
                '#FFCE56',
                '#4BC0C0',
                '#9966FF',
                '#FF9F40',
                '#FF6384'
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});

// Payment Methods Chart
const paymentMethodsCtx = document.getElementById('paymentMethodsChart').getContext('2d');
new Chart(paymentMethodsCtx, {
    type: 'bar',
    data: {
        labels: chartData.paymentMethods,
        datasets: [{
            label: 'Revenue (₵)',
            data: chartData.paymentTotals,
            backgroundColor: 'rgba(54, 162, 235, 0.8)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Daily Orders Chart
const dailyOrdersCtx = document.getElementById('dailyOrdersChart').getContext('2d');
new Chart(dailyOrdersCtx, {
    type: 'bar',
    data: {
        labels: chartData.dailyDates,
        datasets: [{
            label: 'Orders',
            data: chartData.dailyOrders,
            backgroundColor: 'rgba(255, 99, 132, 0.8)',
            borderColor: 'rgba(255, 99, 132, 1)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

function refreshAnalytics() {
    location.reload();
}
</script>

<?php include 'includes/footer.php'; ?>
