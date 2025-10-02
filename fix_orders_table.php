<?php
/**
 * Script to fix the orders table structure
 */

// Database configuration
$host = 'localhost';
$dbname = 'my_shop';
$username = 'root';
$password = '';

// Create connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

try {
    // Add the email column if it doesn't exist
    $sql = "ALTER TABLE orders 
            ADD COLUMN IF NOT EXISTS email VARCHAR(255) NULL DEFAULT NULL AFTER phone";
    
    if ($conn->query($sql) === TRUE) {
        echo "Successfully added 'email' column to the orders table.\n";
    } else {
        echo "Error adding column: " . $conn->error . "\n";
    }
    
    // Show the current table structure
    echo "\nCurrent orders table structure:\n";
    $result = $conn->query("DESCRIBE orders");
    
    if ($result->num_rows > 0) {
        echo str_pad("Field", 20) . str_pad("Type", 20) . str_pad("Null", 10) . "Key\n";
        echo str_repeat("-", 60) . "\n";
        
        while($row = $result->fetch_assoc()) {
            echo str_pad($row['Field'], 20) . 
                 str_pad($row['Type'], 20) . 
                 str_pad($row['Null'], 10) . 
                 ($row['Key'] ?? '') . "\n";
        }
    } else {
        echo "No columns found in the orders table.\n";
    }
    
    echo "\nYou can now safely delete this file (fix_orders_table.php).\n";
    
} catch (Exception $e) {
    die("Error updating database: " . $e->getMessage() . "\n");
} finally {
    // Close connection
    $conn->close();
}
