<?php
/**
 * Migration: Align products and product_images tables with application logic
 */
require_once __DIR__ . '/../includes/db.php';

try {
    $pdo->beginTransaction();

    // 1. Update product_images table (rename is_main to is_primary if needed)
    // Check if is_main exists
    $stmt = $pdo->query("SHOW COLUMNS FROM product_images LIKE 'is_main'");
    if ($stmt->fetch()) {
        $pdo->exec("ALTER TABLE product_images CHANGE is_main is_primary TINYINT(1) DEFAULT 0");
    }

    // 2. Add columns to products table
    $stmt = $pdo->query("SHOW COLUMNS FROM products LIKE 'has_multiple_images'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE products ADD COLUMN has_multiple_images TINYINT(1) DEFAULT 0 AFTER image");
    }

    $stmt = $pdo->query("SHOW COLUMNS FROM products LIKE 'main_image_id'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE products ADD COLUMN main_image_id INT NULL AFTER has_multiple_images");
    }

    // 3. Update main_image_id for existing products
    $pdo->exec("UPDATE products p 
                SET p.main_image_id = (SELECT image_id FROM product_images WHERE product_id = p.product_id AND is_primary = 1 LIMIT 1),
                    p.has_multiple_images = (SELECT COUNT(*) > 1 FROM product_images WHERE product_id = p.product_id)");

    $pdo->commit();
    echo "Migration successful: Database schema aligned with application logic.";
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    die("Migration failed: " . $e->getMessage());
}
