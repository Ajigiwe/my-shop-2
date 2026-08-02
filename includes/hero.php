<?php
/**
 * Hero include — Avazonia hero slider driven by ASO `hero_slides`.
 * Renders template-split slides (text left, full-bleed image right).
 * Include from index.php.
 */
$activeSlides = [];
if (isset($pdo)) {
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
                <a href="<?php echo $base . ltrim(htmlspecialchars($s['secondary_button_link'] ?: 'shop.php'), '/'); ?>" class="btn-ghost" style="background: transparent; color: #fff; border: 2px solid rgba(255,255,255,0.4);"><?php echo htmlspecialchars($s['secondary_button_text']); ?></a>
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
