<?php
/**
 * Migration: Create hero_slides table
 */
require_once __DIR__ . '/../includes/db.php';

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS hero_slides (
        id INT AUTO_INCREMENT PRIMARY KEY,
        badge_text VARCHAR(50),
        title_black VARCHAR(100) NOT NULL,
        title_gray VARCHAR(100),
        description TEXT,
        button_text VARCHAR(50) DEFAULT 'Shop Now',
        button_link VARCHAR(255) DEFAULT 'shop.php',
        secondary_button_text VARCHAR(50),
        secondary_button_link VARCHAR(255),
        image_path VARCHAR(255) NOT NULL,
        display_order INT DEFAULT 0,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // Check if empty and insert defaults
    $stmt = $pdo->query("SELECT COUNT(*) FROM hero_slides");
    if ($stmt->fetchColumn() == 0) {
        $stmt = $pdo->prepare("INSERT INTO hero_slides (badge_text, title_black, title_gray, description, button_text, button_link, secondary_button_text, secondary_button_link, image_path, display_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $slides = [
            ['Innovation First', 'Future of ', 'Technology.', 'Discover our curated collection of cutting-edge hardware designed to elevate your digital experience.', 'Shop Tech', 'shop.php', 'Learn More', 'about.php', 'assets/images/hero_tech.png', 1],
            ['Freshness Guaranteed', 'Organic & ', 'Natural.', 'We source only the finest organic produce directly from local farms to ensure your kitchen is always vibrant.', 'Explore Fresh', 'shop.php', 'Our Farms', 'shop.php', 'assets/images/hero_groceries.png', 2],
            ['Modern Living', 'Elevate Your ', 'Space.', 'Premium lifestyle essentials and smart home integration that blends seamlessly with your aesthetic.', 'Shop Lifestyle', 'shop.php', 'Inspiration', 'about.php', 'assets/images/hero_lifestyle.png', 3]
        ];

        foreach ($slides as $slide) {
            $stmt->execute($slide);
        }
    }

    echo "Migration successful: hero_slides table created and populated.";
} catch (PDOException $e) {
    die("Migration failed: " . $e->getMessage());
}
