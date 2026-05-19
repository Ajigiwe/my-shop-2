<?php
require_once __DIR__ . '/../includes/db.php';

try {
    $pdo->exec("ALTER TABLE promo_cards ADD COLUMN product_id INT NULL AFTER id");
    $pdo->exec("ALTER TABLE promo_cards ADD FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE SET NULL");
    echo "Migration successful: product_id column added to promo_cards.";
} catch (PDOException $e) {
    if ($e->getCode() == '42S21') {
        echo "Column already exists.";
    } else {
        die("Migration failed: " . $e->getMessage());
    }
}
?>
