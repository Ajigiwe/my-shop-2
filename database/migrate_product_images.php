<?php
/**
 * Migration: Create product_images table and migrate existing main images
 */
require_once __DIR__ . '/../includes/db.php';

try {
    // 1. Create the product_images table
    $pdo->exec("CREATE TABLE IF NOT EXISTS product_images (
        image_id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        image_path VARCHAR(255) NOT NULL,
        is_main BOOLEAN DEFAULT FALSE,
        display_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // 2. Migrate existing images from products.image column
    $stmt = $pdo->query("SELECT product_id, image FROM products WHERE image IS NOT NULL AND image != ''");
    $products = $stmt->fetchAll();

    $insertStmt = $pdo->prepare("INSERT INTO product_images (product_id, image_path, is_main, display_order) VALUES (?, ?, TRUE, 0)");
    
    foreach ($products as $product) {
        // Check if image already exists in product_images to avoid duplicates on re-run
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM product_images WHERE product_id = ? AND image_path = ?");
        $checkStmt->execute([$product['product_id'], $product['image']]);
        if ($checkStmt->fetchColumn() == 0) {
            $insertStmt->execute([$product['product_id'], $product['image']]);
        }
    }

    echo "Migration successful: product_images table created and existing images migrated.";
} catch (PDOException $e) {
    die("Migration failed: " . $e->getMessage());
}
