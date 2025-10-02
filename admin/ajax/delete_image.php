<?php
/**
 * AJAX handler for deleting a product image
 */

require_once '../../includes/db.php';
require_once '../includes/product_images.php';

header('Content-Type: application/json');

// Check if user is logged in and is admin
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get and validate input
$image_id = (int)($_POST['image_id'] ?? 0);
$product_id = (int)($_POST['product_id'] ?? 0);

if ($image_id <= 0 || $product_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

try {
    $productImages = new ProductImages($pdo);
    
    // Verify the image belongs to the product
    $stmt = $pdo->prepare("SELECT image_path, is_primary FROM product_images WHERE image_id = ? AND product_id = ?");
    $stmt->execute([$image_id, $product_id]);
    $image = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$image) {
        throw new Exception('Image not found for this product');
    }
    
    // Don't allow deleting the last image
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM product_images WHERE product_id = ?");
    $stmt->execute([$product_id]);
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($count <= 1) {
        throw new Exception('Cannot delete the last image of a product');
    }
    
    // Delete the image
    $success = $productImages->deleteImage($image_id, $product_id);
    
    if ($success) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Failed to delete image');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
