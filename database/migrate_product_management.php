<?php
/**
 * Migration: Product management columns
 * - Adds sku, status, is_featured, low_stock_threshold to products
 * - Adds unique index on sku (allows multiple NULLs)
 */
require_once __DIR__ . '/../includes/db.php';

try {
    // SKU (unique, multiple NULLs allowed in MySQL)
    $pdo->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS sku VARCHAR(100) NULL AFTER name");
    $pdo->exec("ALTER TABLE products ADD UNIQUE KEY IF NOT EXISTS uq_products_sku (sku)");

    // Publish status (existing rows default to 'published' so nothing hides)
    $pdo->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS status ENUM('draft','published') NOT NULL DEFAULT 'published' AFTER stock_quantity");

    // Featured flag
    $pdo->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS is_featured TINYINT(1) NOT NULL DEFAULT 0 AFTER status");

    // Low stock threshold (NULL = use default of 5)
    $pdo->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS low_stock_threshold INT NULL AFTER stock_quantity");

    echo "Migration successful: sku, status, is_featured, low_stock_threshold added to products.";
} catch (PDOException $e) {
    // MySQL 5.7 doesn't support "ADD COLUMN IF NOT EXISTS" on all versions;
    // fall back to checking column existence manually.
    try {
        $cols = $pdo->query("SHOW COLUMNS FROM products")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('sku', $cols)) {
            $pdo->exec("ALTER TABLE products ADD COLUMN sku VARCHAR(100) NULL AFTER name");
            try { $pdo->exec("ALTER TABLE products ADD UNIQUE KEY uq_products_sku (sku)"); } catch (PDOException $e2) {}
        }
        if (!in_array('status', $cols)) {
            $pdo->exec("ALTER TABLE products ADD COLUMN status ENUM('draft','published') NOT NULL DEFAULT 'published' AFTER stock_quantity");
        }
        if (!in_array('is_featured', $cols)) {
            $pdo->exec("ALTER TABLE products ADD COLUMN is_featured TINYINT(1) NOT NULL DEFAULT 0 AFTER status");
        }
        if (!in_array('low_stock_threshold', $cols)) {
            $pdo->exec("ALTER TABLE products ADD COLUMN low_stock_threshold INT NULL AFTER stock_quantity");
        }
        echo "Migration successful (fallback path): product management columns added.";
    } catch (PDOException $e2) {
        die("Migration failed: " . $e2->getMessage());
    }
}
