<?php
/**
 * Migration: Variable products (attributes, variations)
 * Run with: php database/migrate_variable_products.php
 */
require_once __DIR__ . '/../includes/db.php';

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `product_attributes` (
            `attribute_id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(100) NOT NULL,
            `slug` VARCHAR(100) NOT NULL,
            `type` ENUM('select','color','size','text') NOT NULL DEFAULT 'select',
            `position` INT NOT NULL DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `uk_pa_slug` (`slug`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "Table product_attributes created.\n";
} catch (PDOException $e) {
    if ($e->getCode() == '42S01') {
        echo "Table product_attributes already exists, skipping.\n";
    } else {
        echo "Error creating product_attributes: " . $e->getMessage() . "\n";
    }
}

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `product_attribute_terms` (
            `term_id` INT AUTO_INCREMENT PRIMARY KEY,
            `attribute_id` INT NOT NULL,
            `name` VARCHAR(100) NOT NULL,
            `slug` VARCHAR(100) NOT NULL,
            `color_hex` VARCHAR(7) NULL,
            `position` INT NOT NULL DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`attribute_id`) REFERENCES `product_attributes`(`attribute_id`) ON DELETE CASCADE,
            UNIQUE KEY `uk_pat_slug_attr` (`slug`,`attribute_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "Table product_attribute_terms created.\n";
} catch (PDOException $e) {
    if ($e->getCode() == '42S01') {
        echo "Table product_attribute_terms already exists, skipping.\n";
    } else {
        echo "Error creating product_attribute_terms: " . $e->getMessage() . "\n";
    }
}

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `product_variations` (
            `variation_id` INT AUTO_INCREMENT PRIMARY KEY,
            `product_id` INT NOT NULL,
            `sku` VARCHAR(100) NULL,
            `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `original_price` DECIMAL(10,2) NULL,
            `stock_quantity` INT NOT NULL DEFAULT 0,
            `low_stock_threshold` INT NULL DEFAULT 5,
            `image` VARCHAR(255) NULL,
            `is_default` TINYINT(1) NOT NULL DEFAULT 0,
            `position` INT NOT NULL DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`product_id`) REFERENCES `products`(`product_id`) ON DELETE CASCADE,
            UNIQUE KEY `uk_pv_sku` (`sku`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "Table product_variations created.\n";
} catch (PDOException $e) {
    if ($e->getCode() == '42S01') {
        echo "Table product_variations already exists, skipping.\n";
    } else {
        echo "Error creating product_variations: " . $e->getMessage() . "\n";
    }
}

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `product_variation_images` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `variation_id` INT NOT NULL,
            `image_path` VARCHAR(255) NOT NULL,
            `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
            `display_order` INT NOT NULL DEFAULT 0,
            FOREIGN KEY (`variation_id`) REFERENCES `product_variations`(`variation_id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "Table product_variation_images created.\n";
} catch (PDOException $e) {
    if ($e->getCode() == '42S01') {
        echo "Table product_variation_images already exists, skipping.\n";
    } else {
        echo "Error creating product_variation_images: " . $e->getMessage() . "\n";
    }
}

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `product_attribute_relations` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `product_id` INT NOT NULL,
            `attribute_id` INT NOT NULL,
            `term_id` INT NOT NULL,
            FOREIGN KEY (`product_id`) REFERENCES `products`(`product_id`) ON DELETE CASCADE,
            FOREIGN KEY (`attribute_id`) REFERENCES `product_attributes`(`attribute_id`) ON DELETE CASCADE,
            FOREIGN KEY (`term_id`) REFERENCES `product_attribute_terms`(`term_id`) ON DELETE CASCADE,
            UNIQUE KEY `uk_par_product_attr_term` (`product_id`,`attribute_id`,`term_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "Table product_attribute_relations created.\n";
} catch (PDOException $e) {
    if ($e->getCode() == '42S01') {
        echo "Table product_attribute_relations already exists, skipping.\n";
    } else {
        echo "Error creating product_attribute_relations: " . $e->getMessage() . "\n";
    }
}

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `product_variation_term_relations` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `variation_id` INT NOT NULL,
            `attribute_id` INT NOT NULL,
            `term_id` INT NOT NULL,
            FOREIGN KEY (`variation_id`) REFERENCES `product_variations`(`variation_id`) ON DELETE CASCADE,
            FOREIGN KEY (`attribute_id`) REFERENCES `product_attributes`(`attribute_id`) ON DELETE CASCADE,
            FOREIGN KEY (`term_id`) REFERENCES `product_attribute_terms`(`term_id`) ON DELETE CASCADE,
            UNIQUE KEY `uk_pvtr_variation_attr_term` (`variation_id`,`attribute_id`,`term_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "Table product_variation_term_relations created.\n";
} catch (PDOException $e) {
    if ($e->getCode() == '42S01') {
        echo "Table product_variation_term_relations already exists, skipping.\n";
    } else {
        echo "Error creating product_variation_term_relations: " . $e->getMessage() . "\n";
    }
}

echo "\nMigration complete.\n";