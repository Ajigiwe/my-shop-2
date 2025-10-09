<?php
/**
 * Fallback Database Connection
 * Uses file-based storage when MySQL is not available
 */

// Simple file-based order storage
function saveOrderToFile($order_data) {
    $orders_file = 'data/orders.json';
    
    // Create data directory if it doesn't exist
    if (!file_exists('data')) {
        mkdir('data', 0755, true);
    }
    
    // Load existing orders
    $orders = [];
    if (file_exists($orders_file)) {
        $content = file_get_contents($orders_file);
        if ($content) {
            $orders = json_decode($content, true) ?: [];
        }
    }
    
    // Add new order
    $orders[] = $order_data;
    
    // Save back to file
    return file_put_contents($orders_file, json_encode($orders, JSON_PRETTY_PRINT));
}

function getOrdersFromFile($user_id = null) {
    $orders_file = 'data/orders.json';
    
    if (!file_exists($orders_file)) {
        return [];
    }
    
    $content = file_get_contents($orders_file);
    if (!$content) {
        return [];
    }
    
    $orders = json_decode($content, true) ?: [];
    
    // Filter by user if specified
    if ($user_id !== null) {
        $orders = array_filter($orders, function($order) use ($user_id) {
            return $order['user_id'] == $user_id;
        });
    }
    
    return array_values($orders);
}

function getOrderFromFile($order_id, $user_id = null) {
    $orders = getOrdersFromFile($user_id);
    
    foreach ($orders as $order) {
        if ($order['order_id'] == $order_id) {
            return $order;
        }
    }
    
    return null;
}

// Mock database connection function
function getDbConnection() {
    return 'file_based';
}

// Mock prepared statement functions
function mysqli_prepare($conn, $query) {
    return 'mock_stmt';
}

function mysqli_stmt_bind_param($stmt, $types, ...$params) {
    return true;
}

function mysqli_stmt_execute($stmt) {
    return true;
}

function mysqli_insert_id($conn) {
    return time(); // Return a mock ID
}

function mysqli_fetch_assoc($result) {
    return false;
}

function mysqli_fetch_all($result, $mode) {
    return [];
}

function mysqli_num_rows($result) {
    return 0;
}

function mysqli_query($conn, $query) {
    return 'mock_result';
}

function mysqli_close($conn) {
    return true;
}
?>
