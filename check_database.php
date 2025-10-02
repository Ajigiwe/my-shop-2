<?php
// Include database connection
require_once 'includes/db.php';

echo "<h2>Checking Database Structure</h2>";

// Check if tables exist
$tables = [
    'orders' => [
        'order_id', 'user_id', 'total_amount', 'status', 'payment_method',
        'shipping_address', 'billing_address', 'phone', 'email', 'notes', 'created_at'
    ],
    'order_items' => [
        'order_item_id', 'order_id', 'product_id', 'quantity', 'price', 'size', 'color'
    ],
    'products' => [
        'product_id', 'name', 'price', 'stock_quantity', 'image'
    ]
];

foreach ($tables as $table => $columns) {
    echo "<h3>Checking table: $table</h3>";
    
    try {
        // Check if table exists
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() === 0) {
            echo "<div style='color: red;'>Table '$table' does not exist!</div>";
            continue;
        }
        
        echo "<div style='color: green;'>Table '$table' exists.</div>";
        
        // Check columns
        $stmt = $pdo->query("DESCRIBE `$table`");
        $existing_columns = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $existing_columns[] = $row['Field'];
        }
        
        $missing_columns = array_diff($columns, $existing_columns);
        if (!empty($missing_columns)) {
            echo "<div style='color: orange;'>Missing columns in '$table': " . 
                 implode(', ', $missing_columns) . "</div>";
        } else {
            echo "<div style='color: green;'>All required columns exist in '$table'.</div>";
        }
        
        // Show sample data (first row)
        $stmt = $pdo->query("SELECT * FROM `$table` LIMIT 1");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<pre>Sample data: " . print_r($row, true) . "</pre>";
        
    } catch (PDOException $e) {
        echo "<div style='color: red;'>Error checking table '$table': " . 
             $e->getMessage() . "</div>";
    }
}

// Check if there's an active session
echo "<h3>Session Information</h3>";
session_start();
echo "<pre>" . print_r($_SESSION, true) . "</pre>";

// Check PHP error log
echo "<h3>PHP Error Log</h3>";
$error_log = ini_get('error_log');
if (file_exists($error_log)) {
    $log_content = file_get_contents($error_log);
    echo "<pre>" . htmlspecialchars(substr($log_content, -2000)) . "</pre>";
} else {
    echo "Error log not found at: $error_log";
}
?>
