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
    header('Location: categories.php');
    exit();
}

// Fetch category
try {
    $stmt = $pdo->prepare('SELECT * FROM categories WHERE category_id = ?');
    $stmt->execute([$category_id]);
    $category = $stmt->fetch();
    if (!$category) {
        header('Location: categories.php');
        exit();
    }
} catch (PDOException $e) {
    error_log('Category fetch error: ' . $e->getMessage());
    header('Location: categories.php');
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
$where = ['p.category_id = ?'];
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
        $stmt = $pdo->prepare("SELECT p.* FROM products p $where_sql ORDER BY $order_sql LIMIT $per_page OFFSET $offset");
        $stmt->execute($params);
        $products = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('Fetch products error: ' . $e->getMessage());
    }
}

// Handle AJAX add to cart for category products
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_add_to_cart'])) {
    $ajax_product_id = (int)($_POST['product_id'] ?? 0);
    $ajax_quantity = (int)($_POST['quantity'] ?? 1);

    if ($ajax_product_id > 0 && $ajax_quantity > 0) {
        try {
            // Get product details for validation
            $stmt = $pdo->prepare("SELECT stock_quantity, name FROM products WHERE product_id = ?");
            $stmt->execute([$ajax_product_id]);
            $ajax_product = $stmt->fetch();

            if ($ajax_product && $ajax_quantity <= $ajax_product['stock_quantity']) {
                if (isset($_SESSION['user_id'])) {
                    // User is logged in - add to database cart
                    $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity)
                                         VALUES (?, ?, ?)
                                         ON DUPLICATE KEY UPDATE quantity = quantity + ?");
                    $stmt->execute([$_SESSION['user_id'], $ajax_product_id, $ajax_quantity, $ajax_quantity]);
                } else {
                    // User is not logged in - add to session cart
                    if (!isset($_SESSION['cart'])) {
                        $_SESSION['cart'] = [];
                    }

                    if (isset($_SESSION['cart'][$ajax_product_id])) {
                        $_SESSION['cart'][$ajax_product_id] += $ajax_quantity;
                    } else {
                        $_SESSION['cart'][$ajax_product_id] = $ajax_quantity;
                    }
                }

                echo json_encode(['success' => true, 'message' => $ajax_product['name'] . ' (Qty: ' . $ajax_quantity . ') added to cart!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid product or quantity']);
            }
        } catch(PDOException $e) {
            error_log("Error adding to cart via AJAX: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error adding product to cart']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
    }
    exit();
}
?>

<?php include 'includes/header.php'; ?>

<div class="container py-4">


    <div class="row g-4">
        <!-- Sidebar filters -->
        <div class="col-lg-3">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Filter Products</h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="category.php">
                        <input type="hidden" name="id" value="<?php echo $category_id; ?>">
                        <div class="mb-3">
                            <label class="form-label">Search</label>
                            <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search in this category">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Price Range</label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <input type="number" class="form-control" name="min_price" value="<?php echo $min_price ?: ''; ?>" placeholder="Min" min="0" step="0.01">
                                </div>
                                <div class="col-6">
                                    <input type="number" class="form-control" name="max_price" value="<?php echo $max_price ?: ''; ?>" placeholder="Max" min="0" step="0.01">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Sort By</label>
                            <select class="form-select" name="sort">
                                <option value="newest" <?php echo $sort_by==='newest'?'selected':''; ?>>Newest</option>
                                <option value="name" <?php echo $sort_by==='name'?'selected':''; ?>>Name (A-Z)</option>
                                <option value="price_low" <?php echo $sort_by==='price_low'?'selected':''; ?>>Price (Low to High)</option>
                                <option value="price_high" <?php echo $sort_by==='price_high'?'selected':''; ?>>Price (High to Low)</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter me-2"></i>Apply</button>
                        <a href="category.php?id=<?php echo $category_id; ?>" class="btn btn-outline-secondary w-100 mt-2"><i class="fas fa-times me-2"></i>Clear</a>
                    </form>
                </div>
            </div>
        </div>

        <!-- Products -->
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h2 class="mb-0"><?php echo htmlspecialchars($category['category_name']); ?></h2>
                    <?php if (!empty($category['description'])): ?>
                        <p class="text-muted mb-0 small"><?php echo htmlspecialchars($category['description']); ?></p>
                    <?php endif; ?>
                </div>
                <span class="badge bg-primary">Total: <?php echo $total_products; ?></span>
            </div>

            <?php if (empty($products)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>No products found in this category.
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($products as $p): ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card product-card h-100">
                                <img src="assets/images/<?php echo htmlspecialchars($p['image'] ?? 'placeholder.jpg'); ?>" class="card-img-top product-image" alt="<?php echo htmlspecialchars($p['name']); ?>">
                                <div class="card-body d-flex flex-column">
                                    <h6 class="product-title">
                                        <a href="product.php?id=<?php echo $p['product_id']; ?>" class="text-decoration-none text-dark"><?php echo htmlspecialchars($p['name']); ?></a>
                                    </h6>
                                    <p class="product-price mb-3"><?php echo formatCurrency($p['price']); ?></p>
                                    <div class="mt-auto">
                                        <?php if ((int)$p['stock_quantity'] > 0): ?>
                                            <button class="btn btn-primary w-100 category-add-to-cart-btn"
                                                    data-product-id="<?php echo $p['product_id']; ?>"
                                                    data-product-name="<?php echo htmlspecialchars($p['name']); ?>">
                                                <i class="fas fa-cart-plus me-2"></i>Add to Cart
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-secondary w-100" disabled>Out of Stock</button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Category pagination">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">&laquo; Prev</a>
                                </li>
                            <?php endif; ?>
                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">Next &raquo;</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Category page Add to Cart functionality
document.addEventListener('DOMContentLoaded', function() {
    let isProcessing = false; // Prevent multiple simultaneous requests

    // Handle category product Add to Cart buttons
    document.querySelectorAll('.category-add-to-cart-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            // Prevent multiple clicks
            if (isProcessing) return;
            isProcessing = true;

            const productId = this.getAttribute('data-product-id');
            const productName = this.getAttribute('data-product-name');

            // Disable button and show loading
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Adding...';

            // Add to cart via AJAX (quantity = 1)
            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `ajax_add_to_cart=1&product_id=${productId}&quantity=1`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success toast with 2 second duration
                    showToast(data.message, 'success', 1000);
                } else {
                    showToast(data.message, 'danger', 2000);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error adding product to cart', 'danger', 2000);
            })
            .finally(() => {
                // Re-enable button and reset processing flag
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-cart-plus me-2"></i>Add to Cart';
                isProcessing = false;
            });
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
