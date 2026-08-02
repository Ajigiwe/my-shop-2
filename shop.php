<?php
/**
 * Storefront: Shop Listing (Avazonia)
 */
require_once 'includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Filters
$category_name = sanitizeInput($_GET['category'] ?? '');
$tag_filter    = sanitizeInput($_GET['tag'] ?? '');
$search_q      = sanitizeInput($_GET['q'] ?? '');
$price_min     = isset($_GET['price_min']) ? max(0, (float)$_GET['price_min']) : 0;
$price_max     = isset($_GET['price_max']) && $_GET['price_max'] !== '' ? (float)$_GET['price_max'] : null;

if ($search_q !== '') {
    $page_title = 'Search: "' . htmlspecialchars($search_q) . '"';
} elseif ($tag_filter !== '') {
    $page_title = '#' . htmlspecialchars($tag_filter);
} else {
    $page_title = $category_name ? htmlspecialchars($category_name) : 'The Drop';
}

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

// Build WHERE
$where_conditions = [];
$params = [];
if ($category_name) {
    $where_conditions[] = 'c.category_name = ?';
    $params[] = $category_name;
}
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
$where_sql = $where_conditions ? ('WHERE ' . implode(' AND ', $where_conditions)) : '';

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
$products = [];
try {
    $stmt = $pdo->prepare("SELECT p.*, c.category_name
                            FROM products p
                            JOIN categories c ON p.category_id = c.category_id
                            $where_sql
                            ORDER BY $order_by LIMIT $per_page OFFSET $offset");
    $stmt->execute($params);
    $products = $stmt->fetchAll();
} catch(PDOException $e) {
    error_log("Error fetching products: " . $e->getMessage());
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

<main>
    <?php
    $section_title = $category_name ? strtoupper(htmlspecialchars($category_name)) : htmlspecialchars($page_title);
    $section_eyebrow = 'THE DROP';
    require 'includes/shop-section.php';
    ?>
</main>

<?php include 'includes/footer.php'; ?>
