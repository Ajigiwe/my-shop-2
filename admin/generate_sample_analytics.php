<?php
/**
 * Generate Sample Analytics Data
 * This script generates sample analytics data for the file-based analytics
 */

header('Content-Type: application/json');

try {
    $analytics_file = '../logs/analytics_data.json';
    
    // Generate realistic sample data
    $stats = [
        'total_orders' => rand(40, 80),
        'total_revenue' => rand(10000, 25000),
        'avg_order_value' => rand(200, 400),
        'pending_orders' => rand(5, 15),
        'completed_orders' => rand(20, 40),
        'total_users' => rand(25, 50),
        'total_products' => rand(10, 25)
    ];
    
    // Generate monthly data for last 6 months
    $months = [];
    $revenues = [];
    $order_counts = [];
    
    for ($i = 5; $i >= 0; $i--) {
        $date = date('Y-m', strtotime("-$i months"));
        $months[] = $date;
        $revenues[] = rand(1500, 5000);
        $order_counts[] = rand(8, 20);
    }
    
    // Generate status data
    $status_counts = [
        rand(5, 12), // pending
        rand(8, 18), // confirmed
        rand(6, 15), // processing
        rand(4, 10), // shipped
        rand(10, 25), // delivered
        rand(1, 5)   // cancelled
    ];
    
    $status_labels = ['Pending', 'Confirmed', 'Processing', 'Shipped', 'Delivered', 'Cancelled'];
    
    // Generate payment methods data
    $payment_methods = ['Cash on Delivery', 'Paystack', 'Bank Transfer'];
    $payment_totals = [rand(3000, 8000), rand(2000, 6000), rand(1000, 3000)];
    
    // Generate daily data for last 7 days
    $daily_dates = [];
    $daily_orders = [];
    $daily_revenue = [];
    
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $daily_dates[] = $date;
        $daily_orders[] = rand(1, 8);
        $daily_revenue[] = rand(200, 1200);
    }
    
    // Generate top products
    $product_names = [
        'Wireless Headphones', 'Smart Watch', 'Bluetooth Speaker', 'Phone Case',
        'Laptop Stand', 'USB Cable', 'Power Bank', 'Screen Protector',
        'Wireless Mouse', 'Keyboard', 'Tablet Cover', 'Charging Dock'
    ];
    
    $top_products = [];
    for ($i = 0; $i < 8; $i++) {
        $top_products[] = [
            'name' => $product_names[array_rand($product_names)],
            'sold' => rand(5, 30),
            'revenue' => rand(500, 2500)
        ];
    }
    
    // Sort by revenue
    usort($top_products, function($a, $b) {
        return $b['revenue'] - $a['revenue'];
    });
    
    $chart_data = [
        'months' => $months,
        'revenues' => $revenues,
        'order_counts' => $order_counts,
        'payment_methods' => $payment_methods,
        'payment_totals' => $payment_totals,
        'status_counts' => $status_counts,
        'status_labels' => $status_labels,
        'daily_dates' => $daily_dates,
        'daily_orders' => $daily_orders,
        'daily_revenue' => $daily_revenue,
        'top_products' => $top_products
    ];
    
    // Create analytics data structure
    $analytics_data = [
        'stats' => $stats,
        'chart_data' => $chart_data,
        'last_updated' => date('Y-m-d H:i:s'),
        'generated' => true
    ];
    
    // Ensure logs directory exists
    if (!is_dir(dirname($analytics_file))) {
        mkdir(dirname($analytics_file), 0755, true);
    }
    
    // Save to file
    if (file_put_contents($analytics_file, json_encode($analytics_data, JSON_PRETTY_PRINT))) {
        echo json_encode([
            'success' => true,
            'message' => 'Sample analytics data generated successfully',
            'data' => $analytics_data
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to save analytics data file'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error generating sample data: ' . $e->getMessage()
    ]);
}
?>
