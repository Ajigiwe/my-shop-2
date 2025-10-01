<?php
/**
 * Storefront: Categories
 * - Public page to list all categories with product counts
 * - Links to category detail pages
 */
// Include database connection
require_once 'includes/db.php';

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set page title
$page_title = 'Categories';

// Fetch all categories with product counts
$categories = [];
try {
    $stmt = $pdo->query("SELECT c.*, COUNT(p.product_id) AS product_count
                         FROM categories c
                         LEFT JOIN products p ON p.category_id = c.category_id
                         GROUP BY c.category_id
                         ORDER BY c.category_name ASC");
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Error fetching categories: ' . $e->getMessage());
}
?>

<?php include 'includes/header.php'; ?>

<div class="container py-4">


    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Browse Categories</h2>
        <a href="shop.php" class="btn btn-outline-primary"><i class="fas fa-store me-2"></i>Go to Shop</a>
    </div>

    <?php if (empty($categories)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>No categories found.
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($categories as $cat): ?>
                <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                    <a href="category.php?id=<?php echo $cat['category_id']; ?>" class="text-decoration-none">
                        <div class="card h-100 product-card">
                            <img src="assets/images/category_<?php echo $cat['category_id']; ?>.jpg" 
                                 class="card-img-top product-image" 
                                 onerror="this.src='https://via.placeholder.com/600x400/e9ecef/6c757d?text=<?php echo urlencode($cat['category_name']); ?>'"
                                 alt="<?php echo htmlspecialchars($cat['category_name']); ?>">
                            <div class="card-body text-center">
                                <h5 class="product-title mb-1"><?php echo htmlspecialchars($cat['category_name']); ?></h5>
                                <p class="text-muted mb-0"><?php echo (int)$cat['product_count']; ?> products</p>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
