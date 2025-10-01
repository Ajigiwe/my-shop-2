<?php
/**
 * Enhanced Search AJAX Handler with Category Support
 * Works with existing database structure
 */

// Simple database connection
try {
    $pdo = new PDO('mysql:host=localhost;dbname=ecommerce_db', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    // Return empty array if database connection fails
    echo json_encode([]);
    exit();
}

// Set JSON response header
header('Content-Type: application/json');

$query = $_GET['query'] ?? '';
$exact = isset($_GET['exact']) && $_GET['exact'] === 'true';

if (empty($query) || strlen($query) < 1) {
    echo json_encode([]);
    exit();
}

try {
    if ($exact) {
        // For exact matching - find products that contain the search term
        $stmt = $pdo->prepare("
            SELECT
                p.product_id as id,
                p.name,
                p.price,
                p.image,
                p.stock_quantity,
                c.category_name,
                p.average_rating,
                p.review_count
            FROM products p
            JOIN categories c ON p.category_id = c.category_id
            WHERE (LOWER(p.name) LIKE LOWER(?) OR LOWER(c.category_name) LIKE LOWER(?))
            AND p.stock_quantity > 0
            ORDER BY
                CASE
                    WHEN LOWER(p.name) = LOWER(?) THEN 1
                    WHEN LOWER(p.name) LIKE LOWER(?) THEN 2
                    WHEN LOWER(c.category_name) LIKE LOWER(?) THEN 3
                    ELSE 4
                END,
                p.name
            LIMIT 10
        ");

        $exact_match = $query;
        $starts_with = $query . '%';
        $contains = '%' . $query . '%';

        $stmt->execute([
            $contains, $contains, $exact_match, $starts_with, $starts_with
        ]);
    } else {
        // For autocomplete - search broadly in products and categories
        $stmt = $pdo->prepare("
            SELECT
                p.product_id as id,
                p.name,
                p.price,
                p.image,
                p.stock_quantity,
                c.category_name,
                p.average_rating,
                p.review_count
            FROM products p
            JOIN categories c ON p.category_id = c.category_id
            WHERE (p.name LIKE ? OR p.description LIKE ? OR c.category_name LIKE ?)
            AND p.stock_quantity > 0
            ORDER BY
                CASE
                    WHEN p.name LIKE ? THEN 1
                    WHEN p.name LIKE ? THEN 2
                    WHEN c.category_name LIKE ? THEN 3
                    ELSE 4
                END,
                p.name
            LIMIT 8
        ");

        $exact_match = $query;
        $starts_with = $query . '%';
        $contains = '%' . $query . '%';

        $stmt->execute([
            $contains, $contains, $contains,
            $exact_match, $starts_with, $starts_with
        ]);
    }

    $results = $stmt->fetchAll();

    // Format results for frontend
    $formatted_results = array_map(function($product) {
        return [
            'id' => $product['id'],
            'name' => $product['name'],
            'price' => $product['price'],
            'image' => $product['image'] ?: 'assets/images/placeholder.jpg',
            'category_name' => $product['category_name'],
            'stock_quantity' => $product['stock_quantity'],
            'average_rating' => $product['average_rating'] ?: 0,
            'review_count' => $product['review_count'] ?: 0,
            'in_stock' => $product['stock_quantity'] > 0
        ];
    }, $results);

    echo json_encode($formatted_results);

} catch(PDOException $e) {
    // Log error but return empty array
    error_log("Search error: " . $e->getMessage());
    echo json_encode([]);
}
?>
