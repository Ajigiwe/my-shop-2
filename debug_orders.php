<?php
// Session and Order Debug Test
require_once 'includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_log('=== SESSION DEBUG TEST ===');
error_log('Session ID: ' . session_id());
error_log('Current session data: ' . print_r($_SESSION, true));

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<h1>Session Test</h1>\n";
    echo "<p>You are not logged in. <a href='login.php'>Login here</a></p>\n";
    exit();
}

echo "<h1>Session Test</h1>\n";
echo "<p><strong>Session ID:</strong> " . session_id() . "</p>\n";
echo "<p><strong>User ID:</strong> " . $_SESSION['user_id'] . "</p>\n";
echo "<p><strong>Username:</strong> " . ($_SESSION['user_name'] ?? 'Not set') . "</p>\n";

// Check recent orders
try {
    $stmt = $pdo->prepare("
        SELECT o.*, COUNT(oi.order_item_id) as item_count
        FROM orders o
        LEFT JOIN order_items oi ON o.order_id = oi.order_id
        WHERE o.user_id = ?
        GROUP BY o.order_id
        ORDER BY o.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $recent_orders = $stmt->fetchAll();

    echo "<h2>Recent Orders</h2>\n";
    if (!empty($recent_orders)) {
        echo "<ul>\n";
        foreach ($recent_orders as $order) {
            echo "<li>\n";
            echo "Order #" . str_pad($order['order_id'], 6, '0', STR_PAD_LEFT) . " - ";
            echo formatCurrency($order['total_amount']) . " - ";
            echo ucfirst($order['status']) . " - ";
            echo "<a href='order_details.php?order_id=" . $order['order_id'] . "'>View Details</a>\n";
            echo "</li>\n";
        }
        echo "</ul>\n";
    } else {
        echo "<p>No recent orders found.</p>\n";
    }

} catch(PDOException $e) {
    echo "<p>Error loading orders: " . $e->getMessage() . "</p>\n";
}

echo "<p><a href='checkout.php'>Go to Checkout</a></p>\n";
echo "<p><a href='user/orders.php'>View All Orders</a></p>\n";
?>
