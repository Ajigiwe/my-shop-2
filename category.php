<?php
/**
 * Storefront: Category Listing
 * - Lists products for a single category with search, price range filtering, sorting, and pagination
 */
// Include database connection
require_once 'includes/db.php';

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$category_id = (int)($_GET['id'] ?? 0);
if ($category_id <= 0) {
    header('Location: shop.php');
    exit();
}

// Fetch category
try {
    $stmt = $pdo->prepare('SELECT * FROM categories WHERE category_id = ?');
    $stmt->execute([$category_id]);
    $category = $stmt->fetch();
    if (!$category) {
        header('Location: shop.php');
        exit();
    }
} catch (PDOException $e) {
    error_log('Category fetch error: ' . $e->getMessage());
    header('Location: shop.php');
    exit();
}

// Set page title
$page_title = $category['category_name'];

// Filtering and pagination
$search = sanitizeInput($_GET['search'] ?? '');
$min_price = (float)($_GET['min_price'] ?? 0);
$max_price = (float)($_GET['max_price'] ?? 0);
$sort_by = sanitizeInput($_GET['sort'] ?? 'newest');
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Build WHERE clause safely using parameter binding
$where = ['p.category_id = ?', "p.status = 'published'"];
$params = [$category_id];

if ($search) {
    $where[] = '(p.name LIKE ? OR p.description LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($min_price > 0) {
    $where[] = 'p.price >= ?';
    $params[] = $min_price;
}
if ($max_price > 0 && $max_price >= $min_price) {
    $where[] = 'p.price <= ?';
    $params[] = $max_price;
}
$where_sql = 'WHERE ' . implode(' AND ', $where);

// Supported sort options
$sort_map = [
    'newest' => 'p.created_at DESC',
    'price_low' => 'p.price ASC',
    'price_high' => 'p.price DESC',
    'name' => 'p.name ASC',
];
$order_sql = $sort_map[$sort_by] ?? $sort_map['newest'];

// Total count (for pagination controls)
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM products p $where_sql");
    $stmt->execute($params);
    $total_products = (int)$stmt->fetch()['total'];
    $total_pages = (int)ceil($total_products / $per_page);
} catch (PDOException $e) {
    error_log('Count products error: ' . $e->getMessage());
    $total_products = 0;
    $total_pages = 0;
}

// Fetch products
$products = [];
if ($total_products > 0) {
    try {
        $stmt = $pdo->prepare("SELECT p.*, c.category_name FROM products p JOIN categories c ON c.category_id = p.category_id $where_sql ORDER BY $order_sql LIMIT $per_page OFFSET $offset");
        $stmt->execute($params);
        $products = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('Fetch products error: ' . $e->getMessage());
    }
}

$pagination = [
    'page'       => $page,
    'totalPages' => max(1, $total_pages),
    'total'      => $total_products,
    'hasPrev'    => $page > 1,
    'hasNext'    => $page < $total_pages,
];

include 'includes/header.php';
?>

<main>
    <?php
    $section_title = strtoupper(htmlspecialchars($category['category_name']));
    $section_eyebrow = 'CATEGORY';
    $empty_msg = 'No products found in this category.';
    require 'includes/shop-section.php';
    ?>
</main>

<?php include 'includes/footer.php'; ?>
