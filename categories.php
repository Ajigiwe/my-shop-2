<?php
/**
 * Storefront: Categories
 * - Public page to list all categories with product counts
 * - Links to category detail pages
 */
require_once 'includes/db.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$page_title = 'Categories';

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

<main>
    <section class="shop-content" style="padding: 120px 0 80px;">
        <div class="container">
            <div class="sec-head reveal" style="margin-bottom: 48px;">
                <div>
                    <div class="sec-over">THE DROPS</div>
                    <h2 class="hero-heading" style="color: var(--ink); margin-bottom: 8px; line-height: 0.85;">BROWSE CATEGORIES</h2>
                    <p style="font-family: var(--f-body); font-size: 14px; color: var(--mid-gray); margin-top: 12px;">Everything we drop, organised by category. Find your new favourite gear.</p>
                </div>
            </div>

            <?php if (empty($categories)): ?>
                <div style="text-align: center; padding: 60px 0; font-family: var(--f-body); color: var(--mid-gray);">
                    <h3 style="font-family: var(--f-display); font-weight: 900; font-size: 24px; color: var(--ink); text-transform: uppercase; margin-bottom: 12px;">No categories found</h3>
                    <p style="margin-bottom: 24px;">Categories will appear here once they are created.</p>
                    <a href="shop.php" class="btn-red">Go to shop</a>
                </div>
            <?php else: ?>
                <div class="category-grid">
                    <?php foreach ($categories as $i => $cat):
                        $is_hero = ($i === 0);
                        $image_path = !empty($cat['image']) ? 'assets/images/categories/' . $cat['image'] : null;
                        $bg = ($image_path && file_exists($image_path)) ? "url('" . $image_path . "')" : 'linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%)';
                    ?>
                        <a href="category.php?id=<?php echo $cat['category_id']; ?>" class="cat-tile <?php echo $is_hero ? 'cat-hero' : ''; ?>" style="background-image: <?php echo $bg; ?>;">
                            <span class="cat-label"><?php echo htmlspecialchars($cat['category_name']); ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>
