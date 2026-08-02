<?php
/**
 * Deals (Avazonia) — Deals / Pre-Orders / Drop Shipping tabs
 */
require_once 'includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$tab = sanitizeInput($_GET['tab'] ?? 'deals');
$valid_tabs = ['deals', 'preorders', 'dropshipping'];
if (!in_array($tab, $valid_tabs, true)) $tab = 'deals';

// User wishlist
$user_wishlist = [];
if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT product_id FROM wishlist WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user_wishlist = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch(PDOException $e) {}
}

$products = [];
if ($tab === 'deals') {
    try {
        $stmt = $pdo->query("SELECT p.*, c.category_name,
            (SELECT GROUP_CONCAT(image_path ORDER BY is_primary DESC, image_id ASC)
             FROM product_images WHERE product_id = p.product_id) as all_images
            FROM products p JOIN categories c ON p.category_id = c.category_id
            WHERE p.stock_quantity > 0 AND p.original_price > p.price
            ORDER BY (p.original_price - p.price) DESC LIMIT 24");
        $products = $stmt->fetchAll();
    } catch(PDOException $e) {
        error_log("Error fetching deals: " . $e->getMessage());
    }
}

$page_title = 'Deals';
include 'includes/header.php';
?>

<main>
    <?php
    $pagination = [
        'page'       => 1,
        'totalPages' => 1,
        'total'      => count($products),
        'hasPrev'    => false,
        'hasNext'    => false,
    ];
    $section_title = 'DEALS';
    $section_eyebrow = 'SAVE BIG';
    $empty_msg = 'No active deals right now. New deals drop all the time — check back soon.';
    require 'includes/shop-section.php';
    ?>
</main>

<?php include 'includes/footer.php'; ?>
