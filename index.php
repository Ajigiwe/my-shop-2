<?php
// Load environment variables
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require __DIR__ . '/vendor/autoload.php';
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

/**
 * Storefront: Home
 * Avazonia-style home for ASO Online Market
 */

// Include database connection
require_once 'includes/db.php';

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set page title
$page_title = 'Home';

// Get settings
$settings = [];
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {}

$grid_cols = isset($settings['products_per_row']) ? (int)$settings['products_per_row'] : 4;

// Fetch user's wishlist ids
$user_wishlist = [];
if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT product_id FROM wishlist WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user_wishlist = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch(PDOException $e) {}
}

include 'includes/header.php';
?>

<main>
    <?php include 'includes/hero.php'; ?>

    <?php
    // ── CATEGORY GRID ──────────────────────────────
    $categoryGrid = [];
    try {
        $cat_stmt = $pdo->query("SELECT category_id, category_name, image FROM categories ORDER BY category_id ASC");
        while ($row = $cat_stmt->fetch(PDO::FETCH_ASSOC)) {
            $row['name'] = $row['category_name'];
            $row['image_url'] = !empty($row['image']) ? getProductImage($row['image']) : '';
            $categoryGrid[] = $row;
        }
    } catch (PDOException $e) {}
    ?>

    <!-- CATEGORY GRID SECTION -->
    <?php if (!empty($categoryGrid)): ?>
    <section class="category-grid-section">
        <div class="container">
            <div class="category-grid">
                <?php
                $first = array_shift($categoryGrid);
                $img = !empty($first['image_url']) ? $first['image_url'] : '';
                $fallbackBg = 'linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%)';
                ?>
                <a href="shop.php?category=<?= urlencode($first['category_name']) ?>" class="cat-tile cat-hero" style="background-image: <?= $img ? 'url(\'' . $img . '\')' : $fallbackBg ?>;">
                    <span class="cat-label"><?= htmlspecialchars($first['name']) ?></span>
                </a>
                <?php foreach ($categoryGrid as $cat):
                    $img2 = !empty($cat['image_url']) ? $cat['image_url'] : '';
                ?>
                    <a href="shop.php?category=<?= urlencode($cat['category_name']) ?>" class="cat-tile" style="background-image: <?= $img2 ? 'url(\'' . $img2 . '\')' : $fallbackBg ?>;">
                        <span class="cat-label"><?= htmlspecialchars($cat['name']) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <?php
    // ── FEATURED / ALL PRODUCTS (paginated) ────────
    $perPage = 8;
    $page = max(1, (int)($_GET['page'] ?? 1));
    $totalProducts = 0;
    try {
        $totalProducts = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE status = 'published'")->fetchColumn();
    } catch (PDOException $e) {}

    $totalPages = max(1, (int)ceil($totalProducts / $perPage));
    $page = min($page, $totalPages);
    $offset = ($page - 1) * $perPage;

    $all_products = [];
    try {
        $all_stmt = $pdo->prepare("
            SELECT p.product_id AS id, p.name, c.category_name,
                   p.image AS primary_image, p.price AS price_ghs,
                   p.original_price AS compare_at_price_ghs,
                   p.stock_quantity AS stock_qty, p.average_rating AS avg_rating,
                   p.has_multiple_images,
                   (DATEDIFF(NOW(), p.created_at) <= 21) AS is_new_arrival
            FROM products p
            LEFT JOIN categories c ON c.category_id = p.category_id
            WHERE p.status = 'published'
            ORDER BY p.is_featured DESC, p.product_id DESC
            LIMIT $perPage OFFSET $offset
        ");
        $all_stmt->execute();
        $all_products = $all_stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {}

    $pagination = [
        'page' => $page,
        'totalPages' => $totalPages,
        'hasPrev' => $page > 1,
        'hasNext' => $page < $totalPages,
    ];

    // ── PRE-ORDERS (none flagged in ASO catalog) ───
    $preorders = [];

    // ── BESTSELLERS ────────────────────────────────
    $bestsellers = [];
    try {
        $best_stmt = $pdo->query("
            SELECT p.product_id AS id, p.name, c.category_name,
                   p.image AS primary_image, p.price AS price_ghs,
                   p.original_price AS compare_at_price_ghs,
                   p.stock_quantity AS stock_qty, p.average_rating AS avg_rating,
                   p.has_multiple_images,
                   (DATEDIFF(NOW(), p.created_at) <= 21) AS is_new_arrival
            FROM products p
            LEFT JOIN categories c ON c.category_id = p.category_id
            WHERE p.status = 'published'
            ORDER BY p.average_rating DESC, p.view_count DESC, p.product_id DESC
            LIMIT 8
        ");
        $bestsellers = $best_stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {}
    if (empty($bestsellers)) $bestsellers = array_slice($all_products, 0, 5);

    // ── CATEGORY SHOWCASE ──────────────────────────
    $categoryShowcase = [];
    try {
        $show_stmt = $pdo->query("
            SELECT c.category_id, c.category_name AS name
            FROM categories c
            JOIN products p ON p.category_id = c.category_id AND p.status = 'published'
            GROUP BY c.category_id, c.category_name
            ORDER BY COUNT(p.product_id) DESC
            LIMIT 3
        ");
        $showcats = $show_stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($showcats as $sc) {
            $prods = [];
            $pp = $pdo->prepare("
                SELECT p.product_id AS id, p.name, c.category_name,
                       p.image AS primary_image, p.price AS price_ghs,
                       p.original_price AS compare_at_price_ghs,
                       p.stock_quantity AS stock_qty, p.average_rating AS avg_rating,
                       p.has_multiple_images,
                       (DATEDIFF(NOW(), p.created_at) <= 21) AS is_new_arrival
                FROM products p
                LEFT JOIN categories c ON c.category_id = p.category_id
                WHERE p.status = 'published' AND p.category_id = ?
                ORDER BY p.is_featured DESC, p.product_id DESC
                LIMIT 4
            ");
            $pp->execute([$sc['category_id']]);
            $prods = $pp->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($prods)) {
                $categoryShowcase[] = ['category' => $sc, 'products' => $prods];
            }
        }
    } catch (PDOException $e) {}

    // ── POPUP ──────────────────────────────────────
    $popup = [
        'enabled' => $settings['promo_popup_enabled'] ?? '0',
        'image'   => !empty($settings['promo_popup_image']) ? getProductImage($settings['promo_popup_image']) : '',
        'title'   => $settings['promo_popup_title'] ?? 'Welcome to Our Store!',
        'desc'    => $settings['promo_popup_content'] ?? '',
        'link'    => $settings['promo_popup_btn_link'] ?? 'shop.php',
        'btn_text'=> $settings['promo_popup_btn_text'] ?? 'Shop Now',
    ];
    $popup['type'] = !empty($popup['image']) ? 'promo' : 'newsletter';
    $popup['frequency'] = (int)(is_numeric($settings['promo_popup_frequency'] ?? '') ? $settings['promo_popup_frequency'] : 1);
    if ($popup['frequency'] < 1) $popup['frequency'] = 1;
    ?>

    <section class="featured">
        <div class="container">
            <div class="sec-head reveal">
            <div class="sec-title-box">
                <div class="sec-over" style="color: var(--red); font-size: 10px; font-weight: 800; letter-spacing: 0.15em; margin-bottom: 8px;">
                    <?= htmlspecialchars($settings['home_deals_eyebrow'] ?? 'EXCLUSIVE OPPORTUNITY HUB') ?>
                </div>
                <h2 class="hero-heading" style="color: var(--ink); font-size: clamp(24px, 4vw, 38px); margin-bottom: 0; line-height: 1;">
                    <?= htmlspecialchars($settings['home_deals_title'] ?? 'FLASH DEALS & DROPS') ?>
                </h2>
            </div>
                <a href="shop.php" style="font-family: var(--f-semi); font-size: 12px; text-transform: uppercase; color: var(--mid-gray); font-weight: 700; text-decoration: none; border-bottom: 1px solid var(--light-gray); padding-bottom: 4px;">See all products →</a>
            </div>

            <div class="product-grid">
                <?php
                if (!empty($all_products)):
                    foreach ($all_products as $p):
                        require 'includes/product-card.php';
                    endforeach;
                else: ?>
                    <p>No products found.</p>
                <?php endif; ?>
            </div>

            <?php if ($pagination['totalPages'] > 1): ?>
            <div class="shop-pagination" style="margin-top: 32px;">
                <?php if ($pagination['hasPrev']): ?>
                    <a href="index.php?page=<?= $pagination['page'] - 1 ?>" class="page-btn">&laquo; Prev</a>
                <?php endif; ?>
                <?php
                $start = max(1, $pagination['page'] - 2);
                $end = min($pagination['totalPages'], $pagination['page'] + 2);
                if ($start > 1) echo '<span class="page-dots">...</span>';
                for ($i = $start; $i <= $end; $i++):
                    $isActive = $i === $pagination['page'];
                ?>
                    <a href="index.php?page=<?= $i ?>" class="page-btn <?= $isActive ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor;
                if ($end < $pagination['totalPages']) echo '<span class="page-dots">...</span>';
                ?>
                <?php if ($pagination['hasNext']): ?>
                    <a href="index.php?page=<?= $pagination['page'] + 1 ?>" class="page-btn">Next &raquo;</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- BESTSELLERS ROW -->
    <section class="products-sec">
        <div class="container">
            <div class="sec-head reveal">
            <div class="sec-title-box">
                <div class="sec-over">Hand-picked</div>
                <h2 class="hero-heading" style="color: var(--ink); margin-bottom: 0; line-height: 0.85;">Bestsellers</h2>
            </div>
                <div style="display: flex; align-items: center; gap: 24px;">
                    <div class="slider-nav">
                        <button class="slider-nav-btn prev" id="slide-prev" aria-label="Previous">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                        </button>
                        <button class="slider-nav-btn next" id="slide-next" aria-label="Next">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                        </button>
                    </div>
                    <a href="shop.php" class="btn-ghost">Full catalogue <span class="arr">→</span></a>
                </div>
            </div>

            <div class="slider-container" style="position: relative; width: 100%; overflow: hidden;">
                <div class="slider-viewport" id="bestsellers-slider" style="overflow-x: auto !important; scroll-snap-type: x mandatory !important; display: flex !important; -webkit-overflow-scrolling: touch !important; scrollbar-width: none !important;">
                    <div class="slider-track" style="display: flex !important; flex-wrap: nowrap !important; gap: 12px !important; padding: 10px 0 !important; width: max-content !important;">
                        <?php foreach ($bestsellers as $p): ?>
                            <?php require 'includes/product-card.php'; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CATEGORY SHOWCASE SECTIONS -->
    <?php if (!empty($categoryShowcase)): ?>
        <?php foreach ($categoryShowcase as $showcase): ?>
            <section class="products-sec" style="border-top: 1px solid var(--border-color); padding: 60px 0;">
                <div class="container">
                    <div class="sec-head reveal">
                        <div class="sec-title-box">
                            <div class="sec-over" style="color: var(--red); font-size: 10px; font-weight: 800; letter-spacing: 0.15em; margin-bottom: 8px;">
                                EXPLORE CATEGORY
                            </div>
                            <h2 class="hero-heading" style="color: var(--ink); margin-bottom: 0; line-height: 0.85;">
                                <?= htmlspecialchars(strtoupper($showcase['category']['name'])) ?>
                            </h2>
                        </div>
                        <a href="shop.php?category=<?= urlencode($showcase['category']['name']) ?>" style="font-family: var(--f-semi); font-size: 12px; text-transform: uppercase; color: var(--mid-gray); font-weight: 700; text-decoration: none; border-bottom: 1px solid var(--light-gray); padding-bottom: 4px;">Shop All <?= htmlspecialchars($showcase['category']['name']) ?> →</a>
                    </div>

                    <div class="product-grid">
                        <?php foreach ($showcase['products'] as $p): ?>
                            <?php require 'includes/product-card.php'; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if ($popup['enabled'] == '1'): ?>
    <div id="promo-popup" class="promo-overlay" style="display: none;">
        <div class="promo-modal popup-mode-<?= $popup['type'] ?>">
            <button id="close-promo" class="promo-close" aria-label="Close popup">&times;</button>

            <div class="promo-content">
                <?php if ($popup['type'] === 'promo'): ?>
                    <div class="promo-img-side">
                        <img src="<?= htmlspecialchars($popup['image']) ?>" alt="Promotion">
                    </div>
                    <div class="promo-text-side">
                        <div class="promo-label">SPECIAL OFFER</div>
                        <h2 class="promo-title"><?= htmlspecialchars($popup['title']) ?></h2>
                        <p class="promo-desc"><?= htmlspecialchars($popup['desc']) ?></p>
                        <a href="<?= htmlspecialchars($popup['link']) ?>" class="btn-promo"><?= htmlspecialchars($popup['btn_text']) ?></a>
                        <div class="newsletter-footer">
                            <label class="dont-show-container">
                                <input type="checkbox" id="dont-show-check">
                                <span class="checkmark"></span>
                                Don't show this popup anymore.
                            </label>
                        </div>
                    </div>

                <?php else: ?>
                    <div class="promo-top-img">
                        <?php
                        $imgUrl = $popup['image'] ?: 'https://images.unsplash.com/photo-1512428559087-560fa5ceab42?q=80&w=2070&auto=format&fit=crop';
                        $finalImg = (strpos($imgUrl, 'http') === 0 || strpos($imgUrl, '//') === 0) ? $imgUrl : APP_URL . '/' . $imgUrl;
                        ?>
                        <img src="<?= $finalImg ?>" alt="Newsletter">
                    </div>
                    <div class="promo-text-side" style="padding: 32px 40px; text-align: center;">
                        <h2 class="newsletter-title"><?= htmlspecialchars($popup['title']) ?></h2>
                        <p style="font-size: 14px; color: var(--mid-gray); margin-top: -4px;"><?= htmlspecialchars($popup['desc']) ?></p>

                        <form id="newsletter-form" class="newsletter-pill-form">
                            <div class="pill-container">
                                <input type="email" name="email" placeholder="Email Address..." required class="pill-input">
                                <button type="submit" class="pill-submit">Subscribe</button>
                            </div>
                            <div id="newsletter-msg" style="margin-top: 16px; font-family: var(--f-mono); font-size: 11px; font-weight: 800; display: none;"></div>
                        </form>

                        <div class="newsletter-footer">
                            <p>By subscribing, you agree to our <a href="terms.php">Terms of Use</a> and <a href="privacy.php">Privacy Policy</a>.</p>

                            <label class="dont-show-container">
                                <input type="checkbox" id="dont-show-check">
                                <span class="checkmark"></span>
                                Don't show this popup anymore.
                            </label>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const popup = document.getElementById('promo-popup');
        if (!popup) return;

        const closeBtn = document.getElementById('close-promo');
        const REFRESH_KEY = 'aso_popup_visit_count';
        const DISABLE_KEY = 'aso_popup_disabled';
        const frequency = <?= $popup['frequency'] ?>;

        if (localStorage.getItem(DISABLE_KEY) === 'true') return;

        let visitCount = parseInt(localStorage.getItem(REFRESH_KEY) || '0');
        visitCount++;
        localStorage.setItem(REFRESH_KEY, visitCount.toString());

        const shouldShow = () => {
            if (visitCount === 1) return true;
            return (visitCount - 1) % frequency === 0;
        };

        if (shouldShow()) {
            setTimeout(() => {
                popup.style.display = 'flex';
                document.documentElement.classList.add('is-locked');
            }, 1500);
        }

        const closePopup = () => {
            const dontShowCheck = document.getElementById('dont-show-check');
            if (dontShowCheck && dontShowCheck.checked) {
                localStorage.setItem(DISABLE_KEY, 'true');
            }
            popup.style.opacity = '0';
            popup.style.transition = 'opacity 0.3s ease';
            document.documentElement.classList.remove('is-locked');
            setTimeout(() => {
                popup.style.display = 'none';
            }, 300);
        };

        if (closeBtn) closeBtn.addEventListener('click', closePopup);
        popup.addEventListener('click', (e) => { if (e.target === popup) closePopup(); });

        const nlForm = document.getElementById('newsletter-form');
        if (nlForm) {
            nlForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const btn = nlForm.querySelector('button');
                const msg = document.getElementById('newsletter-msg');
                const email = nlForm.email.value;

                btn.innerText = 'WAIT...';
                btn.disabled = true;

                try {
                    const response = await fetch('ajax/subscribe_newsletter.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({ email })
                    });
                    const result = await response.json();
                    msg.style.display = 'block';
                    msg.innerText = result.message;
                    msg.style.color = result.success ? '#00A854' : 'var(--red)';

                    if (result.success) {
                        localStorage.setItem(DISABLE_KEY, 'true');
                        nlForm.style.display = 'none';
                        setTimeout(closePopup, 1500);
                    } else {
                        btn.innerText = 'Subscribe';
                        btn.disabled = false;
                    }
                } catch (err) {
                    msg.style.display = 'block';
                    msg.innerText = 'CONNECTION ERROR';
                    btn.disabled = false;
                }
            });
        }
    });
    </script>
    <?php endif; ?>

    <!-- SUPPORT BANNER section -->
    <?php require 'includes/support-card.php'; ?>
</main>

<?php include 'includes/footer.php'; ?>
