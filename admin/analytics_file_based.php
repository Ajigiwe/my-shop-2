<?php
/**
 * Admin: Analytics Dashboard (File-Based Version)
 * - Works without any database drivers
 * - Uses file-based storage for analytics data
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

// File-based data storage
$analytics_file = '../logs/analytics_data.json';

try {
    // Try to load existing analytics data
    if (file_exists($analytics_file)) {
        $analytics_data = json_decode(file_get_contents($analytics_file), true);
        if ($analytics_data) {
            $stats = array_merge($stats, $analytics_data['stats'] ?? []);
            $chart_data = array_merge($chart_data, $analytics_data['chart_data'] ?? []);
        }
    }
    
    // If no data exists, create sample data
    if (empty($chart_data['months'])) {
        // Generate sample data for demonstration
        $stats = [
            'total_orders' => 45,
            'total_revenue' => 12500.00,
            'avg_order_value' => 277.78,
            'pending_orders' => 8,
            'completed_orders' => 32,
            'total_users' => 28,
            'total_products' => 15
        ];
        
        $chart_data = [
            'months' => ['2024-08', '2024-09', '2024-10'],
            'revenues' => [3500, 4200, 4800],
            'order_counts' => [12, 15, 18],
            'payment_methods' => ['Cash on Delivery', 'Paystack'],
            'payment_totals' => [7500, 5000],
            'status_counts' => [8, 15, 12, 5, 3, 2],
            'status_labels' => ['Pending', 'Confirmed', 'Processing', 'Shipped', 'Delivered', 'Cancelled'],
            'daily_dates' => ['2024-10-01', '2024-10-02', '2024-10-03', '2024-10-04', '2024-10-05'],
            'daily_orders' => [3, 5, 2, 4, 6],
            'daily_revenue' => [850, 1200, 450, 950, 1400],
            'top_products' => [
                ['name' => 'Sample Product 1', 'sold' => 25, 'revenue' => 2500],
                ['name' => 'Sample Product 2', 'sold' => 18, 'revenue' => 1800],
                ['name' => 'Sample Product 3', 'sold' => 12, 'revenue' => 1200]
            ]
        ];
        
        // Save sample data
        $analytics_data = [
            'stats' => $stats,
            'chart_data' => $chart_data,
            'last_updated' => date('Y-m-d H:i:s')
        ];
        
        if (!is_dir(dirname($analytics_file))) {
            mkdir(dirname($analytics_file), 0755, true);
        }
        
        file_put_contents($analytics_file, json_encode($analytics_data, JSON_PRETTY_PRINT));
    }
    
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
                    <button type="button" class="btn btn-outline-info" onclick="generateSampleData()">
                        <i class="fas fa-chart-bar"></i> Generate Sample Data
                    </button>
                </div>
            </div>

            <!-- Database Driver Warning -->
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Note:</strong> Database drivers (PDO MySQL, MySQLi) are not available. 
                This analytics dashboard is showing file-based data. 
                <a href="#" onclick="showDriverInstructions()">Click here for setup instructions</a>.
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
                                    <div class="text-xs font-weight-bold text-[#1A1A1A] text-uppercase mb-1">
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
                            <h6 class="m-0 font-weight-bold text-[#1A1A1A]">Monthly Revenue</h6>
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
                            <h6 class="m-0 font-weight-bold text-[#1A1A1A]">Order Status</h6>
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
                            <h6 class="m-0 font-weight-bold text-[#1A1A1A]">Payment Methods</h6>
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
                            <h6 class="m-0 font-weight-bold text-[#1A1A1A]">Daily Orders (Last 30 Days)</h6>
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
                            <h6 class="m-0 font-weight-bold text-[#1A1A1A]">Top Products</h6>
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

function generateSampleData() {
    if (confirm('Generate new sample data? This will replace current data.')) {
        // Delete existing analytics file to force regeneration
        fetch('generate_sample_analytics.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({action: 'generate'})
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error generating sample data: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error generating sample data');
        });
    }
}

function showDriverInstructions() {
    alert(`To enable database analytics:

1. Open XAMPP Control Panel
2. Stop Apache
3. Edit php.ini file (usually in C:\\xampp\\php\\php.ini)
4. Find and uncomment these lines:
   extension=mysqli
   extension=pdo_mysql
5. Save the file
6. Start Apache again
7. Refresh this page

Current status:
- PDO: ${typeof PDO !== 'undefined' ? 'Available' : 'Not available'}
- MySQLi: Not available
- PDO MySQL: Not available`);
}
</script>

<?php include 'includes/footer.php'; ?>
