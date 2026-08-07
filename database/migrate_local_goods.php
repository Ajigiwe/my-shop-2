<?php
/**
 * Migration: Made in Ghana local goods section
 * - Adds `product_section` column to products (main | local)
 * - Creates `shipping_zones` table (domestic + international flat-rate zones)
 * - Adds shipping zone/country columns to orders
 * Run with: php database/migrate_local_goods.php
 */
require_once __DIR__ . '/../includes/db.php';

// 1) product_section column
try {
    $pdo->exec("
        ALTER TABLE `products`
        ADD COLUMN `product_section` ENUM('main','local') NOT NULL DEFAULT 'main' AFTER `category_id`
    ");
    echo "OK: product_section column added to products.\n";
} catch (PDOException $e) {
    if ($e->getCode() == '42S21' || strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "SKIP: product_section already exists.\n";
    } else {
        echo "ERROR (products.product_section): " . $e->getMessage() . "\n";
    }
}

// 2) shipping_zones table
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `shipping_zones` (
            `zone_id` INT AUTO_INCREMENT PRIMARY KEY,
            `zone_name` VARCHAR(120) NOT NULL,
            `zone_type` ENUM('domestic','international') NOT NULL DEFAULT 'domestic',
            `country_codes` TEXT NULL COMMENT 'JSON array of ISO-2 codes; NULL/empty = all domestic regions for type=domestic',
            `flat_rate` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `free_threshold` DECIMAL(10,2) NULL COMMENT 'Subtotal at which shipping is free for this zone',
            `estimated_days` VARCHAR(60) NULL,
            `flag_emoji` VARCHAR(10) NULL,
            `sort_order` INT NOT NULL DEFAULT 0,
            `is_active` TINYINT(1) NOT NULL DEFAULT 1,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "OK: shipping_zones table created.\n";
} catch (PDOException $e) {
    echo "ERROR (shipping_zones): " . $e->getMessage() . "\n";
}

// 3) orders columns
try {
    $pdo->exec("
        ALTER TABLE `orders`
        ADD COLUMN `shipping_zone_id` INT NULL AFTER `phone`,
        ADD COLUMN `country` VARCHAR(100) NULL AFTER `shipping_zone_id`,
        ADD COLUMN `shipping_label` VARCHAR(160) NULL AFTER `country`
    ");
    echo "OK: shipping_zone_id, country, shipping_label columns added to orders.\n";
} catch (PDOException $e) {
    if ($e->getCode() == '42S21' || strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "SKIP: orders shipping columns already exist.\n";
    } else {
        echo "ERROR (orders shipping columns): " . $e->getMessage() . "\n";
    }
}

// 4) Seed default shipping zones (only when table is empty)
try {
    $count = (int)$pdo->query("SELECT COUNT(*) FROM shipping_zones")->fetchColumn();
    if ($count === 0) {
        // Pull current domestic fees from site_settings so nothing changes for existing shoppers
        $fee = [];
        try {
            $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key LIKE 'shipping_%'");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $fee[$row['setting_key']] = (float)$row['setting_value'];
            }
        } catch (PDOException $e) {}
        $free_threshold = isset($fee['free_shipping_threshold']) ? (float)$fee['free_shipping_threshold'] : 500;

        $zones = [
            // Domestic (mirror current hardcoded zones in checkout.php / cart.php)
            ['Accra & Greater Accra',     'domestic', null,           isset($fee['shipping_accra'])  ? $fee['shipping_accra']  : 15, $free_threshold, '1–2 days',  '📍', 1],
            ['Kumasi / Takoradi',         'domestic', null,           isset($fee['shipping_kumasi']) ? $fee['shipping_kumasi'] : 25, $free_threshold, '2–3 days',  '📍', 2],
            ['All Other Regions',         'domestic', null,           isset($fee['shipping_others']) ? $fee['shipping_others'] : 60, $free_threshold, '3–5 days',  '📍', 3],
            ['Store Pickup',              'domestic', null,           isset($fee['shipping_pickup']) ? $fee['shipping_pickup'] : 0,  null, 'Ready when you are', '🏪', 4],
            // International (flat-rate zones; editable in admin)
            ['Africa',                    'international', '["DZ","EG","KE","NG","ZA","TZ"]', 350, null, '7–14 days', '🌍', 5],
            ['Europe',                    'international', '["GB","DE","FR","NL","IT","ES"]',  700, null, '10–18 days', '🇪🇺', 6],
            ['North America',             'international', '["US","CA","MX"]',                850, null, '10–18 days', '🇺🇸', 7],
            ['Rest of World',             'international', null,                               950, null, '14–21 days', '🌎', 8],
        ];

        $stmt = $pdo->prepare("
            INSERT INTO shipping_zones (zone_name, zone_type, country_codes, flat_rate, free_threshold, estimated_days, flag_emoji, sort_order)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        foreach ($zones as $z) {
            $stmt->execute($z);
        }
        echo "OK: seeded " . count($zones) . " shipping zones.\n";
    } else {
        echo "SKIP: shipping_zones already populated.\n";
    }
} catch (PDOException $e) {
    echo "ERROR (seed shipping_zones): " . $e->getMessage() . "\n";
}

// 5) ad_banners table (homepage ad slider)
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `ad_banners` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `image_path` VARCHAR(255) NOT NULL,
            `title` VARCHAR(255) DEFAULT '',
            `description` TEXT,
            `button_text` VARCHAR(100) DEFAULT 'Shop Now',
            `button_link` VARCHAR(255) DEFAULT 'shop.php',
            `display_order` INT DEFAULT 0,
            `is_active` TINYINT(1) NOT NULL DEFAULT 1,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "OK: ad_banners table created.\n";
} catch (PDOException $e) {
    echo "ERROR (ad_banners): " . $e->getMessage() . "\n";
}

echo "\nMigration complete.\n";
