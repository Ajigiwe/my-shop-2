<?php
/**
 * includes/product-card.php
 * Reusable product card component (Avazonia style).
 * Expects: $p (product array), $wishlistIds (array of wishlist product ids)
 */
$p_id       = (int)($p['id'] ?? $p['product_id'] ?? 0);
$p_name     = $p['name'] ?? 'Product';
$p_cat      = $p['category_name'] ?? 'Gadget';
$p_price    = (float)($p['price_ghs'] ?? $p['price'] ?? 0);
$p_compare  = (float)($p['compare_at_price_ghs'] ?? $p['original_price'] ?? 0);
$p_stock    = (int)($p['stock_qty'] ?? $p['stock_quantity'] ?? 0);
$p_rating   = round((float)($p['avg_rating'] ?? $p['average_rating'] ?? 0));
$is_preorder = (bool)($p['is_preorder'] ?? false);
$is_drop    = (bool)($p['is_dropshipping'] ?? false);
$is_new     = (bool)($p['is_new_arrival'] ?? false);

$imgUrl = getProductImage($p['primary_image'] ?? $p['image'] ?? '');

global $dbSettings;
$sliderEnabled = !isset($dbSettings['product_card_slider_enabled']) || $dbSettings['product_card_slider_enabled'] == '1';

$processedCardImages = [];
if ($sliderEnabled && !empty($p['has_multiple_images']) && isset($pdo)) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT image_path FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, display_order ASC, image_id ASC LIMIT 5");
        $stmt->execute([$p_id]);
        $raw = $stmt->fetchAll(PDO::FETCH_COLUMN);
        foreach ($raw as $url) {
            $processedCardImages[] = getProductImage($url);
        }
    } catch (PDOException $e) {}
}
if (empty($processedCardImages)) {
    $processedCardImages[] = $imgUrl;
}
?>
<div class="card">
    <a href="product.php?id=<?= $p_id ?>" class="card-action-arrow" aria-label="View Product">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
    </a>

    <a href="product.php?id=<?= $p_id ?>" class="card-link-block">
        <div class="card-img-wrap">
            <?php if ($p_stock <= 0 && !$is_preorder && !$is_drop): ?>
                <span class="card-tag outofstock">OUT OF STOCK</span>
            <?php elseif ($is_preorder): ?>
                <span class="card-tag preorder">PRE-ORDER</span>
            <?php elseif ($p_stock > 0 && $p_stock <= 5 && !$is_preorder && !$is_drop): ?>
                <span class="card-tag lowstock">ONLY <?= (int)$p_stock ?> LEFT</span>
            <?php elseif ($p_compare > $p_price): ?>
                <span class="card-tag discount">HOT</span>
            <?php elseif ($is_new): ?>
                <span class="card-tag new">NEW</span>
            <?php endif; ?>

            <div class="card-img <?= $sliderEnabled && count($processedCardImages) > 1 ? 'card-auto-slider' : '' ?>" style="position: relative;">
                <?php foreach ($processedCardImages as $idx => $src): ?>
                    <img src="<?= htmlspecialchars($src) ?>" alt="<?= htmlspecialchars($p_name) ?>" loading="lazy" class="slide-img" style="<?= $idx === 0 ? 'transition: all 0.8s cubic-bezier(0.25, 1, 0.5, 1); opacity: 1; transform: scale(1) translateY(0);' : 'position:absolute; top:0; left:0; width:100%; height:100%; object-fit:cover; opacity:0; transform: scale(1.05) translateY(8px); transition: all 0.8s cubic-bezier(0.25, 1, 0.5, 1);' ?>">
                <?php endforeach; ?>
            </div>

            <div class="card-actions">
                <button type="button"
                        class="card-wish-btn wish-btn-<?= $p_id ?> <?= in_array($p_id, $wishlistIds ?? []) ? 'active' : '' ?>"
                        onclick="toggleWishlist(<?= $p_id ?>, event)"
                        aria-label="Add to Wishlist">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="<?= in_array($p_id, $wishlistIds ?? []) ? 'var(--red)' : 'none' ?>" stroke="<?= in_array($p_id, $wishlistIds ?? []) ? 'var(--red)' : 'var(--ink)' ?>" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l8.84-8.84 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                    </svg>
                </button>

                <button type="button"
                        class="card-share-btn"
                        onclick="openShareModal('<?= APP_URL ?>/product.php?id=<?= $p_id ?>', '<?= addslashes($p_name) ?>', event)"
                        aria-label="Share Product">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="18" cy="5" r="3"></circle>
                        <circle cx="6" cy="12" r="3"></circle>
                        <circle cx="18" cy="19" r="3"></circle>
                        <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line>
                        <line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line>
                    </svg>
                </button>

                <?php if ($p_stock <= 0 && !$is_preorder && !$is_drop): ?>
                    <button type="button"
                            class="card-cart-btn disabled"
                            disabled
                            aria-label="Out of Stock">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"></path>
                            <path d="M3 6h18"></path>
                            <path d="M16 10a4 4 0 0 1-8 0"></path>
                        </svg>
                    </button>
                <?php elseif ($is_preorder): ?>
                    <button type="button"
                            class="card-cart-btn preorder"
                            onclick="window.location.href='product.php?id=<?= $p_id ?>'; event.preventDefault();"
                            aria-label="Pre-Order">
                        PRE
                    </button>
                <?php else: ?>
                    <button type="button"
                            class="card-cart-btn"
                            onclick="quickAddToCart(<?= $p_id ?>, event)"
                            aria-label="Add to Bag">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"></path>
                            <path d="M3 6h18"></path>
                            <path d="M16 10a4 4 0 0 1-8 0"></path>
                        </svg>
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <div class="card-body">
            <div class="card-cat"><?= strtoupper(htmlspecialchars($p_cat)) ?></div>
            <div class="card-name"><?= htmlspecialchars($p_name) ?></div>

            <div class="card-rating <?= ($p_rating <= 0) ? 'faded' : '' ?>">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <span class="star <?= ($i <= ($p_rating ?: 5)) ? 'filled' : '' ?>">★</span>
                <?php endfor; ?>
            </div>

            <div class="card-price-area">
                <div class="card-price"><?= formatCurrency($p_price) ?></div>
                <?php if ($p_compare > $p_price): ?>
                    <div class="card-price-old"><?= formatCurrency($p_compare) ?></div>
                <?php endif; ?>
            </div>
        </div>
    </a>
</div>
