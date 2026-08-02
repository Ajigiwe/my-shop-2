<?php
/**
 * Admin: Export Products to CSV
 * - Respects the same filters used on manage_products.php
 */
require_once '../includes/db.php';
session_start();
require_once '../includes/admin_guard.php';

// Read filters (same keys as manage_products.php)
$q = sanitizeInput($_GET['q'] ?? '');
$category_filter = (int)($_GET['category'] ?? 0);
$status_filter = sanitizeInput($_GET['status'] ?? '');
$stock_filter = sanitizeInput($_GET['stock'] ?? '');
$featured_filter = sanitizeInput($_GET['featured'] ?? '');

$where = ['1=1'];
$params = [];

if ($q !== '') {
    $where[] = '(p.name LIKE ? OR p.sku LIKE ? OR p.product_id = ?)';
    $params[] = "%$q%";
    $params[] = "%$q%";
    $params[] = (int)$q;
}
if ($category_filter > 0) {
    $where[] = 'p.category_id = ?';
    $params[] = $category_filter;
}
if ($status_filter === 'published' || $status_filter === 'draft') {
    $where[] = 'p.status = ?';
    $params[] = $status_filter;
}
if ($stock_filter === 'instock') {
    $where[] = 'p.stock_quantity > 0';
} elseif ($stock_filter === 'outofstock') {
    $where[] = 'p.stock_quantity <= 0';
} elseif ($stock_filter === 'lowstock') {
    $where[] = 'p.stock_quantity > 0 AND p.stock_quantity <= COALESCE(p.low_stock_threshold, 5)';
}
if ($featured_filter === 'yes') {
    $where[] = 'p.is_featured = 1';
} elseif ($featured_filter === 'no') {
    $where[] = 'p.is_featured = 0';
}

$where_sql = 'WHERE ' . implode(' AND ', $where);

// Fetch products with category, subcategory, and tags
$products = [];
try {
    $stmt = $pdo->prepare("SELECT p.product_id, p.name, p.sku, p.description, p.features,
                                  p.price, p.original_price, p.stock_quantity, p.low_stock_threshold,
                                  p.status, p.is_featured,
                                  c.category_name, s.subcategory_name,
                                  (SELECT GROUP_CONCAT(t.tag_name SEPARATOR ', ')
                                   FROM product_tag_relations r
                                   JOIN product_tags t ON r.tag_id = t.tag_id
                                   WHERE r.product_id = p.product_id) AS tags
                           FROM products p
                           JOIN categories c ON p.category_id = c.category_id
                           LEFT JOIN subcategories s ON p.subcategory_id = s.subcategory_id
                           $where_sql
                           ORDER BY p.product_id ASC");
    $stmt->execute($params);
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('CSV export query error: ' . $e->getMessage());
}

// Send CSV with UTF-8 BOM (Excel-friendly)
$filename = 'products-export-' . date('Y-m-d-His') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF");

$headers = [
    'product_id', 'name', 'sku', 'category', 'subcategory', 'price', 'original_price',
    'stock_quantity', 'low_stock_threshold', 'status', 'is_featured', 'description', 'features', 'tags'
];
fputcsv($out, $headers);

foreach ($products as $p) {
    fputcsv($out, [
        $p['product_id'],
        $p['name'],
        $p['sku'],
        $p['category_name'],
        $p['subcategory_name'] ?? '',
        $p['price'],
        $p['original_price'] ?? '',
        $p['stock_quantity'],
        $p['low_stock_threshold'] ?? '',
        $p['status'],
        (int)$p['is_featured'],
        $p['description'] ?? '',
        $p['features'] ?? '',
        $p['tags'] ?? ''
    ]);
}

fclose($out);
exit();
