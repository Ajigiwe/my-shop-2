<?php
// Set headers for JSON response
header('Content-Type: application/json');

// Include database connection
require_once '../includes/db.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get search query
$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$results = [];

// Debug log
file_put_contents('debug_autocomplete.log', "Search query: " . $query . "\n", FILE_APPEND);

if (!empty($query)) {
    try {
        // Prepare search query with positional parameters
        $sql = "SELECT product_id as id, name, price, image 
                FROM products 
                WHERE status = 'published'
                  AND (name LIKE ? 
                  OR description LIKE ?)
                ORDER BY name
                LIMIT 10";
        
        $stmt = $pdo->prepare($sql);
        $search_param = "%$query%";
        
        // Debug log
        file_put_contents('debug_autocomplete.log', "SQL: $sql\n", FILE_APPEND);
        file_put_contents('debug_autocomplete.log', "Search param: $search_param\n", FILE_APPEND);
        
        $stmt->execute([$search_param, $search_param]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Debug log
        file_put_contents('debug_autocomplete.log', "Results found: " . count($results) . "\n", FILE_APPEND);
        
    } catch(PDOException $e) {
        // Log error and return empty array
        $error = "Autocomplete Error: " . $e->getMessage();
        error_log($error);
        file_put_contents('debug_autocomplete.log', $error . "\n", FILE_APPEND);
    }
}

// Debug log
file_put_contents('debug_autocomplete.log', "Final results: " . json_encode($results) . "\n\n", FILE_APPEND);

// Return results as JSON
echo json_encode($results);
