<?php
/**
 * User: Wishlist (Avazonia account layout)
 */
require_once '../includes/db.php';
require_once '../includes/functions.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$page_title = 'My Wishlist';

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
} catch(PDOException $e) {
    error_log("Error fetching user: " . $e->getMessage());
}

$products = [];
try {
    $stmt = $pdo->prepare("
        SELECT p.*, c.category_name, w.created_at as added_at,
        (SELECT GROUP_CONCAT(image_path ORDER BY is_primary DESC, image_id ASC) 
         FROM product_images 
         WHERE product_id = p.product_id) as all_images 
        FROM products p
        JOIN wishlist w ON p.product_id = w.product_id
        LEFT JOIN categories c ON p.category_id = c.category_id
        WHERE w.user_id = ? AND p.status = 'published'
        ORDER BY w.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $products = $stmt->fetchAll();
} catch(PDOException $e) {
    error_log("Error fetching wishlist: " . $e->getMessage());
}

$user_wishlist = array_column($products, 'product_id');

include '../includes/header.php';
?>

<section class="wishlist-page" style="padding: 100px 0 80px; background: #fafafa; min-height: 80vh;">
    <div class="container" style="max-width: 1100px;">

        <!-- Breadcrumb & Header -->
        <nav style="margin-bottom: 32px;">
            <div style="font-family: var(--f-mono); font-size: 10px; text-transform: uppercase; color: var(--mid-gray); letter-spacing: 0.1em; display: flex; align-items: center; gap: 8px;">
                <a href="<?php echo $base; ?>index.php" style="color: inherit; text-decoration: none;">ASO</a>
                <span>/</span>
                <a href="dashboard.php" style="color: inherit; text-decoration: none;">Account</a>
                <span>/</span>
                <span style="color: var(--ink);">Wishlist</span>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-top: 16px; flex-wrap: wrap; gap: 12px;">
                <h1 style="font-family: var(--f-display); font-weight: 800; font-size: 32px; margin: 0; color: var(--ink); letter-spacing: -0.02em;">My Favorites</h1>
                <div style="font-family: var(--f-mono); font-size: 11px; font-weight: 700; color: var(--mid-gray);"><?php echo count($products); ?> Items</div>
            </div>
        </nav>

        <div class="account-grid" style="display: grid; grid-template-columns: 240px 1fr; gap: 48px;">

            <!-- Sidebar -->
            <?php include '_sidebar.php'; ?>

            <!-- Wishlist Content -->
            <div class="wishlist-content" style="min-width: 0;">
                <?php if (empty($products)): ?>
                    <div style="padding: 80px 40px; text-align: center; background: #fff; border: 1px solid #eee; border-radius: 12px;">
                        <span style="font-size: 40px; display: block; margin-bottom: 16px;">💖</span>
                        <p style="font-weight: 700; font-size: 16px; color: var(--ink); margin-bottom: 8px;">Your wishlist is empty.</p>
                        <p style="font-size: 13px; color: var(--mid-gray); margin-bottom: 24px;">Save items you love and they'll appear here.</p>
                        <a href="<?php echo $base; ?>shop.php" style="display: inline-block; padding: 12px 32px; background: var(--ink); color: #fff; text-decoration: none; border-radius: 8px; font-weight: 700; font-size: 13px; text-transform: uppercase; letter-spacing: 0.05em;">Continue Shopping</a>
                    </div>
                <?php else: ?>
                    <div class="wishlist-list" style="display: flex; flex-direction: column; gap: 16px;">
                        <?php foreach ($products as $item):
                            $p_id = (int)$item['product_id'];
                            $p_name = $item['name'] ?? 'Product';
                            $p_price = (float)($item['price_ghs'] ?? $item['price'] ?? 0);
                            $p_compare = (float)($item['compare_at_price_ghs'] ?? $item['original_price'] ?? 0);
                            $img = getProductImage($item['primary_image'] ?? $item['image'] ?? '');
                        ?>
                            <div class="wish-card" id="wish-<?php echo $p_id; ?>" style="background: #fff; border: 1px solid #eee; border-radius: 12px; padding: 16px; display: grid; grid-template-columns: 80px 1fr auto; align-items: center; gap: 24px; transition: all 0.3s var(--ease);">
                                <a href="<?php echo $base; ?>product.php?id=<?php echo $p_id; ?>" style="width: 80px; height: 80px; background: #f9f9f9; border-radius: 8px; overflow: hidden; display: block; flex-shrink: 0;">
                                    <img src="<?php echo htmlspecialchars($img); ?>" onerror="this.src='<?php echo $base; ?>assets/images/placeholder.jpg'; this.onerror=null;" alt="<?php echo htmlspecialchars($p_name); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                </a>

                                <div style="min-width: 0;">
                                    <a href="<?php echo $base; ?>product.php?id=<?php echo $p_id; ?>" style="text-decoration: none; color: var(--ink); font-weight: 800; font-size: 16px; display: block; margin-bottom: 4px;"><?php echo htmlspecialchars($p_name); ?></a>
                                    <div style="display: flex; align-items: baseline; gap: 8px; flex-wrap: wrap;">
                                        <span style="font-weight: 900; color: var(--red); font-size: 18px;"><?php echo formatCurrency($p_price); ?></span>
                                        <?php if ($p_compare > $p_price): ?>
                                            <span style="text-decoration: line-through; color: var(--mid-gray); font-size: 12px;"><?php echo formatCurrency($p_compare); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($item['is_preorder'])): ?>
                                        <div style="display: inline-block; margin-top: 8px; padding: 2px 8px; background: #fff1f0; color: #f5222d; font-size: 10px; font-weight: 800; border-radius: 4px; border: 1px solid rgba(245,34,45,0.1); text-transform: uppercase;">Pre-order</div>
                                    <?php endif; ?>
                                </div>

                                <div class="wish-actions" style="display: flex; align-items: center; gap: 12px;">
                                    <button type="button" onclick="quickAddToCart(<?php echo $p_id; ?>, event)" style="padding: 10px 20px; background: var(--ink); color: #fff; border: none; border-radius: 100px; cursor: pointer; font-weight: 700; font-size: 12px; text-transform: uppercase;">Add to Cart</button>
                                    <button type="button" onclick="removeWishItem(<?php echo $p_id; ?>, this)" style="background: none; border: none; color: #ff4d4f; cursor: pointer; padding: 8px;" title="Remove" aria-label="Remove from wishlist">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 6L5 18M5 6l14 14"></path></svg>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<style>
    .wish-card:hover { border-color: var(--ink) !important; transform: translateY(-2px); box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
    .account-sidebar a:hover { background: var(--off); color: var(--ink) !important; opacity: 1 !important; }

    @media (max-width: 900px) {
        .wishlist-page { padding: 60px 0 60px !important; }
        .account-grid { grid-template-columns: 1fr !important; gap: 32px !important; }
        .account-sidebar { position: static !important; }
        h1 { font-size: 24px !important; }
    }

    @media (max-width: 640px) {
        .wish-card { grid-template-columns: 80px 1fr !important; grid-template-rows: auto auto; gap: 16px !important; }
        .wish-card .wish-actions { grid-column: 1 / -1; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #f5f5f5; padding-top: 16px; margin-top: 8px; }
    }
</style>

<script>
function removeWishItem(pid, btn) {
    const formData = new FormData();
    formData.append('product_id', pid);
    fetch(window.SHOP_URL + 'ajax/toggle_wishlist.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success && data.action === 'removed') {
                const card = document.getElementById('wish-' + pid);
                if (card) {
                    card.style.transition = 'opacity .3s';
                    card.style.opacity = '0';
                    setTimeout(() => {
                        card.remove();
                        if (!document.querySelector('.wish-card')) window.location.reload();
                    }, 300);
                }
                if (window.showToast) window.showToast(data.message);
            } else if (data.login_required) {
                window.location.href = window.SHOP_URL + 'login.php';
            }
        })
        .catch(err => console.error('Wishlist sync failure'));
}
</script>

<?php include '../includes/footer.php'; ?>
