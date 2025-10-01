<?php
/**
 * Admin: Analytics Dashboard
 * - Comprehensive analytics with detailed charts and insights
 * - Revenue trends, order patterns, customer behavior, product performance
 */

// Include database connection
require_once '../includes/db.php';

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

// Get comprehensive analytics data
$analytics = [];
try {
    // Revenue and orders by month (last 12 months)
    $stmt = $pdo->query("SELECT
                            DATE_FORMAT(order_date, '%Y-%m') as month,
                            SUM(total_amount) as revenue,
                            COUNT(*) as orders_count,
                            AVG(total_amount) as avg_order_value
                        FROM orders
                        WHERE order_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                        GROUP BY DATE_FORMAT(order_date, '%Y-%m')
                        ORDER BY month");
    $monthly_data = $stmt->fetchAll();

    // Revenue and orders by month (last 6 months for more detailed view)
    $stmt = $pdo->query("SELECT
                            DATE_FORMAT(order_date, '%Y-%m') as month,
                            SUM(total_amount) as revenue,
                            COUNT(*) as orders_count
                        FROM orders
                        WHERE order_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                        GROUP BY DATE_FORMAT(order_date, '%Y-%m')
                        ORDER BY month");
    $recent_monthly_data = $stmt->fetchAll();

    // Daily stats for last 30 days
    $stmt = $pdo->query("SELECT
                            DATE(order_date) as date,
                            SUM(total_amount) as revenue,
                            COUNT(*) as orders_count
                        FROM orders
                        WHERE order_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                        GROUP BY DATE(order_date)
                        ORDER BY date");
    $daily_data = $stmt->fetchAll();

    // Top selling products
    $stmt = $pdo->query("SELECT
                            p.name,
                            SUM(oi.quantity) as total_sold,
                            SUM(oi.quantity * oi.price) as total_revenue
                        FROM order_items oi
                        JOIN products p ON oi.product_id = p.product_id
                        JOIN orders o ON oi.order_id = o.order_id
                        WHERE o.order_date >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
                        GROUP BY oi.product_id, p.name
                        ORDER BY total_revenue DESC
                        LIMIT 10");
    $top_products = $stmt->fetchAll();

    // Customer acquisition over time
    $stmt = $pdo->query("SELECT
                            DATE_FORMAT(created_at, '%Y-%m') as month,
                            COUNT(*) as new_customers
                        FROM users
                        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                        AND role = 'customer'
                        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                        ORDER BY month");
    $customer_growth = $stmt->fetchAll();

    // Order status distribution
    $stmt = $pdo->query("SELECT
                            status,
                            COUNT(*) as count
                        FROM orders
                        GROUP BY status");
    $order_status_data = $stmt->fetchAll();

    // Payment method distribution
    $stmt = $pdo->query("SELECT
                            payment_method,
                            COUNT(*) as count,
                            SUM(total_amount) as revenue
                        FROM orders
                        GROUP BY payment_method");
    $payment_data = $stmt->fetchAll();

    // Prepare data for charts
    $months = [];
    $revenues = [];
    $orders_counts = [];
    $avg_order_values = [];

    foreach ($monthly_data as $data) {
        $months[] = date('M Y', strtotime($data['month'] . '-01'));
        $revenues[] = (float)$data['revenue'];
        $orders_counts[] = (int)$data['orders_count'];
        $avg_order_values[] = (float)$data['avg_order_value'];
    }

    // Recent months for detailed view
    $recent_months = [];
    $recent_revenues = [];
    $recent_orders = [];

    foreach ($recent_monthly_data as $data) {
        $recent_months[] = date('M Y', strtotime($data['month'] . '-01'));
        $recent_revenues[] = (float)$data['revenue'];
        $recent_orders[] = (int)$data['orders_count'];
    }

    // Daily data preparation
    $daily_dates = [];
    $daily_revenues = [];
    $daily_orders = [];

    foreach ($daily_data as $data) {
        $daily_dates[] = date('M j', strtotime($data['date']));
        $daily_revenues[] = (float)$data['revenue'];
        $daily_orders[] = (int)$data['orders_count'];
    }

    // Customer growth data
    $customer_months = [];
    $new_customers = [];

    foreach ($customer_growth as $data) {
        $customer_months[] = date('M Y', strtotime($data['month'] . '-01'));
        $new_customers[] = (int)$data['new_customers'];
    }

    // Order status data
    $status_labels = [];
    $status_counts = [];

    foreach ($order_status_data as $data) {
        $status_labels[] = ucfirst($data['status']);
        $status_counts[] = (int)$data['count'];
    }

    // Payment method data
    $payment_labels = [];
    $payment_revenues = [];

    foreach ($payment_data as $data) {
        $payment_labels[] = ucfirst(str_replace('_', ' ', $data['payment_method']));
        $payment_revenues[] = (float)$data['revenue'];
    }
}

catch(PDOException $e) {
    error_log("Error fetching analytics data: " . $e->getMessage());
    $months = $revenues = $orders_counts = $payment_labels = $payment_revenues = [];
}

?>

<?php include '../includes/header.php'; ?>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1"><i class="fas fa-chart-line me-2"></i>Analytics Dashboard</h2>
                    <p class="text-muted mb-0">Comprehensive insights into your store performance</p>
                </div>
                <a href="dashboard.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <!-- Key Metrics Summary -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <i class="fas fa-dollar-sign fa-2x mb-2"></i>
                    <h4><?php echo formatCurrency(array_sum($revenues)); ?></h4>
                    <small>Total Revenue (12mo)</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <i class="fas fa-shopping-cart fa-2x mb-2"></i>
                    <h4><?php echo array_sum($orders_counts); ?></h4>
                    <small>Total Orders (12mo)</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <i class="fas fa-chart-bar fa-2x mb-2"></i>
                    <h4><?php echo formatCurrency($avg_order_values ? $avg_order_values[0] : 0); ?></h4>
                    <small>Avg Order Value</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <i class="fas fa-users fa-2x mb-2"></i>
                    <h4><?php echo array_sum($new_customers); ?></h4>
                    <small>New Customers (12mo)</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Charts -->
    <div class="row mb-4">
        <!-- Revenue Trend -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Revenue Trend (12 Months)</h5>
                </div>
                <div class="card-body">
                    <canvas id="revenueTrendChart" height="300"></canvas>
                </div>
            </div>
        </div>

        <!-- Order Status Distribution -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Order Status</h5>
                </div>
                <div class="card-body">
                    <canvas id="orderStatusChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Secondary Charts -->
    <div class="row mb-4">
        <!-- Recent Activity (6 months) -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Recent Performance (6 Months)</h5>
                </div>
                <div class="card-body">
                    <canvas id="recentPerformanceChart" height="250"></canvas>
                </div>
            </div>
        </div>

        <!-- Payment Methods -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Payment Methods</h5>
                </div>
                <div class="card-body">
                    <canvas id="paymentMethodsChart" height="250"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Additional Analytics -->
    <div class="row mb-4">
        <!-- Daily Activity (30 days) -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Daily Activity (Last 30 Days)</h5>
                </div>
                <div class="card-body">
                    <canvas id="dailyActivityChart" height="200"></canvas>
                </div>
            </div>
        </div>

        <!-- Top Products -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Top Products (3 Months)</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($top_products)): ?>
                        <p class="text-muted text-center py-4">No product data available</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th class="text-end">Revenue</th>
                                        <th class="text-center">Units Sold</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($top_products as $product): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                                            <td class="text-end"><?php echo formatCurrency($product['total_revenue']); ?></td>
                                            <td class="text-center"><?php echo $product['total_sold']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Customer Growth -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Customer Growth (12 Months)</h5>
                </div>
                <div class="card-body">
                    <canvas id="customerGrowthChart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js Analytics -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Revenue Trend Chart (12 months)
    const revenueTrendCtx = document.getElementById('revenueTrendChart').getContext('2d');
    new Chart(revenueTrendCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($months); ?>,
            datasets: [{
                label: 'Revenue (GH₵)',
                data: <?php echo json_encode($revenues); ?>,
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                tension: 0.1,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
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

    // Order Status Distribution
    const orderStatusCtx = document.getElementById('orderStatusChart').getContext('2d');
    new Chart(orderStatusCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($status_labels); ?>,
            datasets: [{
                data: <?php echo json_encode($status_counts); ?>,
                backgroundColor: [
                    '#ffc107', // pending - warning
                    '#17a2b8', // processing - info
                    '#007bff', // shipped - primary
                    '#28a745', // delivered - success
                    '#dc3545'  // cancelled - danger
                ],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Recent Performance (6 months)
    const recentPerformanceCtx = document.getElementById('recentPerformanceChart').getContext('2d');
    new Chart(recentPerformanceCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($recent_months); ?>,
            datasets: [
                {
                    label: 'Revenue',
                    data: <?php echo json_encode($recent_revenues); ?>,
                    backgroundColor: 'rgba(75, 192, 192, 0.8)',
                    yAxisID: 'y'
                },
                {
                    label: 'Orders',
                    data: <?php echo json_encode($recent_orders); ?>,
                    backgroundColor: 'rgba(153, 102, 255, 0.8)',
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top'
                }
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    ticks: {
                        callback: function(value) {
                            return 'GH₵' + value.toLocaleString();
                        }
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    grid: {
                        drawOnChartArea: false,
                    },
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });

    // Payment Methods
    const paymentMethodsCtx = document.getElementById('paymentMethodsChart').getContext('2d');
    new Chart(paymentMethodsCtx, {
        type: 'pie',
        data: {
            labels: <?php echo json_encode($payment_labels); ?>,
            datasets: [{
                data: <?php echo json_encode($payment_revenues); ?>,
                backgroundColor: [
                    '#007bff', // cash on delivery
                    '#28a745', // paypal
                    '#17a2b8'  // paystack
                ],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Daily Activity (30 days)
    const dailyActivityCtx = document.getElementById('dailyActivityChart').getContext('2d');
    new Chart(dailyActivityCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($daily_dates); ?>,
            datasets: [
                {
                    label: 'Daily Revenue',
                    data: <?php echo json_encode($daily_revenues); ?>,
                    borderColor: 'rgb(255, 99, 132)',
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    yAxisID: 'y'
                },
                {
                    label: 'Daily Orders',
                    data: <?php echo json_encode($daily_orders); ?>,
                    borderColor: 'rgb(54, 162, 235)',
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top'
                }
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    ticks: {
                        callback: function(value) {
                            return 'GH₵' + value.toLocaleString();
                        }
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    grid: {
                        drawOnChartArea: false,
                    }
                }
            }
        }
    });

    // Customer Growth Chart
    const customerGrowthCtx = document.getElementById('customerGrowthChart').getContext('2d');
    new Chart(customerGrowthCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($customer_months); ?>,
            datasets: [{
                label: 'New Customers',
                data: <?php echo json_encode($new_customers); ?>,
                backgroundColor: 'rgba(255, 206, 86, 0.8)',
                borderColor: 'rgb(255, 206, 86)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
});
</script>

<style>
.analytics-card {
    transition: all 0.3s ease;
    cursor: pointer;
}

.analytics-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.card {
    border: none;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}

canvas {
    max-height: 300px;
}
</style>



