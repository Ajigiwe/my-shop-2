<?php
/**
 * Migration: Inventory movements table
 * Run with: php database/migrate_inventory_movements.php
 */
require_once __DIR__ . '/../includes/db.php';

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `inventory_movements` (
            `movement_id` INT AUTO_INCREMENT PRIMARY KEY,
            `product_id` INT NOT NULL,
            `variation_id` INT NULL,
            `type` ENUM('receive','adjustment','sale','return','transfer') NOT NULL DEFAULT 'adjustment',
            `quantity` INT NOT NULL,
            `quantity_before` INT NOT NULL DEFAULT 0,
            `quantity_after` INT NOT NULL DEFAULT 0,
            `reference_type` VARCHAR(50) NULL,
            `reference_id` INT NULL,
            `notes` TEXT NULL,
            `created_by` INT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`product_id`) REFERENCES `products`(`product_id`) ON DELETE CASCADE,
            FOREIGN KEY (`variation_id`) REFERENCES `product_variations`(`variation_id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "Table inventory_movements created.\n";
} catch (PDOException $e) {
    if ($e->getCode() == '42S01') {
        echo "Table inventory_movements already exists, skipping.\n";
    } else {
        echo "Error creating inventory_movements: " . $e->getMessage() . "\n";
    }
}

echo "\nMigration complete.\n";