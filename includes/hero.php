<?php
/**
 * Hero include — Avazonia hero slider driven by ASO `hero_slides`.
 * Renders template-split slides (text left, full-bleed image right).
 * Include from index.php.
 */
if (!function_exists('ensureHeroSlidesSchema')) {
    function ensureHeroSlidesSchema($pdo) {
        if (!$pdo) return;
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS hero_slides (
                id INT AUTO_INCREMENT PRIMARY KEY,
                badge_text VARCHAR(100) DEFAULT 'Featured This Week',
                title_black VARCHAR(255) NOT NULL,
                title_gray VARCHAR(255) DEFAULT '',
                description TEXT,
                button_text VARCHAR(100) DEFAULT 'Shop Now',
                button_link VARCHAR(255) DEFAULT 'shop.php',
                secondary_button_text VARCHAR(100) DEFAULT '',
                secondary_button_link VARCHAR(255) DEFAULT 'shop.php',
                image_path VARCHAR(255) NOT NULL,
                card_bg VARCHAR(50) DEFAULT '#FFFFFF',
                text_color VARCHAR(50) DEFAULT '#1A1A1A',
                display_order INT DEFAULT 0,
                is_active TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            $cols = [
                'secondary_button_text' => "ALTER TABLE hero_slides ADD COLUMN secondary_button_text VARCHAR(100) DEFAULT '' AFTER button_link",
                'secondary_button_link' => "ALTER TABLE hero_slides ADD COLUMN secondary_button_link VARCHAR(255) DEFAULT 'shop.php' AFTER secondary_button_text",
                'badge_text' => "ALTER TABLE hero_slides ADD COLUMN badge_text VARCHAR(100) DEFAULT 'Featured This Week' AFTER id",
                'title_gray' => "ALTER TABLE hero_slides ADD COLUMN title_gray VARCHAR(255) DEFAULT '' AFTER title_black",
                'card_bg' => "ALTER TABLE hero_slides ADD COLUMN card_bg VARCHAR(50) DEFAULT '#FFFFFF' AFTER image_path",
                'text_color' => "ALTER TABLE hero_slides ADD COLUMN text_color VARCHAR(50) DEFAULT '#1A1A1A' AFTER card_bg",
            ];
            foreach ($cols as $col => $sql) {
                try {
                    $check = $pdo->query("SHOW COLUMNS FROM hero_slides LIKE '$col'");
                    if ($check && $check->rowCount() === 0) {
                        $pdo->exec($sql);
                    }
                } catch (Throwable $e) {}
            }
        } catch (Throwable $e) {}
    }
}

$activeSlides = [];
if (isset($pdo)) {
    ensureHeroSlidesSchema($pdo);
    try {
        $activeSlides = $pdo->query("SELECT * FROM hero_slides WHERE is_active = 1 ORDER BY display_order ASC, id ASC")->fetchAll();
    } catch (PDOException $e) { $activeSlides = []; }
}
if (empty($activeSlides)) return;
?>
<section class="hero-slider">
    <?php foreach ($activeSlides as $index => $s): ?>
    <div class="hero-slide <?php echo $index === 0 ? 'active' : ''; ?> template-split">
        <div class="hero-left">
            <div class="sec-eyebrow">
                <span class="eyebrow-text"><?php echo htmlspecialchars($s['badge_text'] ?: 'Featured This Week'); ?></span>
                <span class="eyebrow-line"></span>
            </div>
            <h1 class="hero-heading"><?php echo htmlspecialchars($s['title_black']); ?></h1>
            <?php if (!empty($s['title_gray'])): ?>
            <p style="font-size: 22px; color: #fff; max-width: 520px; margin-bottom: 20px; line-height: 1.4; font-weight: 700;">
                <?php echo htmlspecialchars($s['title_gray']); ?>
            </p>
            <?php endif; ?>
            <?php if (!empty($s['description'])): ?>
            <p style="font-size: 15px; color: rgba(255,255,255,0.72); max-width: 460px; margin-bottom: 40px; line-height: 1.6; font-weight: 500;">
                <?php echo htmlspecialchars($s['description']); ?>
            </p>
            <?php else: ?>
            <div style="height: 40px;"></div>
            <?php endif; ?>
            <div style="display: flex; gap: 16px; flex-wrap: wrap;">
                <a href="<?php echo $base . ltrim(htmlspecialchars($s['button_link'] ?: 'shop.php'), '/'); ?>" class="btn-red"><?php echo htmlspecialchars($s['button_text'] ?: 'Shop Now'); ?> →</a>
                <?php if (!empty($s['secondary_button_text'])): ?>
                <a href="<?php echo $base . ltrim(htmlspecialchars($s['secondary_button_link'] ?: 'shop.php'), '/'); ?>" class="btn-hero-secondary"><?php echo htmlspecialchars($s['secondary_button_text']); ?></a>
                <?php endif; ?>
            </div>
        </div>
        <?php if (!empty($s['image_path'])): ?>
        <div class="hero-right">
            <img src="<?php echo htmlspecialchars(getProductImage($s['image_path'])); ?>" alt="<?php echo htmlspecialchars($s['title_black']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
            <div style="position: absolute; inset: 0; background: linear-gradient(90deg, rgba(13,13,13,0.92) 0%, rgba(13,13,13,0.45) 45%, rgba(13,13,13,0.15) 100%);"></div>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <?php if (count($activeSlides) > 1): ?>
    <div class="slider-dots">
        <?php foreach ($activeSlides as $index => $s): ?>
            <div class="dot <?php echo $index === 0 ? 'active' : ''; ?>" data-index="<?php echo $index; ?>"></div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</section>
