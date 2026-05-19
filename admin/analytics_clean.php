<?php
/**
 * Clean Analytics Dashboard
 * - No sidebar, just the analytics
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Include database connection and functions
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

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
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
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

    // Get monthly data
    $result = $pdo->query("SELECT 
                            DATE_FORMAT(order_date, '%Y-%m') as month,
                            COUNT(*) as order_count,
                            COALESCE(SUM(total_amount), 0) as revenue
                          FROM orders 
                          WHERE order_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                          AND order_status != 'cancelled'
                          GROUP BY DATE_FORMAT(order_date, '%Y-%m')
                          ORDER BY month");
    
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $chart_data['months'][] = $row['month'];
        $chart_data['revenues'][] = (float)$row['revenue'];
        $chart_data['order_counts'][] = (int)$row['order_count'];
    }

    // Get payment methods
    $result = $pdo->query("SELECT 
                            CASE 
                                WHEN payment_method IN ('cash_on_delivery', 'cod', 'Pay on Delivery', 'pay_on_delivery', 'payment on delivery') THEN 'Cash on Delivery'
                                WHEN payment_method = 'paystack' THEN 'Online Payment'
                                ELSE NULL
                            END as payment_group,
                            COUNT(*) as count,
                            COALESCE(SUM(total_amount), 0) as amount
                          FROM orders 
                          WHERE payment_method IS NOT NULL
                            AND payment_method IN ('cash_on_delivery', 'cod', 'Pay on Delivery', 'pay_on_delivery', 'payment on delivery', 'paystack')
                          GROUP BY payment_group
                          HAVING payment_group IS NOT NULL
                          ORDER BY amount DESC");
    
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $chart_data['payment_methods'][] = $row['payment_group'];
        $chart_data['payment_totals'][] = (float)$row['amount'];
    }

    // Get daily data for last 30 days
    $result = $pdo->query("SELECT 
                            DATE(order_date) as date,
                            COUNT(*) as order_count,
                            COALESCE(SUM(total_amount), 0) as revenue
                          FROM orders 
                          WHERE order_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                          AND order_status != 'cancelled'
                          GROUP BY DATE(order_date)
                          ORDER BY date");
    
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $chart_data['daily_dates'][] = date('M j', strtotime($row['date']));
        $chart_data['daily_orders'][] = (int)$row['order_count'];
        $chart_data['daily_revenue'][] = (float)$row['revenue'];
    }

    // Get most bought products
    $result = $pdo->query("SELECT 
                            p.name as product_name,
                            p.image as product_image,
                            COUNT(oi.order_item_id) as times_ordered,
                            SUM(oi.quantity) as total_quantity,
                            SUM(oi.quantity * oi.product_price) as total_revenue
                          FROM order_items oi
                          JOIN products p ON oi.product_id = p.product_id
                          JOIN orders o ON oi.order_id = o.order_id
                          WHERE o.order_status != 'cancelled'
                          GROUP BY oi.product_id
                          ORDER BY total_quantity DESC
                          LIMIT 5");
    
    $chart_data['top_products'] = $result->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
    error_log($error_message);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Dashboard - <?php echo htmlspecialchars($site_name ?? 'ASO Online Market'); ?></title>
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="../assets/images/favicon.ico" type="image/x-icon">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            background-color: #f8f9fc;
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }
        .navbar {
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        .card {
            border: none;
            border-radius: 0.35rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            margin-bottom: 1.5rem;
        }
        .card-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
            padding: 1rem 1.25rem;
            border-radius: 0.35rem 0.35rem 0 0 !important;
        }
        .card-body {
            padding: 1.25rem;
        }
        .bg-primary {
            background-color: #4e73df !important;
        }
        .bg-success {
            background-color: #1cc88a !important;
        }
        .bg-info {
            background-color: #36b9cc !important;
        }
        .bg-warning {
            background-color: #f6c23e !important;
        }
        .text-white {
            color: #fff !important;
        }
        .icon-circle {
            align-items: center;
            border-radius: 50%;
            display: inline-flex;
            height: 2.5rem;
            justify-content: center;
            width: 2.5rem;
        }
        .small {
            font-size: 0.875rem;
        }
        .text-uppercase {
            text-transform: uppercase !important;
        }
        .font-weight-bold {
            font-weight: 700 !important;
        }
        .mb-0 {
            margin-bottom: 0 !important;
        }
        .h5 {
            font-size: 1.25rem;
        }
        .mb-4 {
            margin-bottom: 1.5rem !important;
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
        @media (min-width: 1200px) {
            .chart-pie {
                height: calc(20rem - 43px) !important;
            }
        }
        .content {
            padding: 20px;
        }
        @media (min-width: 768px) {
            .content {
                padding: 20px 30px;
            }
        }
    </style>
</head>
<body>
    <!-- Top Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-chart-line me-2"></i>Analytics Dashboard
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i> <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="../index.php"><i class="fas fa-home me-2"></i>View Site</a></li>
                            <li><a class="dropdown-item" href="../user/profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="content">
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <!-- Page Heading -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">Analytics Dashboard</h1>
            <a href="#" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                <i class="fas fa-download fa-sm text-white-50"></i> Generate Report
            </a>
        </div>

        <!-- Content Row -->
        <div class="row">
            <!-- Total Revenue Card -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-primary h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-[#1A1A1A] text-uppercase mb-1">
                                    Total Revenue</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">GH₵<?php echo number_format($stats['total_revenue'], 2); ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Orders Card -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-success h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    Total Orders</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($stats['total_orders']); ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-shopping-cart fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pending Orders Card -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-warning h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                    Pending Orders</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($stats['pending_orders']); ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-clock fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Completed Orders Card -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-info h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                    Completed Orders</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($stats['completed_orders']); ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row">
            <!-- Area Chart -->
            <div class="col-xl-8 col-lg-7">
                <div class="card shadow mb-4
                <!-- Card Header - Dropdown -->
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-[#1A1A1A]">Revenue Overview</h6>
                    <div class="dropdown no-arrow">
                        <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="dropdownMenuLink">
                            <li><a class="dropdown-item" href="#">This Year</a></li>
                            <li><a class="dropdown-item" href="#">Last Year</a></li>
                            <li><a class="dropdown-item" href="#">All Time</a></li>
                        </ul>
                    </div>
                </div>
                <!-- Card Body -->
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pie Chart -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <!-- Card Header - Dropdown -->
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-[#1A1A1A]">Order Status</h6>
                </div>
                <!-- Card Body -->
                <div class="card-body">
                    <div class="chart-pie pt-4 pb-2">
                        <canvas id="statusChart"></canvas>
                    </div>
                    <div class="mt-4 text-center small">
                        <?php foreach ($chart_data['status_labels'] as $index => $label): ?>
                            <span class="me-3">
                                <i class="fas fa-circle" style="color: <?php echo getStatusColor($label); ?>"></i> 
                                <?php echo $label; ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Second Row -->
    <div class="row">
        <!-- Payment Methods -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-[#1A1A1A]">Payment Methods</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie pt-4 pb-2">
                        <canvas id="paymentChart"></canvas>
                    </div>
                    <div class="mt-4 text-center small">
                        <?php foreach ($chart_data['payment_methods'] as $index => $method): ?>
                            <span class="me-3">
                                <i class="fas fa-circle" style="color: <?php echo getPaymentColor($method); ?>"></i> 
                                <?php echo $method; ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Daily Orders -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
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

    <!-- Top Products -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-[#1A1A1A]">Top Products</h6>
                </div>
                <div class="card-body">
                    <div class="chart-bar">
                        <canvas id="topProductsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap core JavaScript-->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
    // Set new default font family and font color to mimic Bootstrap's default styling
    Chart.defaults.font.family = 'Nunito, -apple-system, system-ui, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif';
    Chart.defaults.color = '#858796';

    // Function to format numbers with commas
    function number_format(number, decimals, dec_point, thousands_sep) {
        // Format number with commas and decimals
        number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
        var n = !isFinite(+number) ? 0 : +number,
            prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
            sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
            dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
            s = '',
            toFixedFix = function (n, prec) {
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

    // Helper function to get color for status
    function getStatusColor(status) {
        const colors = {
            'Pending': '#f6c23e',
            'Processing': '#36b9cc',
            'Shipped': '#4e73df',
            'Delivered': '#1cc88a',
            'Cancelled': '#e74a3b',
            'Refunded': '#6c757d',
            'Confirmed': '#4e73df'
        };
        return colors[status] || '#858796';
    }

    // Helper function to get color for payment method
    function getPaymentColor(method) {
        return method === 'Cash on Delivery' ? '#1cc88a' : '#4e73df';
    }

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
                    data: <?php echo json_encode($chart_data['revenues']); ?>
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
                        titleFontColor: '#6e707e',
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
                    backgroundColor: <?php 
                        $colors = [];
                        foreach ($chart_data['status_labels'] as $label) {
                            $colors[] = getStatusColor($label);
                        }
                        echo json_encode($colors);
                    ?>,
                    hoverBackgroundColor: <?php 
                        $hoverColors = [];
                        foreach ($chart_data['status_labels'] as $label) {
                            $hoverColors[] = adjustBrightness(getStatusColor($label), -10);
                        }
                        echo json_encode($hoverColors);
                    ?>,
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
                },
                cutout: '70%',
                plugins: {
                    legend: {
                        display: false
                    }
                }
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
                    backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e'],
                    hoverBackgroundColor: ['#2e59d9', '#17a673', '#2c9faf', '#dda20a'],
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
                            var label = data.labels[tooltipItem.index] || '';
                            if (label) {
                                label += ': ';
                            }
                            if (data.datasets[0].data[tooltipItem.index] !== null) {
                                label += 'GH₵' + number_format(data.datasets[0].data[tooltipItem.index], 2);
                            }
                            return label;
                        }
                    }
                },
                cutout: '70%',
                plugins: {
                    legend: {
                        display: false
                    }
                }
            },
        });
    }

    // Daily Orders Chart
    var ctx = document.getElementById("dailyOrdersChart");
    if (ctx) {
        var myLineChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($chart_data['daily_dates']); ?>,
                datasets: [
                    {
                        label: "Orders",
                        backgroundColor: "#4e73df",
                        hoverBackgroundColor: "#2e59d9",
                        borderColor: "#4e73df",
                        data: <?php echo json_encode($chart_data['daily_orders']); ?>
                    },
                    {
                        label: "Revenue",
                        backgroundColor: "#1cc88a",
                        hoverBackgroundColor: "#17a673",
                        borderColor: "#1cc88a",
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
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        backgroundColor: "rgb(255,255,255)",
                        bodyColor: "#858796",
                        titleMarginBottom: 10,
                        titleFontColor: '#6e707e',
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
                                    if (context.datasetIndex === 1) {
                                        label += 'GH₵' + number_format(context.parsed.y, 2);
                                    } else {
                                        label += number_format(context.parsed.y);
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
        // Prepare data for top products chart
        var productNames = <?php echo json_encode(array_column($chart_data['top_products'], 'product_name')); ?>;
        var productQuantities = <?php echo json_encode(array_column($chart_data['top_products'], 'total_quantity')); ?>;
        var productRevenues = <?php echo json_encode(array_column($chart_data['top_products'], 'total_revenue')); ?>;
        
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

        // Create the chart
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

    // Function to adjust color brightness
    function adjustBrightness(color, amount) {
        return '#' + color.replace(/^#/, '').replace(/../g, color => 
            ('0' + Math.min(255, Math.max(0, parseInt(color, 16) + amount)).toString(16)).substr(-2)
        );
    }
    </script>
</body>
</html>
