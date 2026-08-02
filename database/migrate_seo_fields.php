<?php
/**
 * Migration: Add SEO fields to products table
 * Run with: php database/migrate_seo_fields.php
 */
require_once __DIR__ . '/../includes/db.php';

try {
    $pdo->exec("
        ALTER TABLE `products` 
        ADD COLUMN `meta_title` VARCHAR(255) NULL AFTER `description`,
        ADD COLUMN `meta_description` TEXT NULL AFTER `meta_title`,
        ADD COLUMN `slug` VARCHAR(255) NULL AFTER `meta_description`,
        ADD UNIQUE KEY `uk_products_slug` (`slug`)
    ");
    echo "Migration successful: meta_title, meta_description, slug added to products.\n";
} catch (PDOException $e) {
    if ($e->getCode() == '42S21' || strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "Columns already exist, skipping.\n";
    } else {
        echo "Migration failed: " . $e->getMessage() . "\n";
        exit(1);
    }
}