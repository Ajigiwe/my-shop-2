<?php
/**
 * Admin: Export Orders (CSV)
 * - Streams orders as CSV to the browser for download
 * - Includes item count via a subquery; suitable for spreadsheet analysis
 */
require_once '../includes/db.php';
session_start();
require_once '../includes/admin_guard.php';

header('Content-Type: text/csv; charset=utf-8');
$filename = 'orders_' . date('Ymd_His') . '.csv';
header('Content-Disposition: attachment; filename=' . $filename);

$output = fopen('php://output', 'w');

// CSV header
fputcsv($output, [
    'Order ID', 'Order Date', 'Customer Name', 'Customer Email', 'Status', 'Payment Method', 'Total Amount',
    'Shipping Address', 'Billing Address', 'Item Count'
]);

try {
    // Query orders with customer info and item count
    $sql = "SELECT o.*, u.name AS customer_name, u.email AS customer_email,
                   (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.order_id) AS item_count
            FROM orders o
            JOIN users u ON u.user_id = o.user_id
            ORDER BY o.order_date DESC";
    $stmt = $pdo->query($sql);
    while ($row = $stmt->fetch()) {
        fputcsv($output, [
            $row['order_id'],
            $row['order_date'],
            $row['customer_name'],
            $row['customer_email'],
            $row['status'],
            $row['payment_method'],
            $row['total_amount'],
            // Normalize whitespace for multi-line addresses
            preg_replace('/\s+/', ' ', trim($row['shipping_address'])),
            preg_replace('/\s+/', ' ', trim($row['billing_address'])),
            $row['item_count']
        ]);
    }
} catch (PDOException $e) {
    // Output a line explaining the error in CSV
    fputcsv($output, ['ERROR', $e->getMessage()]);
}

fclose($output);
exit;

