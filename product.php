<?php
/**
 * Storefront: Product Details (Avazonia)
 */
require_once 'includes/db.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$product_id = (int)($_GET['id'] ?? 0);
if ($product_id <= 0) {
    header('Location: shop.php');
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT p.*, c.category_name
                           FROM products p JOIN categories c ON p.category_id = c.category_id
                           WHERE p.product_id = ? AND p.status = 'published'");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    if (!$product) {
        header('Location: shop.php');
        exit();
    }
} catch(PDOException $e) {
    header('Location: shop.php');
    exit();
}

// Fetch product tags
$product_tags = [];
try {
    $stmt = $pdo->prepare("SELECT t.tag_id, t.tag_name FROM product_tags t JOIN product_tag_relations r ON t.tag_id = r.tag_id WHERE r.product_id = ? ORDER BY t.tag_name");
    $stmt->execute([$product_id]);
    $product_tags = $stmt->fetchAll();
} catch(PDOException $e) {}
$product['tags'] = implode(',', array_column($product_tags, 'tag_name'));

// Fetch product attributes and variations
$product_attributes = [];
$product_variations = [];
$variation_term_map = [];
try {
    $stmt = $pdo->prepare("
        SELECT pa.attribute_id, pa.name AS attr_name, pa.type AS attr_type,
               pat.term_id, pat.name AS term_name, pat.slug AS term_slug, pat.color_hex
        FROM product_attribute_relations par
        JOIN product_attributes pa ON pa.attribute_id = par.attribute_id
        JOIN product_attribute_terms pat ON pat.term_id = par.term_id
        WHERE par.product_id = ?
        ORDER BY pa.position, pat.position
    ");
    $stmt->execute([$product_id]);
    $product_attributes = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT * FROM product_variations WHERE product_id = ? ORDER BY position");
    $stmt->execute([$product_id]);
    $product_variations = $stmt->fetchAll();

    foreach ($product_variations as $var) {
        $stmt = $pdo->prepare("
            SELECT pvtr.term_id, pat.name AS term_name, pat.color_hex, pa.attribute_id
            FROM product_variation_term_relations pvtr
            JOIN product_attribute_terms pat ON pat.term_id = pvtr.term_id
            JOIN product_attributes pa ON pa.attribute_id = pvtr.attribute_id
            WHERE pvtr.variation_id = ?
        ");
        $stmt->execute([$var['variation_id']]);
        $variation_term_map[$var['variation_id']] = $stmt->fetchAll();
    }
} catch(PDOException $e) {
    error_log("Error fetching variations: " . $e->getMessage());
}

// Normalize variations for the Avazonia variant picker
$variants = [];
foreach ($product_variations as $var) {
    $terms = $variation_term_map[$var['variation_id']] ?? [];
    $color = $color_hex = $size = '';
    foreach ($terms as $t) {
        if (!empty($t['color_hex'])) {
            $color = $t['term_name'];
            $color_hex = $t['color_hex'];
        } else {
            $size .= ($size ? ' / ' : '') . $t['term_name'];
        }
    }
    $variants[] = [
        'id'                 => $var['variation_id'],
        'price_override_ghs' => (float)($var['price'] ?? 0),
        'color'              => $color,
        'color_hex'          => $color_hex,
        'size'               => $size,
        'image_url'          => !empty($var['image']) ? getProductImage($var['image']) : '',
        'stock_quantity'     => (int)($var['stock_quantity'] ?? 0),
    ];
}

// Fetch reviews (normalized for Avazonia review list)
$reviews = [];
$avg_rating = 0;
$total_reviews = 0;
try {
    $stmt = $pdo->prepare("SELECT r.*, u.name FROM reviews r JOIN users u ON r.user_id = u.user_id WHERE r.product_id = ? ORDER BY r.created_at DESC");
    $stmt->execute([$product_id]);
    $raw_reviews = $stmt->fetchAll();
    foreach ($raw_reviews as $rv) {
        $reviews[] = [
            'reviewer_name' => $rv['name'],
            'rating'        => (int)$rv['rating'],
            'body'          => $rv['comment'],
            'created_at'    => $rv['created_at'],
        ];
    }
    $total_reviews = count($reviews);
    if ($total_reviews > 0) {
        $avg_rating = round(array_sum(array_column($reviews, 'rating')) / $total_reviews, 1);
    }
} catch(PDOException $e) {}
$review_count = $total_reviews;

// Fetch all product images
$product_images = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, display_order ASC, image_id ASC");
    $stmt->execute([$product_id]);
    $product_images = $stmt->fetchAll();
} catch(PDOException $e) {}

$images = [];
foreach ($product_images as $pi) {
    $images[] = ['url' => getProductImage($pi['image_path']), 'alt_text' => $product['name']];
}

$main_image = !empty($product_images[0]['image_path']) ? getProductImage($product_images[0]['image_path']) : getProductImage($product['image'] ?? '');
$main_image_src = $main_image;

// Related products
$related_products = [];
try {
    $stmt = $pdo->prepare("SELECT p.*, c.category_name
                           FROM products p JOIN categories c ON p.category_id = c.category_id
                           WHERE p.category_id = ? AND p.product_id != ? AND p.status = 'published'
                           ORDER BY p.is_featured DESC, RAND() LIMIT 8");
    $stmt->execute([$product['category_id'], $product_id]);
    $related_products = $stmt->fetchAll();
} catch(PDOException $e) {}
$related = $related_products;

// Wishlist state
$user_wishlist = [];
if (isset($_SESSION['user_id'])) {
    try {
        $w_stmt = $pdo->prepare("SELECT product_id FROM wishlist WHERE user_id = ?");
        $w_stmt->execute([$_SESSION['user_id']]);
        $user_wishlist = $w_stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {}
}
$in_wishlist = in_array($product_id, $user_wishlist);
$isInWishlist = $in_wishlist;
$wishlistIds = $user_wishlist;

$page_title = $product['name'];

// Derived flags
$original_price = (float)($product['original_price'] ?? 0);
$discount_percentage = 0;
if ($original_price > (float)$product['price']) {
    $discount_percentage = round((($original_price - (float)$product['price']) / $original_price) * 100);
}
$is_preorder = !empty($product['is_preorder']) || !empty($product['preorder_flag']) || (isset($product['status']) && $product['status'] === 'preorder');
$is_dropshipping = false;
$in_stock = (int)($product['stock_quantity'] ?? 0) > 0;
$features_list = !empty($product['features']) ? array_filter(array_map('trim', explode("\n", $product['features']))) : [];

// Open Graph meta tags
$og_title = !empty($product['meta_title']) ? $product['meta_title'] : $product['name'];
$og_description = !empty($product['meta_description']) ? $product['meta_description'] : (strlen($product['description'] ?? '') > 160 ? substr($product['description'], 0, 157) . '...' : ($product['description'] ?? ''));
$og_image = SITE_URL . $main_image_src;
$og_url = SITE_URL . 'product.php?id=' . $product_id;
$og_type = 'product';

// JSON-LD structured data
$json_site_name = 'ASO Online Market';
try {
    $stmt = $pdo->query("SELECT setting_value FROM site_settings WHERE setting_key = 'site_name' LIMIT 1");
    $sn = $stmt->fetchColumn();
    if ($sn) $json_site_name = $sn;
} catch(PDOException $e) {}

$json_ld_product = [
    '@context' => 'https://schema.org/',
    '@type' => 'Product',
    'name' => $product['name'],
    'image' => $og_image,
    'description' => strip_tags($product['description'] ?? ''),
    'sku' => $product['sku'] ?? '',
    'brand' => ['@type' => 'Brand', 'name' => $json_site_name],
    'offers' => [
        '@type' => 'Offer',
        'url' => $og_url,
        'priceCurrency' => 'GHS',
        'price' => number_format((float)$product['price'], 2, '.', ''),
        'availability' => $in_stock ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
        'itemCondition' => 'https://schema.org/NewCondition',
    ],
];
if ($total_reviews > 0) {
    $json_ld_product['aggregateRating'] = [
        '@type' => 'AggregateRating',
        'ratingValue' => number_format($avg_rating, 1, '.', ''),
        'reviewCount' => $total_reviews,
    ];
}
$json_ld = json_encode($json_ld_product, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

include 'includes/header.php';
?>

<section class="product-page" style="padding: 100px 0 100px;">
    <div class="container product-detail-layout">
        <!-- Image Gallery -->
        <div class="product-gallery" style="display: flex; flex-direction: column; gap: 16px;">
            <div class="zoom-container" id="zoom-container" style="position:relative; aspect-ratio: 1; background: var(--off); border: 1px solid var(--light-gray); display: flex; align-items: center; justify-content: center;">
                <img id="main-product-image" src="<?= htmlspecialchars($main_image_src) ?>" alt="<?= htmlspecialchars($product['name']) ?>" style="width: 100%; height: 100%; object-fit: contain; padding: 40px; transition: transform 0.1s ease-out, opacity 0.2s ease;">
            </div>

            <?php if (count($images) > 1): ?>
            <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 12px;">
                <?php foreach ($images as $index => $imgData): ?>
                    <div class="thumbnail-item" onclick="const vid = document.getElementById('main-product-video'); if(vid){vid.pause(); vid.style.display='none';} document.getElementById('main-product-image').style.display='block'; const mainImg = document.getElementById('main-product-image'); mainImg.style.opacity='0.5'; setTimeout(()=>{mainImg.src='<?= htmlspecialchars($imgData['url']) ?>'; mainImg.style.opacity='1';},100); document.querySelectorAll('.thumbnail-item').forEach(t=>t.style.borderColor='var(--light-gray)'); this.style.borderColor='var(--red)';" style="aspect-ratio: 1; background: var(--off); border: 1.5px solid <?= $index === 0 ? 'var(--red)' : 'var(--light-gray)' ?>; cursor: pointer; display: flex; align-items: center; justify-content: center; overflow: hidden; padding: 8px;">
                        <img src="<?= htmlspecialchars($imgData['url']) ?>" alt="<?= htmlspecialchars($imgData['alt_text']) ?>" style="width: 100%; height: 100%; object-fit: contain; pointer-events: none;">
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Product Info -->
        <div class="product-info">
            <div class="sec-eyebrow">
                <span class="eyebrow-text"><?= htmlspecialchars($product['category_name'] ?? 'ASO') ?></span>
                <span class="eyebrow-line"></span>
            </div>

            <?php if ($is_preorder): ?>
                <div style="display: inline-block; background: #0088FF; color: #fff; font-family: var(--f-display); font-size: 10px; font-weight: 800; padding: 6px 16px; border-radius: 100px; text-transform: uppercase; letter-spacing: .1em; margin-bottom: 20px;">Pre-order Item</div>
            <?php elseif ($is_dropshipping): ?>
                <div style="display: inline-block; background: #FF8800; color: #fff; font-family: var(--f-display); font-size: 10px; font-weight: 800; padding: 6px 16px; border-radius: 100px; text-transform: uppercase; letter-spacing: .1em; margin-bottom: 20px;">Global Direct</div>
            <?php endif; ?>

            <h1 style="font-family: var(--f-display); font-weight: 700; font-size: clamp(24px, 4vw, 38px); text-transform: uppercase; margin-bottom: 16px; line-height: 1.1; letter-spacing: -0.02em;"><?= htmlspecialchars($product['name']) ?></h1>

            <?php if ($review_count > 0): ?>
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 32px;">
                    <div class="card-rating" style="font-size: 14px; gap: 4px;">
                        <?php
                        $rating = round($avg_rating ?: 5);
                        for ($i = 1; $i <= 5; $i++):
                        ?>
                            <span class="star <?= $i <= $rating ? 'filled' : '' ?>">★</span>
                        <?php endfor; ?>
                    </div>
                    <a href="#reviews" style="font-family: var(--f-mono); font-size: 10px; color: var(--mid-gray); text-decoration: none; text-transform: uppercase; letter-spacing: .05em;">(<?= $review_count ?>) Reviews</a>
                </div>
            <?php endif; ?>

            <div style="font-family: var(--f-display); margin-bottom: 32px; display: flex; flex-direction: column; align-items: flex-start; gap: 4px;">
                <div id="live-price-display" style="font-weight: 800; font-size: clamp(28px, 5vw, 44px); color: var(--ink); line-height: 1;"><?= formatCurrency($product['price']) ?></div>
                <?php if ($original_price > (float)$product['price']): ?>
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <span style="font-family: var(--f-display); font-size: 18px; color: var(--mid-gray); text-decoration: line-through; font-weight: 500; opacity: 0.6;"><?= formatCurrency($original_price) ?></span>
                        <span style="background: #FFF5E6; color: #FF8C00; font-size: 14px; font-weight: 800; padding: 6px 14px; border-radius: 8px;">-<?= $discount_percentage ?>%</span>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($product['tags'])): ?>
                <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 32px; align-items: center;">
                    <span style="font-family: var(--f-mono); font-size: 9px; text-transform: uppercase; color: var(--mid-gray); margin-right: 4px;">In This Drop:</span>
                    <?php foreach (explode(',', $product['tags']) as $tag): ?>
                        <?php if (trim($tag)): ?>
                            <span style="font-family: var(--f-mono); font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: var(--ink); background: var(--off); padding: 4px 10px; border-radius: 4px; border: 1px solid var(--light-gray);"><?= htmlspecialchars(trim($tag)) ?></span>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div style="font-family: var(--f-body); font-size: 14px; color: var(--mid-gray); line-height: 1.6; margin-bottom: 32px; max-width: 480px;">
                <?= !empty($product['description']) ? nl2br(substr(strip_tags($product['description']), 0, 180)) . '...' : 'No description available.' ?>
            </div>

            <?php if (!empty($variants)): ?>
            <div style="margin-bottom: 32px;">
                <label style="display: block; font-family: var(--f-mono); font-size: 11px; text-transform: uppercase; color: var(--mid-gray); margin-bottom: 12px; font-weight: 600;">Select Variant</label>
                <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                    <?php foreach ($variants as $idx => $v): ?>
                        <div class="variant-pill"
                             data-id="<?= $v['id'] ?>"
                             data-price="<?= $v['price_override_ghs'] ? number_format($v['price_override_ghs'], 2) : number_format($product['price'], 2) ?>"
                             data-currency="GHS"
                             data-image="<?= $v['image_url'] ?: '' ?>"
                             onclick="selectVariant(this)"
                             style="cursor: pointer; display: flex; align-items: center; gap: 8px; border: 2px solid <?= $idx === 0 ? 'var(--ink)' : 'var(--light-gray)' ?>; border-radius: 20px; padding: 6px 16px; font-size: 12px; font-weight: 700;">
                            <?php if ($v['color_hex']): ?>
                                <span style="display: inline-block; width: 14px; height: 14px; border-radius: 50%; background: <?= $v['color_hex'] ?>; border: 1px solid rgba(0,0,0,0.1);"></span>
                            <?php endif; ?>
                            <span><?= htmlspecialchars(trim($v['color'] . ' ' . $v['size']) ?: 'Standard') ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <script>
                window.selectVariant = function(pill) {
                    const priceDisplay = document.getElementById('live-price-display');
                    const formVarInput = document.getElementById('form-variant-id');
                    const mainImg = document.getElementById('main-product-image');

                    document.querySelectorAll('.variant-pill').forEach(p => p.style.borderColor = 'var(--light-gray)');
                    pill.style.borderColor = 'var(--ink)';

                    if (formVarInput) formVarInput.value = pill.getAttribute('data-id');

                    if (pill.getAttribute('data-price')) {
                        priceDisplay.innerText = '\u20B5' + pill.getAttribute('data-price');
                    }

                    const newImage = pill.getAttribute('data-image');
                    if (newImage && mainImg) {
                        mainImg.style.opacity = '0.5';
                        setTimeout(() => {
                            mainImg.src = newImage.startsWith('http') ? newImage : '<?= APP_PATH ?>/' + newImage;
                            mainImg.style.opacity = '1';
                        }, 100);
                    }
                };
            </script>
            <?php endif; ?>

            <form id="product-add-form" class="ajax-cart-form" action="ajax/add_to_cart.php" method="POST">
                <input type="hidden" name="product_id" value="<?= $product['product_id'] ?>">
                <input type="hidden" name="variant_id" id="form-variant-id" value="<?= !empty($variants) ? $variants[0]['id'] : '' ?>">

                <div class="product-actions-grid">
                    <div class="qty-selector-v2">
                        <button type="button" onclick="changeQty(-1)">-</button>
                        <input type="number" name="quantity" id="product-qty" value="1" min="1" max="<?= (int)$product['stock_quantity'] ?: 99 ?>">
                        <button type="button" onclick="changeQty(1)">+</button>
                    </div>

                    <button type="submit" class="btn-ink add-to-bag-btn" <?= !$in_stock ? 'disabled' : '' ?>>
                        <?= !$in_stock ? 'OUT OF STOCK' : ($is_preorder ? 'PRE-ORDER' : 'ADD TO BAG') ?>
                        <?php if ($in_stock): ?><span style="font-size: 1.1em;">→</span><?php endif; ?>
                    </button>

                    <div class="secondary-actions">
                        <button type="button" id="wish-toggle-btn"
                                onclick="toggleWishlist(<?= $product['product_id'] ?>)"
                                class="wish-btn wish-btn-<?= $product['product_id'] ?> <?= $isInWishlist ? 'active' : '' ?>">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="<?= $isInWishlist ? 'var(--red)' : 'none' ?>" stroke="<?= $isInWishlist ? 'var(--red)' : 'var(--ink)' ?>" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l8.84-8.84 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                            </svg>
                        </button>

                        <button type="button"
                                onclick="openShareModal('<?= APP_URL ?>/product.php?id=<?= $product['product_id'] ?>', '<?= addslashes($product['name']) ?>', event)"
                                class="share-trigger-btn">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--ink)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="18" cy="5" r="3"></circle>
                                <circle cx="6" cy="12" r="3"></circle>
                                <circle cx="18" cy="19" r="3"></circle>
                                <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line>
                                <line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line>
                            </svg>
                        </button>
                    </div>
                </div>

                <style>
                    .product-actions-grid { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
                    .qty-selector-v2 { display: flex; align-items: center; border: 1px solid var(--light-gray); height: 48px; border-radius: 8px; overflow: hidden; background: #fff; }
                    .qty-selector-v2 button { width: 40px; height: 100%; border: none; background: none; cursor: pointer; font-size: 18px; color: var(--mid-gray); display: flex; align-items: center; justify-content: center; transition: background 0.2s; }
                    .qty-selector-v2 button:hover { background: var(--off); }
                    .qty-selector-v2 input { width: 40px; height: 100%; border: none; text-align: center; font-family: var(--f-display); font-weight: 700; font-size: 14px; background: #fff; -moz-appearance: textfield; }
                    .qty-selector-v2 input::-webkit-outer-spin-button,
                    .qty-selector-v2 input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
                    .add-to-bag-btn { height: 48px; border-radius: 8px !important; flex: 1; min-width: 160px; justify-content: center; }
                    .add-to-bag-btn:disabled { opacity: 0.5; cursor: not-allowed; }
                    .secondary-actions { display: flex; gap: 8px; }
                    .wish-btn, .share-trigger-btn { height: 48px; width: 48px; padding: 0; border: 1px solid var(--light-gray); background: #fff; border-radius: 8px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.3s var(--ease); flex-shrink: 0; }
                    .wish-btn:hover, .share-trigger-btn:hover { border-color: var(--red); transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
                    .wish-btn.active { border-color: var(--red); background: #FFF5F6; }
                    .wish-btn.active svg { fill: var(--red); stroke: var(--red); }
                    @media (max-width: 480px) {
                        .product-actions-grid { gap: 8px; }
                        .qty-selector-v2 { width: 100%; justify-content: space-between; }
                        .qty-selector-v2 button { flex: 1; }
                        .add-to-bag-btn { order: 2; width: 100%; flex: none; }
                        .secondary-actions { order: 3; width: 100%; }
                        .secondary-actions button { flex: 1; }
                    }
                </style>
            </form>

            <script>
                function changeQty(delta) {
                    const i = document.getElementById('product-qty');
                    const max = parseInt(i.getAttribute('max'));
                    let val = parseInt(i.value) || 1;
                    val = Math.min(Math.max(1, val + delta), max || 99);
                    i.value = val;
                }
                document.addEventListener('DOMContentLoaded', function() {
                    const form = document.getElementById('product-add-form');
                    if (form) {
                        form.addEventListener('submit', async function(e) {
                            e.preventDefault();
                            const btn = form.querySelector('.add-to-bag-btn');
                            if (btn.disabled) return;
                            const original = btn.innerHTML;
                            btn.disabled = true;
                            btn.innerHTML = 'ADDING... <span style="font-size:1.1em;">→</span>';
                            try {
                                const body = new URLSearchParams(new FormData(form));
                                const res = await fetch(form.action, { method: 'POST', body });
                                const data = await res.json();
                                if (data.success) {
                                    if (window.refreshCartCounter) window.refreshCartCounter(data.cart_count);
                                    if (typeof showToast === 'function') showToast('Added to cart', 'success', 2000);
                                    btn.innerHTML = 'ADDED ✓';
                                } else {
                                    if (typeof showToast === 'function') showToast(data.message || 'Error adding to cart', 'danger', 3000);
                                    else alert(data.message || 'Error adding to cart');
                                    btn.innerHTML = original;
                                }
                            } catch (err) {
                                btn.innerHTML = original;
                                if (typeof showToast === 'function') showToast('Connection error', 'danger', 3000);
                            }
                            setTimeout(() => { btn.innerHTML = original; btn.disabled = false; }, 1500);
                        });
                    }
                });
            </script>

            <!-- Premium Trust & Help Section -->
            <div class="product-trust-group">
                <div class="payment-trust-box">
                    <span class="payment-label">Supported payment types:</span>
                    <div class="payment-icons-row">
                        <img src="<?= APP_PATH ?>/assets/images/paystack1.png" alt="Powered by Paystack" class="paystack-banner">
                    </div>
                </div>

                <div class="trust-meta-row">
                    <div class="shipping-promise">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--mid-gray);"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line><path d="M9 16l2 2 4-4"></path></svg>
                        <span>Order now and your order ships by <span class="ship-date-highlight"><?= date('D, M d', strtotime('+3 days')) ?></span></span>
                    </div>

                    <div class="social-sharing-circles">
                        <?php if (!empty($settings['social_facebook'])): ?>
                            <a href="<?= htmlspecialchars($settings['social_facebook']) ?>" class="soc-circle" target="_blank"><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path></svg></a>
                        <?php endif; ?>
                        <?php if (!empty($settings['social_youtube'])): ?>
                            <a href="<?= htmlspecialchars($settings['social_youtube']) ?>" class="soc-circle" target="_blank"><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M22.54 6.42a2.78 2.78 0 0 0-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.42a2.78 2.78 0 0 0-1.94 2C1 8.11 1 12 1 12s0 3.89.46 5.58a2.78 2.78 0 0 0 1.94 2C5.12 20 12 20 12 20s6.88 0 8.6-.42a2.78 2.78 0 0 0 1.94-2C23 15.89 23 12 23 12s0-3.89-.46-5.58zM9.75 15.02V8.98L15 12l-5.25 3.02z"></path></svg></a>
                        <?php endif; ?>
                        <?php if (!empty($settings['social_instagram'])): ?>
                            <a href="<?= htmlspecialchars($settings['social_instagram']) ?>" class="soc-circle" target="_blank"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line></svg></a>
                        <?php endif; ?>
                        <?php if (!empty($settings['social_tiktok'])): ?>
                            <a href="<?= htmlspecialchars($settings['social_tiktok']) ?>" class="soc-circle" target="_blank"><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M9 12a4 4 0 1 0 4 4V0h4a8.13 8.13 0 0 1-5 2V8a4 4 0 0 0-3 4z"></path></svg></a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="help-pills-row">
                    <a href="contact.php" class="help-pill-btn">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                        Need Help? Chat with an Expert
                    </a>
                    <a href="tel:+<?= ltrim(WHATSAPP_NUMBER, '+') ?>" class="help-pill-btn call-pill">
                        <span class="call-number">+<?= ltrim(WHATSAPP_NUMBER, '+') ?></span>
                        <span class="call-action">Call Us</span>
                    </a>
                </div>

                <div class="trust-badges-large">
                    <div class="tbadge-item">
                        <div class="tbadge-icon">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12A10 10 0 1 1 12 2a10 10 0 0 1 10 10z"></path><polyline points="12 6 12 12 16 14"></polyline></svg>
                        </div>
                        <div class="tbadge-content">
                            <h4>Online Support 24/7</h4>
                        </div>
                    </div>
                    <div class="tbadge-item">
                        <div class="tbadge-icon">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                        </div>
                        <div class="tbadge-content">
                            <h4>Secure Payment</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Lightbox Overlay -->
    <div id="mobile-lightbox" class="lightbox-overlay">
        <button class="lightbox-close" id="close-lightbox">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
        <img id="lightbox-img" class="lightbox-img" src="" alt="Expanded View">
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const container = document.getElementById('zoom-container');
        const img = document.getElementById('main-product-image');
        const lightbox = document.getElementById('mobile-lightbox');
        const lightboxImg = document.getElementById('lightbox-img');
        const closeLightbox = document.getElementById('close-lightbox');

        if (!container || !img) return;

        container.addEventListener('click', function() {
            lightboxImg.src = img.src;
            lightbox.classList.add('active');
            document.body.style.overflow = 'hidden';
        });

        if (closeLightbox) {
            closeLightbox.addEventListener('click', function() {
                lightbox.classList.remove('active');
                document.body.style.overflow = '';
            });
        }

        lightbox.addEventListener('click', function(e) {
            if (e.target === lightbox) {
                lightbox.classList.remove('active');
                document.body.style.overflow = '';
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && lightbox.classList.contains('active')) {
                lightbox.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    });
    </script>
</section>

<!-- PRODUCT DEEP DIVE -->
<section class="product-deep-dive" style="padding: 60px 0 100px; border-top: 1px solid var(--light-gray);">
    <div class="container">
        <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 80px; align-items: flex-start;">

            <div class="overview-column">
                <h2 style="font-family: var(--f-display); font-weight: 800; font-size: 24px; text-transform: uppercase; color: var(--ink); margin-bottom: 32px; letter-spacing: -0.01em;">Overview</h2>

                <div id="overview-wrapper" class="expandable-wrapper">
                    <div class="expandable-content">
                        <div style="font-family: var(--f-body); font-size: 15px; line-height: 1.8; color: var(--ink); opacity: 0.85;">
                            <?= nl2br($product['description'] ?: 'Detailed information for this product is coming soon.') ?>
                        </div>

                        <?php if (!empty($features_list)): ?>
                            <div style="margin-top: 48px;">
                                <ul style="list-style: none; padding: 0; display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                                    <?php foreach ($features_list as $feat): ?>
                                        <li style="display: flex; align-items: flex-start; gap: 10px; font-family: var(--f-body); font-size: 14px; color: var(--ink);">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#00C853" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; margin-top: 2px;"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                            <span><?= htmlspecialchars($feat) ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                    <button type="button" class="expand-btn" onclick="toggleExpand('overview-wrapper')">
                        View Full Overview <span>↓</span>
                    </button>
                </div>
            </div>

            <div class="details-column">
                <h2 style="font-family: var(--f-display); font-weight: 800; font-size: 24px; text-transform: uppercase; color: var(--ink); margin-bottom: 32px; letter-spacing: -0.01em;">Details</h2>

                <div style="background: var(--off); padding: 12px 20px; margin-bottom: 24px;">
                    <span style="font-family: var(--f-mono); font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.15em; color: var(--red);">Specifications</span>
                </div>

                <div id="specs-wrapper" class="expandable-wrapper">
                    <div class="expandable-content">
                        <div style="display: flex; flex-direction: column; gap: 16px;">
                            <div style="display: flex; justify-content: space-between; padding-bottom: 12px; border-bottom: 1px solid var(--light-gray);">
                                <span style="font-family: var(--f-mono); font-size: 11px; text-transform: uppercase; color: var(--mid-gray);">Category</span>
                                <span style="font-family: var(--f-display); font-size: 13px; font-weight: 700;"><?= htmlspecialchars($product['category_name'] ?? '—') ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; padding-bottom: 12px; border-bottom: 1px solid var(--light-gray);">
                                <span style="font-family: var(--f-mono); font-size: 11px; text-transform: uppercase; color: var(--mid-gray);">Ref#</span>
                                <span style="font-family: var(--f-display); font-size: 13px; font-weight: 700;"><?= htmlspecialchars($product['sku'] ?? ('ASO-' . $product['product_id'])) ?></span>
                            </div>
                            <?php if (!empty($product_tags)): ?>
                                <div style="display: flex; justify-content: space-between; padding-bottom: 12px; border-bottom: 1px solid var(--light-gray);">
                                    <span style="font-family: var(--f-mono); font-size: 11px; text-transform: uppercase; color: var(--mid-gray);">Tags</span>
                                    <span style="font-family: var(--f-display); font-size: 13px; font-weight: 700;"><?= htmlspecialchars(implode(', ', array_column($product_tags, 'tag_name'))) ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <button type="button" class="expand-btn" onclick="toggleExpand('specs-wrapper')">
                        View Full Specifications <span>↓</span>
                    </button>
                </div>
            </div>

        </div>
    </div>
</section>

<style>
.expandable-wrapper { position: relative; overflow: hidden; }
.expandable-content { max-height: 250px; overflow: hidden; transition: max-height 0.8s cubic-bezier(0.19, 1, 0.22, 1); position: relative; }
.expandable-wrapper:not(.expanded) .expandable-content::after { content: ''; position: absolute; bottom: 0; left: 0; width: 100%; height: 100px; background: linear-gradient(to top, #fff 0%, rgba(255, 255, 255, 0) 100%); pointer-events: none; transition: opacity 0.3s; }
.expandable-wrapper.expanded .expandable-content::after { opacity: 0; }
.expandable-wrapper.expanded .expandable-content { max-height: 2000px; }
.expand-btn { display: inline-flex; align-items: center; gap: 12px; margin-top: 24px; padding: 12px 24px; background: var(--ink); color: #fff; border: none; border-radius: 8px; font-family: var(--f-semi); font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; cursor: pointer; transition: all 0.3s var(--ease); }
.expand-btn:hover { background: var(--red); transform: translateY(-2px); box-shadow: 0 8px 24px rgba(229, 0, 26, 0.2); }
.expand-btn span { font-size: 14px; transition: transform 0.4s var(--ease); }
.expandable-wrapper.expanded .expand-btn span { transform: rotate(180deg); }
@media (max-width: 991px) { .product-deep-dive .container > div { grid-template-columns: 1fr !important; gap: 60px !important; } }
</style>

<script>
function toggleExpand(wrapperId) {
    const wrapper = document.getElementById(wrapperId);
    const btn = wrapper.querySelector('.expand-btn');
    if (wrapper.classList.contains('expanded')) {
        wrapper.classList.remove('expanded');
        btn.innerHTML = wrapperId === 'overview-wrapper' ? 'View Full Overview <span>↓</span>' : 'View Full Specifications <span>↓</span>';
        wrapper.scrollIntoView({ behavior: 'smooth', block: 'start' });
    } else {
        wrapper.classList.add('expanded');
        btn.innerHTML = 'Show Less <span>↑</span>';
    }
}
document.addEventListener('DOMContentLoaded', function() {
    const wrappers = document.querySelectorAll('.expandable-wrapper');
    wrappers.forEach(wrapper => {
        const content = wrapper.querySelector('.expandable-content');
        const btn = wrapper.querySelector('.expand-btn');
        if (content && content.scrollHeight <= 260) {
            btn.style.display = 'none';
            wrapper.classList.add('expanded');
        }
    });
});
</script>

<!-- REVIEWS SECTION -->
<section id="reviews" class="reviews-sec-v2">
    <div class="container">
        <div class="review-summary-bar">
            <div class="review-summary-left">
                <div class="review-avg-box">
                    <div class="review-avg-score"><?= number_format($avg_rating, 1) ?></div>
                    <div class="review-avg-label">Avg. Rating</div>
                </div>
                <div class="review-summary-meta">
                    <h3>Customer Reviews</h3>
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div class="card-rating" style="font-size: 16px; gap: 4px;">
                            <?php
                            $rating = round($avg_rating ?: 5);
                            for ($i = 1; $i <= 5; $i++):
                            ?>
                                <span class="star <?= $i <= $rating ? 'filled' : '' ?>" style="color: <?= $i <= $rating ? 'var(--red)' : 'var(--light-gray)' ?>; font-size: 14px;">★</span>
                            <?php endfor; ?>
                        </div>
                        <div class="review-count-small"><?= $review_count ?> Reviews</div>
                    </div>
                </div>
            </div>

            <?php if (isset($_SESSION['user_id'])): ?>
            <button type="button" class="write-review-toggle-btn" onclick="toggleReviewForm()">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                Write a Review
            </button>
            <?php else: ?>
            <a href="login.php" class="write-review-toggle-btn" style="text-decoration: none;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                Write a Review
            </a>
            <?php endif; ?>
        </div>

        <?php if (isset($_SESSION['user_id'])): ?>
        <div id="review-form-wrapper" class="review-form-container">
            <div class="review-form-inner">
                <h3 style="font-family: var(--f-display); font-weight: 700; font-size: 13px; text-transform: uppercase; letter-spacing: .15em; margin-bottom: 32px; text-align: center;">Share Your Experience</h3>
                <form action="api/submit_review.php" method="POST" style="display: flex; flex-direction: column; gap: 24px;">
                    <input type="hidden" name="product_id" value="<?= $product['product_id'] ?>">

                    <div class="form-group">
                        <label style="display: block; font-family: var(--f-semi); font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .2em; color: var(--mid-gray); margin-bottom: 8px;">Rating</label>
                        <input type="hidden" name="rating" id="review-rating-val" value="5">
                        <div class="star-rating-input" style="display: flex; gap: 8px; font-size: 24px; color: var(--light-gray); cursor: pointer;">
                            <?php for($i=1; $i<=5; $i++): ?>
                                <span class="star-pick-v2 active" data-value="<?= $i ?>" style="color: var(--red);">★</span>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label style="display: block; font-family: var(--f-semi); font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .2em; color: var(--mid-gray); margin-bottom: 8px;">Review</label>
                        <textarea name="comment" required placeholder="What do you think of this product?" style="width: 100%; height: 120px; background: #fff; border: 1px solid var(--light-gray); padding: 16px; font-family: var(--f-body); font-size: 14px; outline: none; resize: none; border-radius: 4px;"></textarea>
                    </div>

                    <button type="submit" class="btn-red" style="width: 100%; height: 52px; font-size: 11px;">Post Review →</button>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <div class="compact-review-list">
            <?php if (empty($reviews)): ?>
                <div style="padding: 60px 20px; text-align: center; background: var(--off); border: 1px solid var(--light-gray); border-radius: 8px;">
                    <p style="font-family: var(--f-mono); font-size: 10px; color: var(--mid-gray); text-transform: uppercase; letter-spacing: .1em;">Be the first to review this product.</p>
                </div>
            <?php else: ?>
                <?php foreach ($reviews as $rev): ?>
                    <div class="compact-review-item">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                            <div>
                                <div style="font-family: var(--f-display); font-weight: 800; font-size: 15px; text-transform: uppercase; color: var(--ink);"><?= htmlspecialchars($rev['reviewer_name']) ?></div>
                                <div style="font-family: var(--f-mono); font-size: 9px; color: var(--mid-gray); margin-top: 4px; text-transform: uppercase; letter-spacing: .1em;"><?= date('M d, Y', strtotime($rev['created_at'])) ?></div>
                            </div>
                            <div style="color: var(--red); font-size: 10px; display: flex; gap: 2px;">
                                <?php for($i=1; $i<=5; $i++): ?>
                                    <span style="color: <?= $i <= $rev['rating'] ? 'var(--red)' : 'var(--light-gray)' ?>">★</span>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <p style="font-family: var(--f-body); font-size: 14px; line-height: 1.6; color: var(--ink); opacity: .85;"><?= nl2br(htmlspecialchars($rev['body'])) ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<script>
function toggleReviewForm() {
    const wrapper = document.getElementById('review-form-wrapper');
    const btn = document.querySelector('.write-review-toggle-btn');
    if (!wrapper || !btn) return;
    wrapper.classList.toggle('active');
    btn.classList.toggle('is-active');
    if (wrapper.classList.contains('active')) {
        btn.innerHTML = 'Cancel Review';
    } else {
        btn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg> Write a Review';
    }
}

document.querySelectorAll('.star-pick-v2').forEach(star => {
    star.addEventListener('click', function() {
        const val = this.getAttribute('data-value');
        document.getElementById('review-rating-val').value = val;
        document.querySelectorAll('.star-pick-v2').forEach(s => {
            s.style.color = (s.getAttribute('data-value') <= val) ? 'var(--red)' : 'var(--light-gray)';
        });
    });
});
</script>

<!-- RELATED PRODUCTS -->
<?php if (!empty($related)): ?>
<section class="related-products" style="padding: 120px 0; background: var(--white); border-top: 1px solid var(--light-gray);">
    <div class="container">
        <div class="sec-eyebrow">
            <span class="eyebrow-text">You May Also Like</span>
            <span class="eyebrow-line"></span>
        </div>
        <h2 style="font-family: var(--f-display); font-weight: 900; font-size: 48px; text-transform: uppercase; margin-bottom: 64px;">Related<br>Drops</h2>

        <div class="product-grid">
            <?php foreach ($related as $p): ?>
                <?php require 'includes/product-card.php'; ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
