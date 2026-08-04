<?php
/**
 * AJAX handler for quick product inline updates
 */

require_once '../../includes/db.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid form submission']);
    exit();
}

$product_id = (int)($_POST['product_id'] ?? 0);
if ($product_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid Product ID']);
    exit();
}

$price = (float)($_POST['price'] ?? 0);
$original_price = isset($_POST['original_price']) && $_POST['original_price'] !== '' ? (float)$_POST['original_price'] : null;
$stock_quantity = (int)($_POST['stock_quantity'] ?? 0);
$sku = isset($_POST['sku']) && trim($_POST['sku']) !== '' ? sanitizeInput(trim($_POST['sku'])) : null;
$status = in_array($_POST['status'] ?? '', ['published', 'draft']) ? $_POST['status'] : 'published';

if ($price < 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Price cannot be negative']);
    exit();
}

try {
    // Check if product exists
    $stmt = $pdo->prepare("SELECT product_id, sku FROM products WHERE product_id = ?");
    $stmt->execute([$product_id]);
    $existing = $stmt->fetch();

    if (!$existing) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit();
    }

    // Check SKU uniqueness if provided
    if (!empty($sku)) {
        $stmt = $pdo->prepare("SELECT product_id FROM products WHERE sku = ? AND product_id != ?");
        $stmt->execute([$sku, $product_id]);
        if ($stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'SKU already in use by another product']);
            exit();
        }
    }

    $stmt = $pdo->prepare("
        UPDATE products 
        SET price = ?, 
            original_price = ?, 
            stock_quantity = ?, 
            sku = ?, 
            status = ?, 
            updated_at = NOW() 
        WHERE product_id = ?
    ");
    $stmt->execute([$price, $original_price, $stock_quantity, $sku, $status, $product_id]);

    echo json_encode([
        'success' => true,
        'message' => 'Product updated successfully',
        'data' => [
            'product_id' => $product_id,
            'price' => number_format($price, 2, '.', ''),
            'original_price' => $original_price !== null ? number_format($original_price, 2, '.', '') : null,
            'stock_quantity' => $stock_quantity,
            'sku' => $sku,
            'status' => $status
        ]
    ]);
} catch (Exception $e) {
    error_log('Quick edit product error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
