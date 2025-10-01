<?php
/**
 * Storefront: Shop Listing
 * - Lists all products with pagination
 * - Supports category filtering
 */

require_once 'includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get category filter
$category_name = sanitizeInput($_GET['category'] ?? '');
$category_filter = '';

// Set page title
if ($category_name) {
    $page_title = htmlspecialchars($category_name) . ' - Shop';
} else {
    $page_title = 'Shop';
}

// Get pagination parameters
$page = (int)($_GET['page'] ?? 1);
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Build WHERE clause for category filtering
$where_conditions = ['p.stock_quantity > 0'];
$params = [];

if ($category_name) {
    $where_conditions[] = 'c.category_name = ?';
    $params[] = $category_name;
}

$where_sql = 'WHERE ' . implode(' AND ', $where_conditions);

// Get total products count
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM products p
                          JOIN categories c ON p.category_id = c.category_id $where_sql");
    $stmt->execute($params);
    $total_products = $stmt->fetch()['total'];
    $total_pages = ceil($total_products / $per_page);
} catch(PDOException $e) {
    error_log("Error getting product count: " . $e->getMessage());
    $total_products = 0;
    $total_pages = 1;
}

// Get products for current page
$products = [];
try {
    $stmt = $pdo->prepare("SELECT p.*, c.category_name FROM products p
                          JOIN categories c ON p.category_id = c.category_id
                          $where_sql ORDER BY p.created_at DESC LIMIT $per_page OFFSET $offset");
    $stmt->execute($params);
    $products = $stmt->fetchAll();
} catch(PDOException $e) {
    error_log("Error fetching products: " . $e->getMessage());
}

// Get all categories for filter sidebar
$categories = [];
try {
    $stmt = $pdo->query("SELECT c.*, COUNT(p.product_id) as product_count
                        FROM categories c
                        LEFT JOIN products p ON c.category_id = p.category_id
                        GROUP BY c.category_id, c.category_name
                        ORDER BY c.category_name");
    $categories = $stmt->fetchAll();
} catch(PDOException $e) {
    error_log("Error fetching categories: " . $e->getMessage());
}

// Handle AJAX add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_add_to_cart'])) {
    $ajax_product_id = (int)($_POST['product_id'] ?? 0);
    $ajax_quantity = (int)($_POST['quantity'] ?? 1);

    if ($ajax_product_id > 0 && $ajax_quantity > 0) {
        try {
            $stmt = $pdo->prepare("SELECT stock_quantity, name FROM products WHERE product_id = ?");
            $stmt->execute([$ajax_product_id]);
            $ajax_product = $stmt->fetch();

            if ($ajax_product && $ajax_quantity <= $ajax_product['stock_quantity']) {
                if (isset($_SESSION['user_id'])) {
                    $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity)
                                         VALUES (?, ?, ?)
                                         ON DUPLICATE KEY UPDATE quantity = quantity + ?");
                    $stmt->execute([$_SESSION['user_id'], $ajax_product_id, $ajax_quantity, $ajax_quantity]);
                } else {
                    if (!isset($_SESSION['cart'])) {
                        $_SESSION['cart'] = [];
                    }
                    if (isset($_SESSION['cart'][$ajax_product_id])) {
                        $_SESSION['cart'][$ajax_product_id] += $ajax_quantity;
                    } else {
                        $_SESSION['cart'][$ajax_product_id] = $ajax_quantity;
                    }
                }
                echo json_encode(['success' => true, 'message' => $ajax_product['name'] . ' added to cart!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid product or quantity']);
            }
        } catch(PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Error adding product to cart']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - ASO Online Market</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Other CSS and JS includes -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<!-- Page Header -->
<section class="page-header py-5" style="background: linear-gradient(135deg, #f5f5dc 0%, rgba(245, 245, 220, 0.9) 100%);">
    <div class="container">
        <div class="row">
            <div class="col-12 text-center">
                <h1 class="display-4 fw-bold text-dark mb-3">
                    <?php if ($category_name): ?>
                        <?php echo htmlspecialchars($category_name); ?>
                    <?php else: ?>
                        All Products
                    <?php endif; ?>
                </h1>
                <p class="lead text-muted">
                    <?php if ($category_name): ?>
                        Discover quality <?php echo strtolower(htmlspecialchars($category_name)); ?> products
                    <?php else: ?>
                        Discover our complete collection of quality products
                    <?php endif; ?>
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Shop Content -->
<section class="py-5">
    <div class="container">
        <div class="row">
            <!-- Sidebar Filters -->
            <div class="col-lg-3">
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="fas fa-filter me-2"></i>Categories
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <a href="shop.php"
                               class="list-group-item list-group-item-action <?php echo !$category_name ? 'active' : ''; ?>">
                                <i class="fas fa-th-large me-2"></i>All Products
                                <?php if ($total_products > 0): ?>
                                    <span class="badge bg-primary float-end"><?php echo $total_products; ?></span>
                                <?php endif; ?>
                            </a>
                            <?php foreach ($categories as $category): ?>
                                <a href="shop.php?category=<?php echo urlencode($category['category_name']); ?>"
                                   class="list-group-item list-group-item-action <?php echo $category_name === $category['category_name'] ? 'active' : ''; ?>">
                                    <i class="fas fa-box me-2"></i><?php echo htmlspecialchars($category['category_name']); ?>
                                    <?php if ($category['product_count'] > 0): ?>
                                        <span class="badge bg-secondary float-end"><?php echo $category['product_count']; ?></span>
                                    <?php endif; ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Products Grid -->
            <div class="col-lg-9">
                <!-- Results Info -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <p class="text-muted mb-0">
                            Showing <?php echo count($products); ?> of <?php echo $total_products; ?> products
                            <?php if ($category_name): ?>
                                in <?php echo htmlspecialchars($category_name); ?>
                            <?php endif; ?>
                        </p>
                    </div>
                  
                </div>

                <!-- Products Grid -->
                <?php if (empty($products)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                        <h4>No Products Found</h4>
                        <p class="text-muted">
                            <?php if ($category_name): ?>
                                No products available in <?php echo htmlspecialchars($category_name); ?> category.
                            <?php else: ?>
                                No products available at the moment.
                            <?php endif; ?>
                        </p>
                        <?php if ($category_name): ?>
                            <a href="shop.php" class="btn btn-primary">Browse All Products</a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="row g-4" id="productsContainer">
                        <?php foreach ($products as $product): ?>
                            <div class="col-lg-3 col-md-4 col-sm-6">
                                <div class="card product-card h-100" style="max-height: 320px;">
                                    <div class="product-image-container">
                                        <img src="assets/images/<?php echo htmlspecialchars($product['image'] ?? 'placeholder.jpg'); ?>"
                                             class="card-img-top product-image"
                                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                                             style="height: 150px; object-fit: cover;">
                                        <?php if ($product['stock_quantity'] <= 0): ?>
                                            <div class="out-of-stock-overlay">
                                                <span class="out-of-stock-text">Out of Stock</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="card-body d-flex flex-column p-2">
                                        <div class="product-info">
                                            <h6 class="card-title product-title mb-1" style="font-size: 0.9rem; line-height: 1.2;">
                                                <a href="product.php?id=<?php echo $product['product_id']; ?>"
                                                   class="text-decoration-none text-dark">
                                                    <?php echo htmlspecialchars(substr($product['name'], 0, 35) . (strlen($product['name']) > 35 ? '...' : '')); ?>
                                                </a>
                                            </h6>
                                            <p class="text-muted small mb-1" style="font-size: 0.75rem;"><?php echo htmlspecialchars($product['category_name']); ?></p>
                                            <div class="product-price fw-bold" style="font-size: 1rem;"><?php echo formatCurrency($product['price']); ?></div>
                                        </div>

                                        <div class="mt-auto pt-2">
                                            <?php if ($product['stock_quantity'] > 0): ?>
                                                <button class="btn btn-primary btn-sm w-100 shop-add-to-cart-btn"
                                                        data-product-id="<?php echo $product['product_id']; ?>"
                                                        data-product-name="<?php echo htmlspecialchars($product['name']); ?>"
                                                        style="font-size: 0.8rem; padding: 0.4rem 0.6rem;">
                                                    <i class="fas fa-cart-plus me-1" style="font-size: 0.7rem;"></i>Add to Cart
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-secondary btn-sm w-100" disabled style="font-size: 0.8rem; padding: 0.4rem 0.6rem;">
                                                    <i class="fas fa-times me-1" style="font-size: 0.7rem;"></i>Out of Stock
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav aria-label="Product pagination" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $category_name ? '&category=' . urlencode($category_name) : ''; ?>">
                                            <i class="fas fa-chevron-left"></i> Previous
                                        </a>
                                    </li>
                                <?php endif; ?>

                                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?><?php echo $category_name ? '&category=' . urlencode($category_name) : ''; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>

                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $category_name ? '&category=' . urlencode($category_name) : ''; ?>">
                                            Next <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>

<!-- Bootstrap JS (loaded at end for better performance) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/script.js"></script>

<script>
// Shop page Add to Cart functionality
document.addEventListener('DOMContentLoaded', function() {
    // Handle shop product Add to Cart buttons
    document.querySelectorAll('.shop-add-to-cart-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();

            const productId = this.getAttribute('data-product-id');
            const productName = this.getAttribute('data-product-name');

            // Disable button and show loading state
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Adding...';

            // Make AJAX request to add item to cart
            fetch('ajax/add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `product_id=${productId}&quantity=1`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success', 2000);

                    // Update cart count in navbar if it exists
                    const cartCountElement = document.querySelector('.cart-count');
                    if (cartCountElement) {
                        const currentCount = parseInt(cartCountElement.textContent) || 0;
                        cartCountElement.textContent = currentCount + 1;
                        cartCountElement.style.display = 'inline';
                    }
                } else {
                    showToast(data.message || 'Error adding to cart', 'danger', 2000);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error adding product to cart', 'danger', 2000);
            })
            .finally(() => {
                // Re-enable button
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-cart-plus me-2"></i>Add to Cart';
            });
        });
    });
});
</script>
</body>
</html>
