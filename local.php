<?php
/**
 * Storefront: Made in Ghana (Avazonia)
 * Dedicated landing for locally-made / locally-sourced goods.
 * Lists products flagged product_section = 'local'.
 */
require_once 'includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$page_title = 'Made in Ghana';

// Filters
$search_q = sanitizeInput($_GET['q'] ?? '');
$price_min = isset($_GET['price_min']) ? max(0, (float)$_GET['price_min']) : 0;
$price_max = isset($_GET['price_max']) && $_GET['price_max'] !== '' ? (float)$_GET['price_max'] : null;

// Pagination
$page     = (int)($_GET['page'] ?? 1);
$per_page = 24;
if (!empty($settings['products_per_page'])) { $per_page = (int)$settings['products_per_page']; }
$offset   = ($page - 1) * $per_page;

// Sort
$sort = sanitizeInput($_GET['sort'] ?? 'newest');
$order_by = 'p.created_at DESC';
if ($sort === 'price_asc')  $order_by = 'p.price ASC';
if ($sort === 'price_desc') $order_by = 'p.price DESC';
if ($sort === 'rating')     $order_by = 'p.average_rating DESC';

// Build WHERE (always restricted to local goods)
$where_conditions = ["p.product_section = 'local'"];
$params = [];
if ($search_q) {
    $where_conditions[] = '(p.name LIKE ? OR p.description LIKE ?)';
    $params[] = '%' . $search_q . '%';
    $params[] = '%' . $search_q . '%';
}
if ($price_min > 0) {
    $where_conditions[] = 'p.price >= ?';
    $params[] = $price_min;
}
if ($price_max !== null) {
    $where_conditions[] = 'p.price <= ?';
    $params[] = $price_max;
}
$where_sql = 'WHERE ' . implode(' AND ', $where_conditions);

// Totals
$total_products = 0;
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM products p JOIN categories c ON p.category_id = c.category_id $where_sql");
    $stmt->execute($params);
    $total_products = (int)$stmt->fetch()['total'];
} catch(PDOException $e) {
    $total_products = 0;
}
$total_pages = max(1, (int)ceil($total_products / $per_page));
$page = min(max(1, $page), $total_pages);
$offset = ($page - 1) * $per_page;

// Wishlist ids
$user_wishlist = [];
if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT product_id FROM wishlist WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user_wishlist = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch(PDOException $e) {}
}

// Products
$local_products = [];
try {
    $stmt = $pdo->prepare("SELECT p.*, c.category_name
                            FROM products p
                            JOIN categories c ON p.category_id = c.category_id
                            $where_sql
                            ORDER BY $order_by LIMIT $per_page OFFSET $offset");
    $stmt->execute($params);
    $local_products = $stmt->fetchAll();
} catch(PDOException $e) {
    error_log("Error fetching local products: " . $e->getMessage());
}

$pagination = [
    'page'       => $page,
    'totalPages' => $total_pages,
    'total'      => $total_products,
    'hasPrev'    => $page > 1,
    'hasNext'    => $page < $total_pages,
];

include 'includes/header.php';
?>

<style>
.local-hero { padding: 150px 0 60px; background: linear-gradient(180deg, #1a0f05 0%, #2c1a0a 60%, #3a2410 100%); color: #fff; position: relative; overflow: hidden; }
.local-hero::after { content: "🇬🇭"; position: absolute; right: -30px; bottom: -40px; font-size: 260px; opacity: 0.06; line-height: 1; }
.local-hero-inner { position: relative; z-index: 2; max-width: 700px; }
.local-eyebrow { font-family: var(--f-mono); font-size: 11px; letter-spacing: 0.14em; text-transform: uppercase; color: #f0c36a; margin-bottom: 18px; font-weight: 700; display: flex; align-items: center; gap: 10px; }
.local-eyebrow::before { content: ""; width: 34px; height: 2px; background: #f0c36a; }
.local-h1 { font-family: var(--f-display); font-weight: 900; font-size: clamp(44px, 8vw, 84px); letter-spacing: -0.04em; line-height: 0.88; margin-bottom: 24px; text-transform: uppercase; }
.local-h1 .gh { color: #f0c36a; }
.local-sub { font-size: 16px; line-height: 1.7; color: rgba(255,255,255,0.78); max-width: 620px; margin-bottom: 36px; }
.local-cta-row { display: flex; flex-wrap: wrap; gap: 14px; }
.local-cta-list { display: flex; flex-wrap: wrap; gap: 12px 28px; margin-top: 40px; padding-top: 32px; border-top: 1px solid rgba(255,255,255,0.12); }
.local-cta-list span { font-family: var(--f-mono); font-size: 10px; letter-spacing: 0.08em; text-transform: uppercase; color: rgba(255,255,255,0.55); display: flex; align-items: center; gap: 8px; }
</style>

<main>
    <section class="local-hero">
        <div class="container local-hero-inner">
            <div class="local-eyebrow">Proudly Ghanaian</div>
            <h1 class="local-h1">Made in<br><span class="local-h1">Ghana</span></h1>
            <p class="local-sub">Authentic local goods crafted, grown and produced right here in Ghana. Support homegrown artisans and businesses with products delivered nationwide — or anywhere in the world.</p>
            <div class="local-cta-row">
                <a href="#local-grid" class="btn-red">BROWSE LOCAL GOODS →</a>
                <a href="shop.php" class="btn-hero-secondary">View Full Store</a>
            </div>
            <div class="local-cta-list">
                <span>✅ Authentic Ghanaian craft</span>
                <span>🚚 Local &amp; international delivery</span>
                <span>💳 Pay in GHS via Paystack</span>
            </div>
        </div>
    </section>

    <?php
    $section_eyebrow = 'MADE IN GHANA';
    $section_title = strtoupper($search_q ? 'Search: "' . htmlspecialchars($search_q) . '"' : 'LOCAL GOODS');
    $empty_msg = 'No Made in Ghana products found yet.';
    $products = $local_products;
    $page_base_url = 'local.php';
    require 'includes/shop-section.php';
    ?>
</main>

<?php include 'includes/footer.php'; ?>