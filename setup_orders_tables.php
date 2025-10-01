<?php
/**
 * Setup Orders Tables
 * Run this script to create the necessary tables for the orders system
 */

// Include database connection
require_once 'includes/db.php';

try {
    // Read and execute the SQL file
    $sql = file_get_contents('orders_tables.sql');

    // Split by semicolon to execute each statement separately
    $statements = array_filter(array_map('trim', explode(';', $sql)));

    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $pdo->exec($statement);
        }
    }

    echo "<h2>✅ Orders Tables Created Successfully!</h2>";
    echo "<p>The following tables have been created:</p>";
    echo "<ul>";
    echo "<li><strong>orders</strong> - Main orders table</li>";
    echo "<li><strong>order_items</strong> - Order line items</li>";
    echo "<li><strong>order_status_updates</strong> - Status change tracking</li>";
    echo "</ul>";
    echo "<p><a href='index.php' class='btn btn-primary'>Back to Home</a></p>";

} catch(PDOException $e) {
    echo "<h2>❌ Error Creating Tables</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<p><a href='index.php' class='btn btn-secondary'>Back to Home</a></p>";
}
?>
