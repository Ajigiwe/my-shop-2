<?php
require_once __DIR__ . '/../includes/db.php';

try {
    $pdo->exec("
        ALTER TABLE `products`
        ADD COLUMN `alert_email` VARCHAR(255) NULL AFTER `slug`,
        ADD COLUMN `alert_enabled` TINYINT(1) NOT NULL DEFAULT 1 AFTER `alert_email`
    ");
    echo "Added alert_email and alert_enabled columns to products.\n";
} catch (PDOException $e) {
    if ($e->getCode() == '42S21' || strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "Columns already exist, skipping.\n";
    } else {
        echo "Error adding alert columns: " . $e->getMessage() . "\n";
    }
}

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `stock_alert_log` (
            `alert_id` INT AUTO_INCREMENT PRIMARY KEY,
            `product_id` INT NOT NULL,
            `variation_id` INT NULL,
            `threshold` INT NOT NULL DEFAULT 5,
            `current_stock` INT NOT NULL DEFAULT 0,
            `notified` TINYINT(1) NOT NULL DEFAULT 0,
            `notified_at` TIMESTAMP NULL,
            `resolved` TINYINT(1) NOT NULL DEFAULT 0,
            `resolved_at` TIMESTAMP NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`product_id`) REFERENCES `products`(`product_id`) ON DELETE CASCADE,
            FOREIGN KEY (`variation_id`) REFERENCES `product_variations`(`variation_id`) ON DELETE CASCADE,
            KEY `idx_product` (`product_id`),
            KEY `idx_notified` (`notified`),
            KEY `idx_resolved` (`resolved`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "Table stock_alert_log created.\n";
} catch (PDOException $e) {
    if ($e->getCode() == '42S01') {
        echo "Table stock_alert_log already exists, skipping.\n";
    } else {
        echo "Error creating stock_alert_log: " . $e->getMessage() . "\n";
    }
}

echo "\nMigration complete.\n";