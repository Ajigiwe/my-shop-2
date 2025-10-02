<?php
/**
 * Admin: Analytics Dashboard
 * - Comprehensive analytics with detailed charts and insights
 * - Revenue trends, order patterns, customer behavior, product performance
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Debug: Show all errors
ini_set('display_startup_errors', 1);

// Include database connection
require_once '../includes/db.php';

// Debug: Check database connection and fetch sample data
try {
    // Test connection and get order count
    $test = $pdo->query("SELECT COUNT(*) as count FROM orders");
    $order_count = $test->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Get sample order data for debugging
    $sample_orders = $pdo->query("SELECT * FROM orders ORDER BY order_date DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    
    // Get table structure
    $table_info = [];
    $tables = ['orders', 'order_items', 'users'];
    foreach ($tables as $table) {
        $result = $pdo->query("DESCRIBE $table");
        if ($result) {
            $table_info[$table] = $result->fetchAll(PDO::FETCH_COLUMN);
        }
    }
    
    $debug_info = [
        'db_connection' => 'Success',
        'order_count' => $order_count,
        'sample_orders' => $sample_orders,
        'table_structure' => $table_info,
        'server' => [
            'PHP_SELF' => $_SERVER['PHP_SELF'],
            'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? null,
            'HTTP_REFERER' => $_SERVER['HTTP_REFERER'] ?? null
        ],
        'session' => session_status() === PHP_SESSION_ACTIVE ? [
            'user_id' => $_SESSION['user_id'] ?? null,
            'user_role' => $_SESSION['user_role'] ?? null,
            'session_status' => 'active'
        ] : 'Session not started'
    ];
    
    // Uncomment to see debug info
    echo '<div style="background: #f8f9fa; padding: 20px; margin: 20px; border: 1px solid #ddd; border-radius: 5px;">';
    echo '<h3>Debug Information</h3>';
    echo '<pre>'.print_r($debug_info, true).'</pre>';
    echo '</div>';
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}

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
    'completed_orders' => 0
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
    'daily_revenue' => []
];

try {
    // Get basic stats
    $result = $pdo->query("SELECT 
                            COUNT(*) as total_orders,
                            COALESCE(SUM(total_amount), 0) as total_revenue,
                            COALESCE(AVG(total_amount), 0) as avg_order_value
                          FROM orders");
    
    if ($result) {
        $data = $result->fetch(PDO::FETCH_ASSOC);
        $stats['total_orders'] = $data['total_orders'];
        $stats['total_revenue'] = $data['total_revenue'];
        $stats['avg_order_value'] = $data['avg_order_value'];
    }

    // Get order status counts
    $result = $pdo->query("SELECT 
                            status, 
                            COUNT(*) as count,
                            SUM(total_amount) as amount
                          FROM orders 
                          GROUP BY status");
    
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $chart_data['status_labels'][] = ucfirst($row['status']);
        $chart_data['status_counts'][] = (int)$row['count'];
        
        if (in_array(strtolower($row['status']), ['pending', 'processing'])) {
            $stats['pending_orders'] += $row['count'];
        } elseif (strtolower($row['status']) === 'delivered') {
            $stats['completed_orders'] += $row['count'];
        }
    }

    // Get monthly data
    $result = $pdo->query("SELECT 
                            DATE_FORMAT(order_date, '%Y-%m') as month,
                            COUNT(*) as order_count,
                            COALESCE(SUM(total_amount), 0) as revenue
                          FROM orders 
                          WHERE order_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                          AND status != 'cancelled'
                          GROUP BY DATE_FORMAT(order_date, '%Y-%m')
                          ORDER BY month");
    
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $chart_data['months'][] = $row['month'];
        $chart_data['revenues'][] = (float)$row['revenue'];
        $chart_data['order_counts'][] = (int)$row['order_count'];
    }

    // Get payment methods
    $result = $pdo->query("SELECT 
                            payment_method,
                            COUNT(*) as count,
                            COALESCE(SUM(total_amount), 0) as amount
                          FROM orders 
                          GROUP BY payment_method");
    
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $chart_data['payment_methods'][] = ucfirst(str_replace('_', ' ', $row['payment_method']));
        $chart_data['payment_totals'][] = (float)$row['amount'];
    }

    // Get daily data for last 30 days
    $result = $pdo->query("SELECT 
                            DATE(order_date) as date,
                            COUNT(*) as order_count,
                            COALESCE(SUM(total_amount), 0) as revenue
                          FROM orders 
                          WHERE order_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                          AND status != 'cancelled'
                          GROUP BY DATE(order_date)
                          ORDER BY date");
    
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $chart_data['daily_dates'][] = date('M j', strtotime($row['date']));
        $chart_data['daily_orders'][] = (int)$row['order_count'];
        $chart_data['daily_revenue'][] = (float)$row['revenue'];
    }

} catch (PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
    error_log($error_message);
}
?>

<?php include '../includes/header.php'; ?>

<div class="container-fluid py-4">
    <!-- Debug Info -->
    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <!-- No Data Message -->
    <?php if ($stats['total_orders'] == 0): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            No order data available. Start making sales to see analytics.
        </div>
    <?php endif; ?>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3 col-sm-6 mb-4">
            <div class="card border-left-primary h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Revenue</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                GH₵<?php echo number_format($stats['total_revenue'], 2); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 col-sm-6 mb-4">
            <div class="card border-left-success h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Orders</div>
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
        
        <div class="col-md-3 col-sm-6 mb-4">
            <div class="card border-left-info h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Avg. Order Value</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                GH₵<?php echo number_format($stats['avg_order_value'], 2); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-tag fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 col-sm-6 mb-4">
            <div class="card border-left-warning h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pending Orders</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($stats['pending_orders']); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($stats['total_orders'] > 0): ?>
    <!-- Charts Section -->
    <div class="row">
        <!-- Revenue Trend -->
        <div class="col-lg-8 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Revenue Trend (Last 12 Months)</h6>
                </div>
                <div class="card-body">
                    <canvas id="revenueChart" height="100"></canvas>
                </div>
            </div>
        </div>

        <!-- Order Status -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Order Status</h6>
                </div>
                <div class="card-body">
                    <canvas id="statusChart" height="300"></canvas>
                </div>
            </div>
        </div>

        <!-- Payment Methods -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Payment Methods</h6>
                </div>
                <div class="card-body">
                    <canvas id="paymentChart" height="200"></canvas>
                </div>
            </div>
        </div>

        <!-- Daily Activity -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Daily Activity (Last 30 Days)</h6>
                </div>
                <div class="card-body">
                    <canvas id="dailyChart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Revenue Trend Chart
    if (document.getElementById('revenueChart')) {
        new Chart(document.getElementById('revenueChart').getContext('2d'), {
            type: 'line',
            data: {
                labels: <?php echo json_encode($chart_data['months']); ?>,
                datasets: [{
                    label: 'Revenue (GH₵)',
                    data: <?php echo json_encode($chart_data['revenues']); ?>,
                    borderColor: '#4e73df',
                    backgroundColor: 'rgba(78, 115, 223, 0.05)',
                    borderWidth: 2,
                    pointRadius: 3,
                    pointBackgroundColor: '#4e73df',
                    pointBorderColor: '#4e73df',
                    pointHoverRadius: 5,
                    pointHoverBackgroundColor: '#4e73df',
                    pointHoverBorderColor: '#4e73df',
                    pointHitRadius: 10,
                    pointBorderWidth: 2,
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                maintainAspectRatio: false,
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'GH₵' + context.raw.toLocaleString(undefined, {minimumFractionDigits: 2});
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'GH₵' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    }

    // Status Chart
    if (document.getElementById('statusChart')) {
        new Chart(document.getElementById('statusChart').getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($chart_data['status_labels']); ?>,
                datasets: [{
                    data: <?php echo json_encode($chart_data['status_counts']); ?>,
                    backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b'],
                    hoverBackgroundColor: ['#2e59d9', '#17a673', '#2c9faf', '#dda20a', '#be2617'],
                    hoverBorderColor: 'rgba(234, 236, 244, 1)',
                }]
            },
            options: {
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                },
                cutout: '70%'
            }
        });
    }

    // Payment Chart
    if (document.getElementById('paymentChart')) {
        new Chart(document.getElementById('paymentChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($chart_data['payment_methods']); ?>,
                datasets: [{
                    label: 'Revenue (GH₵)',
                    data: <?php echo json_encode($chart_data['payment_totals']); ?>,
                    backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc'],
                    hoverBackgroundColor: ['#2e59d9', '#17a673', '#2c9faf'],
                    borderColor: '#fff',
                    borderWidth: 1,
                    borderRadius: 5
                }]
            },
            options: {
                maintainAspectRatio: false,
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'GH₵' + context.raw.toLocaleString(undefined, {minimumFractionDigits: 2});
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'GH₵' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    }

    // Daily Activity Chart
    if (document.getElementById('dailyChart')) {
        new Chart(document.getElementById('dailyChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($chart_data['daily_dates']); ?>,
                datasets: [{
                    label: 'Orders',
                    data: <?php echo json_encode($chart_data['daily_orders']); ?>,
                    backgroundColor: 'rgba(78, 115, 223, 0.8)',
                    borderColor: 'rgba(78, 115, 223, 1)',
                    borderWidth: 1,
                    borderRadius: 3,
                    yAxisID: 'y'
                }, {
                    label: 'Revenue (GH₵)',
                    data: <?php echo json_encode($chart_data['daily_revenue']); ?>,
                    type: 'line',
                    borderColor: '#1cc88a',
                    backgroundColor: 'rgba(28, 200, 138, 0.1)',
                    borderWidth: 2,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    yAxisID: 'y1',
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                maintainAspectRatio: false,
                responsive: true,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    if (context.datasetIndex === 1) { // Revenue
                                        label += 'GH₵' + context.parsed.y.toLocaleString(undefined, {minimumFractionDigits: 2});
                                    } else { // Orders
                                        label += context.parsed.y;
                                    }
                                }
                                return label;
                            }
                        }
                    },
                    legend: {
                        position: 'bottom'
                    }
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Number of Orders'
                        },
                        ticks: {
                            beginAtZero: true,
                            precision: 0
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Revenue (GH₵)'
                        },
                        grid: {
                            drawOnChartArea: false
                        },
                        ticks: {
                            beginAtZero: true,
                            callback: function(value) {
                                return 'GH₵' + value.toLocaleString();
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?>
