<?php
/**
 * Search Suggestions API
 * - Returns a list of products matching the search query for autocomplete
 */
header('Content-Type: application/json');
require_once '../includes/db.php';

$query = sanitizeInput($_GET['q'] ?? '');

if (strlen($query) < 2) {
    echo json_encode([]);
    exit();
}

try {
    // Fetch up to 5 matching products
    $stmt = $pdo->prepare("
        SELECT product_id, name, price, image 
        FROM products 
        WHERE status = 'published'
          AND (name LIKE ? OR description LIKE ?) 
        LIMIT 5
    ");
    $stmt->execute(["%$query%", "%$query%"]);
    $products = $stmt->fetchAll();

    $suggestions = [];
    foreach ($products as $p) {
        $suggestions[] = [
            'id' => $p['product_id'],
            'name' => $p['name'],
            'price' => formatCurrency($p['price']),
            'image' => $p['image'] ?? 'placeholder.jpg',
            'image_url' => getProductImage($p['image'] ?? 'placeholder.jpg'),
            'url' => 'product.php?id=' . $p['product_id']
        ];
    }

    echo json_encode($suggestions);
} catch (PDOException $e) {
    error_log("Search suggestions error: " . $e->getMessage());
    echo json_encode([]);
}
