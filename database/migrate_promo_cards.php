<?php
/**
 * Migration: Create promo_cards table
 */
require_once __DIR__ . '/../includes/db.php';

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS promo_cards (
        id INT AUTO_INCREMENT PRIMARY KEY,
        badge_text VARCHAR(50),
        title VARCHAR(100) NOT NULL,
        subtitle VARCHAR(100),
        price_text VARCHAR(50),
        button_text VARCHAR(50) DEFAULT 'Buy Now',
        button_link VARCHAR(255) DEFAULT 'shop.php',
        image_path VARCHAR(255) NOT NULL,
        card_bg VARCHAR(20) DEFAULT '#F2F4F7',
        text_color VARCHAR(20) DEFAULT '#0D0D0D',
        badge_color VARCHAR(20) DEFAULT '#55514E',
        is_button BOOLEAN DEFAULT TRUE,
        display_order INT DEFAULT 0,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // Insert current hardcoded cards as defaults
    $stmt = $pdo->query("SELECT COUNT(*) FROM promo_cards");
    if ($stmt->fetchColumn() == 0) {
        $stmt = $pdo->prepare("INSERT INTO promo_cards (badge_text, title, subtitle, price_text, button_text, button_link, image_path, card_bg, text_color, badge_color, is_button, display_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $cards = [
            ['Big Saving', 'Galaxy S13 Lite.', 'From', 'GH₵429.90', 'Buy Now', 'shop.php', 'assets/images/galaxy_s13.png', '#F2F4F7', '#0D0D0D', '#55514E', 1, 1],
            ['10% Off', 'Smartwatch 7.', 'From', 'GH₵379.00', 'Learn More', 'shop.php', 'assets/images/smartwatch_7.png', '#FBE4E7', '#0D0D0D', '#B05B6F', 0, 2],
            ['Smart Home', 'Five Bold Colors.', 'From', 'GH₵229.00', 'Buy Now', 'shop.php', 'assets/images/smart_home.png', '#FEF3C7', '#0D0D0D', '#92400E', 1, 3],
            ['Best Price', '5th Gen AirPods.', 'From', 'GH₵499.30', 'Learn More', 'shop.php', 'assets/images/airpods_5.png', '#F8FAFC', '#0D0D0D', '#64748B', 0, 4],
            ['Flat 20% Off', 'Headset Max 3.', 'From', 'GH₵649.00', 'Buy Now', 'shop.php', 'assets/images/headset_max3.png', '#EFF6FF', '#0D0D0D', '#1E40AF', 1, 5],
            ['Newly Added', 'MacBook Pro.', 'From', 'GH₵2499', 'Learn More', 'shop.php', 'assets/images/macbook_pro.png', '#F5F3FF', '#0D0D0D', '#5B21B6', 0, 6]
        ];

        foreach ($cards as $card) {
            $stmt->execute($card);
        }
    }

    echo "Migration successful: promo_cards table created and populated.";
} catch (PDOException $e) {
    die("Migration failed: " . $e->getMessage());
}
