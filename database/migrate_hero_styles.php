<?php
/**
 * Migration: Add styling columns to hero_slides
 */
require_once __DIR__ . '/../includes/db.php';

try {
    // Add card_bg and text_color columns
    $pdo->exec("ALTER TABLE hero_slides ADD COLUMN IF NOT EXISTS card_bg VARCHAR(20) DEFAULT '#FFFFFF' AFTER image_path");
    $pdo->exec("ALTER TABLE hero_slides ADD COLUMN IF NOT EXISTS text_color VARCHAR(20) DEFAULT '#1A1A1A' AFTER card_bg");
    
    echo "Migration successful: card_bg and text_color columns added to hero_slides.";
} catch (PDOException $e) {
    die("Migration failed: " . $e->getMessage());
}
