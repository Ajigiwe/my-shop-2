<?php
/**
 * Script to update the orders table structure
 * - Adds the email column if it doesn't exist
 */

// Include database connection
require_once 'includes/db.php';

try {
    // Check if email column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'email'");
    $columnExists = $stmt->rowCount() > 0;
    
    if (!$columnExists) {
        // Add the email column
        $pdo->exec("ALTER TABLE orders ADD COLUMN email VARCHAR(255) AFTER phone");
        echo "Successfully added 'email' column to the orders table.\n";
        
        // Update existing orders with user's email
        $pdo->exec("UPDATE orders o 
                    JOIN users u ON o.user_id = u.user_id 
                    SET o.email = u.email 
                    WHERE o.email IS NULL");
        echo "Updated existing orders with user email addresses.\n";
    } else {
        echo "The 'email' column already exists in the orders table.\n";
    }
    
    echo "Database update completed successfully.\n";
    
} catch (PDOException $e) {
    die("Error updating database: " . $e->getMessage() . "\n");
}

// Show the current table structure
echo "\nCurrent orders table structure:\n";
$stmt = $pdo->query("DESCRIBE orders");
$columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
print_r($columns);

echo "\nYou can now safely delete this file (update_orders_table.php).\n";
