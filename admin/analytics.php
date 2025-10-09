<?php
/**
 * Admin: Analytics Dashboard
 * - Comprehensive analytics with charts and metrics
 * - Uses same header structure as dashboard.php
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

try {
    // Get basic stats
    $stmt = $pdo->query("SELECT 
                        COUNT(*) as total_orders,
                        COALESCE(SUM(total_amount), 0) as total_revenue,
                        COALESCE(AVG(total_amount), 0) as avg_order_value
                      FROM orders");
    
    if ($stmt) {
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['total_orders'] = $data['total_orders'] ?? 0;
        $stats['total_revenue'] = $data['total_revenue'] ?? 0;
        $stats['avg_order_value'] = $data['avg_order_value'] ?? 0;
    } else {
        error_log("Failed to fetch basic stats");
    }

    // Get total users
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'customer'");
    $stats['total_users'] = $stmt->fetch()['total'] ?? 0;

    // Get total products
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM products");
    $stats['total_products'] = $stmt->fetch()['total'] ?? 0;

    // Get order status counts - FIXED: Use order_status instead of status
    $stmt = $pdo->query("SELECT 
                        order_status, 
                        COUNT(*) as count,
                        SUM(total_amount) as amount
                      FROM orders 
                      GROUP BY order_status");
    
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
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $status = strtolower($row['order_status']);
        if (array_key_exists($status, $statuses)) {
            $statuses[$status] = (int)$row['count'];
        }
    }
    
    // Prepare data for chart
    foreach ($statuses as $status => $count) {
        if ($count > 0) {
            $chart_data['status_labels'][] = ucfirst($status);
            $chart_data['status_counts'][] = $count;
        }
    }
    
    // Update stats
    $stats['pending_orders'] = $statuses['pending'] + $statuses['confirmed'] + $statuses['processing'];
    $stats['completed_orders'] = $statuses['delivered'];
    
    // Get monthly data for the last 12 months - FIXED: Use order_status instead of status
    $stmt = $pdo->query("SELECT 
                        DATE_FORMAT(order_date, '%Y-%m') as month,
                        COUNT(*) as order_count,
                        COALESCE(SUM(total_amount), 0) as revenue
                      FROM orders 
                      WHERE order_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                      AND order_status != 'cancelled'
                      GROUP BY DATE_FORMAT(order_date, '%Y-%m')
                      ORDER BY month");
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $chart_data['months'][] = date('M Y', strtotime($row['month'] . '-01'));
        $chart_data['order_counts'][] = (int)$row['order_count'];
        $chart_data['revenues'][] = (float)$row['revenue'];
    }
    
    // Get payment methods
    $stmt = $pdo->query("SELECT 
                        CASE 
                            WHEN payment_method IN ('cash_on_delivery', 'cod', 'Pay on Delivery', 'pay_on_delivery') THEN 'Cash on Delivery'
                            WHEN payment_method = 'paypal' THEN 'PayPal'
                            WHEN payment_method = 'paystack' THEN 'Paystack'
                            ELSE payment_method
                        END as payment_group,
                        COUNT(*) as count,
                        SUM(total_amount) as amount
                      FROM orders 
                      WHERE payment_method IS NOT NULL
                      GROUP BY payment_group");
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $chart_data['payment_methods'][] = $row['payment_group'];
        $chart_data['payment_totals'][] = (float)$row['amount'];
    }
    
    // Get daily data for the last 30 days - FIXED: Use order_status instead of status
    $stmt = $pdo->query("SELECT 
                        DATE(order_date) as date,
                        COUNT(*) as order_count,
                        COALESCE(SUM(total_amount), 0) as revenue
                      FROM orders 
                      WHERE order_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                      AND order_status != 'cancelled'
                      GROUP BY DATE(order_date)
                      ORDER BY date");
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $chart_data['daily_dates'][] = date('M j', strtotime($row['date']));
        $chart_data['daily_orders'][] = (int)$row['order_count'];
        $chart_data['daily_revenue'][] = (float)$row['revenue'];
    }

    // Get most bought products - FIXED: Use order_status instead of status
    $stmt = $pdo->query("SELECT 
                        p.name as product_name,
                        p.image as product_image,
                        COUNT(oi.order_item_id) as times_ordered,
                        SUM(oi.quantity) as total_quantity,
                        SUM(oi.quantity * oi.price) as total_revenue
                      FROM order_items oi
                      JOIN products p ON oi.product_id = p.product_id
                      JOIN orders o ON oi.order_id = o.order_id
                      WHERE o.order_status != 'cancelled'
                      GROUP BY oi.product_id
                      ORDER BY times_ordered DESC
                      LIMIT 5");
    
    $chart_data['top_products'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Analytics error: " . $e->getMessage());
    $error_message = "Error loading analytics data. Please check the error log for details.";
}

// Helper function to get color for status
function getStatusColor($status) {
    $status = strtolower($status);
    switch ($status) {
        case 'pending':
            return '#f6c23e'; // Yellow
        case 'processing':
            return '#36b9cc'; // Cyan
        case 'shipped':
            return '#4e73df'; // Blue
        case 'delivered':
            return '#1cc88a'; // Green
        case 'cancelled':
            return '#e74a3b'; // Red
        case 'refunded':
            return '#6f42c1'; // Purple
        case 'confirmed':
            return '#20c9a6'; // Teal
        default:
            return '#858796'; // Gray
    }
}

// Helper function to get color for payment method
function getPaymentColor($method) {
    $method = strtolower($method);
    if (strpos($method, 'cash') !== false || strpos($method, 'cod') !== false) {
        return '#4e73df'; // Blue
    } elseif (strpos($method, 'paypal') !== false) {
        return '#00b4b4'; // Teal
    } elseif (strpos($method, 'paystack') !== false) {
        return '#00b4b4'; // Teal
    } else {
        return '#858796'; // Gray
    }
}

// Include header before any HTML output
include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <a href="dashboard.php" class="btn btn-outline-secondary btn-sm mb-2">
                <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
            </a>
            <h1 class="h3 mb-0 text-gray-800">Analytics Dashboard</h1>
        </div>
        <a href="#" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
            <i class="fas fa-download fa-sm text-white-50"></i> Generate Report
        </a>
    </div>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <!-- Debug Information -->
    <?php if (isset($_GET['debug'])): ?>
        <div class="alert alert-info">
            <h6>Debug Information:</h6>
            <p><strong>Total Orders:</strong> <?php echo $stats['total_orders']; ?></p>
            <p><strong>Total Revenue:</strong> <?php echo $stats['total_revenue']; ?></p>
            <p><strong>Chart Data Months:</strong> <?php echo json_encode($chart_data['months']); ?></p>
            <p><strong>Chart Data Revenues:</strong> <?php echo json_encode($chart_data['revenues']); ?></p>
        </div>
    <?php endif; ?>



    <?php if ($stats['total_orders'] > 0): ?>
    <!-- Charts Row -->
    <div class="row">
        <!-- Revenue Chart -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Revenue Overview (Last 12 Months)</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Status Chart -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Order Status Distribution</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie pt-4 pb-2">
                        <canvas id="statusChart"></canvas>
                    </div>
                    <div class="mt-4 text-center small">
                        <?php foreach ($chart_data['status_labels'] as $index => $label): ?>
                            <span class="mr-2">
                                <i class="fas fa-circle" style="color: <?php echo getStatusColor($label); ?>"></i> <?php echo $label; ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Second Charts Row -->
    <div class="row">
        <!-- Payment Methods Chart -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Payment Methods</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie pt-4 pb-2">
                        <canvas id="paymentChart"></canvas>
                    </div>
                    <div class="mt-4 text-center small">
                        <?php foreach ($chart_data['payment_methods'] as $index => $method): ?>
                            <span class="mr-2">
                                <i class="fas fa-circle" style="color: <?php echo getPaymentColor($method); ?>"></i> <?php echo $method; ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Daily Activity Chart -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Daily Activity (Last 30 Days)</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="dailyActivityChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Products Chart -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Top 5 Bestselling Products</h6>
                </div>
                <div class="card-body">
                    <div class="chart-bar">
                        <canvas id="topProductsChart"></canvas>
                    </div>
                    <div class="mt-4 small text-muted">
                        <i class="fas fa-info-circle"></i> Shows top 5 most purchased products by quantity sold
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php else: ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            No order data available. Start making sales to see analytics.
        </div>
    <?php endif; ?>
</div>

<!-- Custom CSS for Analytics -->
<style>
    .border-left-primary {
        border-left: 0.25rem solid #4e73df !important;
    }
    .border-left-success {
        border-left: 0.25rem solid #1cc88a !important;
    }
    .border-left-info {
        border-left: 0.25rem solid #36b9cc !important;
    }
    .border-left-warning {
        border-left: 0.25rem solid #f6c23e !important;
    }
    .text-xs {
        font-size: 0.7rem;
    }
    .font-weight-bold {
        font-weight: 700 !important;
    }
    .text-uppercase {
        text-transform: uppercase !important;
    }
    .text-gray-800 {
        color: #5a5c69 !important;
    }
    .text-gray-300 {
        color: #dddfeb !important;
    }
    .chart-area {
        position: relative;
        height: 20rem;
        width: 100%;
    }
    .chart-pie {
        position: relative;
        height: 20rem;
        width: 100%;
    }
    .chart-bar {
        position: relative;
        height: 20rem;
        width: 100%;
    }
    @media (min-width: 1200px) {
        .chart-pie {
            height: calc(20rem - 43px) !important;
        }
    }
</style>

<!-- Bootstrap core JavaScript-->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Set Chart.js defaults
Chart.defaults.font.family = 'Nunito', '-apple-system,system-ui,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif';
Chart.defaults.color = '#858796';

// Number formatting function
function number_format(number, decimals, dec_point, thousands_sep) {
    number = (number + '').replace(',', '').replace(' ', '');
    var n = !isFinite(+number) ? 0 : +number,
        prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
        sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
        dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
        s = '',
        toFixedFix = function(n, prec) {
            var k = Math.pow(10, prec);
            return '' + Math.round(n * k) / k;
        };
    s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
    if (s[0].length > 3) {
        s[0] = s[0].replace(/\B(?=(\d{3})+(?!\d))/g, sep);
    }
    if ((s[1] || '').length < prec) {
        s[1] = s[1] || '';
        s[1] += new Array(prec - s[1].length + 1).join('0');
    }
    return s.join(dec);
}

<?php if ($stats['total_orders'] > 0): ?>
// Revenue Chart
var ctx = document.getElementById("revenueChart");
if (ctx) {
    var myLineChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($chart_data['months']); ?>,
            datasets: [{
                label: "Revenue",
                lineTension: 0.3,
                backgroundColor: "rgba(78, 115, 223, 0.05)",
                borderColor: "rgba(78, 115, 223, 1)",
                pointRadius: 3,
                pointBackgroundColor: "rgba(78, 115, 223, 1)",
                pointBorderColor: "rgba(78, 115, 223, 1)",
                pointHoverRadius: 3,
                pointHoverBackgroundColor: "rgba(78, 115, 223, 1)",
                pointHoverBorderColor: "rgba(78, 115, 223, 1)",
                pointHitRadius: 10,
                pointBorderWidth: 2,
                data: <?php echo json_encode($chart_data['revenues']); ?>,
            }],
        },
        options: {
            maintainAspectRatio: false,
            layout: {
                padding: {
                    left: 10,
                    right: 25,
                    top: 25,
                    bottom: 0
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false,
                        drawBorder: false
                    },
                    ticks: {
                        maxTicksLimit: 7
                    }
                },
                y: {
                    ticks: {
                        maxTicksLimit: 5,
                        padding: 10,
                        callback: function(value, index, values) {
                            return 'GH₵' + number_format(value);
                        }
                    },
                    grid: {
                        color: "rgb(234, 236, 244)",
                        zeroLineColor: "rgb(234, 236, 244)",
                        drawBorder: false,
                        borderDash: [2],
                        zeroLineBorderDash: [2]
                    }
                },
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: "rgb(255,255,255)",
                    bodyColor: "#858796",
                    titleMarginBottom: 10,
                    titleColor: '#6e707e',
                    titleFontSize: 14,
                    borderColor: '#dddfeb',
                    borderWidth: 1,
                    xPadding: 15,
                    yPadding: 15,
                    displayColors: false,
                    intersect: false,
                    mode: 'index',
                    caretPadding: 10,
                    callbacks: {
                        label: function(context) {
                            var label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                label += 'GH₵' + number_format(context.parsed.y, 2);
                            }
                            return label;
                        }
                    }
                }
            }
        }
    });
}

// Status Chart
var ctx = document.getElementById("statusChart");
if (ctx) {
    var myPieChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($chart_data['status_labels']); ?>,
            datasets: [{
                data: <?php echo json_encode($chart_data['status_counts']); ?>,
                backgroundColor: [
                    '#f6c23e', '#36b9cc', '#4e73df', '#1cc88a', '#e74a3b', '#6f42c1', '#20c9a6'
                ],
                hoverBackgroundColor: [
                    '#f4b942', '#2c9faf', '#2e59d9', '#17a673', '#e02d1b', '#5a32a3', '#1ea896'
                ],
                hoverBorderColor: "rgba(234, 236, 244, 1)",
            }],
        },
        options: {
            maintainAspectRatio: false,
            tooltips: {
                backgroundColor: "rgb(255,255,255)",
                bodyFontColor: "#858796",
                borderColor: '#dddfeb',
                borderWidth: 1,
                xPadding: 15,
                yPadding: 15,
                displayColors: false,
                caretPadding: 10,
                callbacks: {
                    label: function(tooltipItem, data) {
                        var dataset = data.datasets[tooltipItem.datasetIndex];
                        var total = dataset.data.reduce(function(previousValue, currentValue, currentIndex, array) {
                            return previousValue + currentValue;
                        });
                        var currentValue = dataset.data[tooltipItem.index];
                        var percentage = Math.floor(((currentValue/total) * 100)+0.5);
                        return data.labels[tooltipItem.index] + ': ' + currentValue + ' (' + percentage + '%)';
                    }
                }
            },
            cutout: '70%',
        },
    });
}

// Payment Chart
var ctx = document.getElementById("paymentChart");
if (ctx) {
    var myPieChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($chart_data['payment_methods']); ?>,
            datasets: [{
                data: <?php echo json_encode($chart_data['payment_totals']); ?>,
                backgroundColor: [
                    '#4e73df', '#00b4b4', '#1cc88a', '#f6c23e', '#e74a3b'
                ],
                hoverBackgroundColor: [
                    '#2e59d9', '#009999', '#17a673', '#f4b942', '#e02d1b'
                ],
                hoverBorderColor: "rgba(234, 236, 244, 1)",
            }],
        },
        options: {
            maintainAspectRatio: false,
            tooltips: {
                backgroundColor: "rgb(255,255,255)",
                bodyFontColor: "#858796",
                borderColor: '#dddfeb',
                borderWidth: 1,
                xPadding: 15,
                yPadding: 15,
                displayColors: false,
                caretPadding: 10,
                callbacks: {
                    label: function(tooltipItem, data) {
                        var dataset = data.datasets[tooltipItem.datasetIndex];
                        var total = dataset.data.reduce(function(previousValue, currentValue, currentIndex, array) {
                            return previousValue + currentValue;
                        });
                        var currentValue = dataset.data[tooltipItem.index];
                        var percentage = Math.floor(((currentValue/total) * 100)+0.5);
                        return data.labels[tooltipItem.index] + ': GH₵' + number_format(currentValue, 2) + ' (' + percentage + '%)';
                    }
                }
            },
            cutout: '70%',
        },
    });
}

// Daily Activity Chart
var ctx = document.getElementById("dailyActivityChart");
if (ctx) {
    var myLineChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($chart_data['daily_dates']); ?>,
            datasets: [
                {
                    label: "Orders",
                    backgroundColor: "#4e73df",
                    borderColor: "#4e73df",
                    data: <?php echo json_encode($chart_data['daily_orders']); ?>
                },
                {
                    label: "Revenue",
                    type: "line",
                    backgroundColor: "#1cc88a",
                    borderColor: "#1cc88a",
                    pointBackgroundColor: "#1cc88a",
                    pointBorderColor: "#1cc88a",
                    pointHoverRadius: 3,
                    pointHoverBackgroundColor: "#1cc88a",
                    pointHoverBorderColor: "#1cc88a",
                    pointHitRadius: 10,
                    pointBorderWidth: 2,
                    fill: false,
                    data: <?php echo json_encode($chart_data['daily_revenue']); ?>
                }
            ],
        },
        options: {
            maintainAspectRatio: false,
            layout: {
                padding: {
                    left: 10,
                    right: 25,
                    top: 25,
                    bottom: 0
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false,
                        drawBorder: false
                    },
                    ticks: {
                        maxTicksLimit: 7
                    }
                },
                y: {
                    ticks: {
                        maxTicksLimit: 5,
                        padding: 10,
                        callback: function(value, index, values) {
                            return number_format(value);
                        }
                    },
                    grid: {
                        color: "rgb(234, 236, 244)",
                        zeroLineColor: "rgb(234, 236, 244)",
                        drawBorder: false,
                        borderDash: [2],
                        zeroLineBorderDash: [2]
                    }
                },
                y1: {
                    position: 'right',
                    grid: {
                        display: false,
                        drawBorder: false
                    },
                    ticks: {
                        maxTicksLimit: 5,
                        padding: 10,
                        callback: function(value, index, values) {
                            return 'GH₵' + number_format(value);
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom'
                },
                tooltip: {
                    backgroundColor: "rgb(255,255,255)",
                    bodyColor: "#858796",
                    titleMarginBottom: 10,
                    titleColor: '#6e707e',
                    titleFontSize: 14,
                    borderColor: '#dddfeb',
                    borderWidth: 1,
                    xPadding: 15,
                    yPadding: 15,
                    displayColors: false,
                    intersect: false,
                    mode: 'index',
                    caretPadding: 10,
                    callbacks: {
                        label: function(context) {
                            var label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.datasetIndex === 1) {
                                if (context.parsed.y !== null) {
                                    label += 'GH₵' + number_format(context.parsed.y, 2);
                                }
                            } else {
                                if (context.parsed.y !== null) {
                                    label += context.parsed.y;
                                }
                            }
                            return label;
                        }
                    }
                }
            }
        }
    });
}

// Top Products Chart
var ctx = document.getElementById("topProductsChart");
if (ctx) {
    var productNames = <?php echo json_encode(array_column($chart_data['top_products'] ?? [], 'product_name')); ?>;
    var productQuantities = <?php echo json_encode(array_column($chart_data['top_products'] ?? [], 'total_quantity')); ?>;
    var productRevenues = <?php echo json_encode(array_column($chart_data['top_products'] ?? [], 'total_revenue')); ?>;

    var backgroundColors = [
        'rgba(78, 115, 223, 0.8)',
        'rgba(28, 200, 138, 0.8)',
        'rgba(54, 185, 204, 0.8)',
        'rgba(246, 194, 62, 0.8)',
        'rgba(231, 74, 59, 0.8)'
    ];

    var borderColors = [
        'rgba(78, 115, 223, 1)',
        'rgba(28, 200, 138, 1)',
        'rgba(54, 185, 204, 1)',
        'rgba(246, 194, 62, 1)',
        'rgba(231, 74, 59, 1)'
    ];

    var myBarChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: productNames,
            datasets: [{
                label: 'Quantity Sold',
                data: productQuantities,
                backgroundColor: backgroundColors,
                borderColor: borderColors,
                borderWidth: 1,
                borderRadius: 4,
                barPercentage: 0.7,
                categoryPercentage: 0.8
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        afterLabel: function(context) {
                            var index = context.dataIndex;
                            var revenue = productRevenues[index];
                            return 'Revenue: GH₵' + number_format(revenue, 2);
                        }
                    }
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    grid: {
                        display: false,
                        drawBorder: false
                    },
                    ticks: {
                        precision: 0
                    }
                },
                y: {
                    grid: {
                        display: false,
                        drawBorder: false
                    }
                }
            }
        }
    });
}
<?php endif; ?>

// Utility functions
function exportData() {
    alert('Export functionality will be implemented soon!');
}

function refreshCharts() {
    location.reload();
}
</script>