<?php
/**
 * User: My Orders (Avazonia account layout)
 */
require_once '../includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$page_title = 'My Orders';
$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
} catch(PDOException $e) {
    error_log("Error fetching user: " . $e->getMessage());
}

$orders = [];
try {
    $stmt = $pdo->prepare("
        SELECT o.*, 
               (SELECT COUNT(*) FROM order_items WHERE order_id = o.order_id) as item_count 
        FROM orders o 
        WHERE o.user_id = ? 
        ORDER BY o.order_date DESC
    ");
    $stmt->execute([$user_id]);
    $orders = $stmt->fetchAll();
} catch(PDOException $e) {
    error_log("Error: " . $e->getMessage());
    $orders = [];
}

include '../includes/header.php';
?>

<section class="account-page" style="padding: 100px 0 80px; background: #fafafa; min-height: 80vh;">
    <div class="container" style="max-width: 1100px;">

        <!-- Breadcrumb & Header -->
        <nav style="margin-bottom: 32px;">
            <div style="font-family: var(--f-mono); font-size: 10px; text-transform: uppercase; color: var(--mid-gray); letter-spacing: 0.1em; display: flex; align-items: center; gap: 8px;">
                <a href="<?php echo $base; ?>index.php" style="color: inherit; text-decoration: none;">ASO</a>
                <span>/</span>
                <a href="dashboard.php" style="color: inherit; text-decoration: none;">Account</a>
                <span>/</span>
                <span style="color: var(--ink);">My Orders</span>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-top: 16px; flex-wrap: wrap; gap: 12px;">
                <h1 style="font-family: var(--f-display); font-weight: 800; font-size: 32px; margin: 0; color: var(--ink); letter-spacing: -0.02em;">My Orders</h1>
                <div style="font-family: var(--f-mono); font-size: 11px; font-weight: 700; color: var(--mid-gray);"><?php echo count($orders); ?> Orders</div>
            </div>
        </nav>

        <div class="account-grid" style="display: grid; grid-template-columns: 240px 1fr; gap: 48px;">

            <!-- Sidebar -->
            <?php include '_sidebar.php'; ?>

            <!-- Main Content -->
            <div style="min-width: 0;">
                <?php if (empty($orders)): ?>
                    <div style="padding: 80px 40px; text-align: center; background: #fff; border: 1px solid #eee; border-radius: 12px;">
                        <span style="font-size: 40px; display: block; margin-bottom: 16px;">📦</span>
                        <p style="font-weight: 700; font-size: 16px; color: var(--ink); margin-bottom: 8px;">No orders yet</p>
                        <p style="font-size: 13px; color: var(--mid-gray); margin-bottom: 24px;">Your orders will appear here once you make your first purchase.</p>
                        <a href="<?php echo $base; ?>shop.php" style="display: inline-block; padding: 12px 32px; background: var(--ink); color: #fff; text-decoration: none; border-radius: 8px; font-weight: 700; font-size: 13px; text-transform: uppercase; letter-spacing: 0.05em;">Start shopping</a>
                    </div>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <?php foreach ($orders as $order):
                            $previews = [];
                            try {
                                $stmt = $pdo->prepare("SELECT p.image FROM order_items oi JOIN products p ON oi.product_id = p.product_id WHERE oi.order_id = ? LIMIT 3");
                                $stmt->execute([$order['order_id']]);
                                $previews = $stmt->fetchAll();
                            } catch(PDOException $e) {
                                error_log("Error: " . $e->getMessage());
                            }
                            $statusColors = [
                                'pending'    => ['bg' => '#fff7e6', 'text' => '#fa8c16'],
                                'processing' => ['bg' => '#f9f0ff', 'text' => '#722ed1'],
                                'confirmed'  => ['bg' => '#e6f7ff', 'text' => '#1890ff'],
                                'shipped'    => ['bg' => '#e6f7ff', 'text' => '#1890ff'],
                                'delivered'  => ['bg' => '#f6ffed', 'text' => '#52c41a'],
                                'cancelled'  => ['bg' => '#fff1f0', 'text' => '#f5222d']
                            ];
                            $s = $statusColors[strtolower($order['order_status'])] ?? ['bg' => '#f5f5f5', 'text' => '#a1a1a1'];
                        ?>
                        <div class="order-card" style="background: #fff; border: 1px solid #eee; border-radius: 12px; padding: 20px; display: grid; grid-template-columns: 1fr 1fr 1fr auto; align-items: center; gap: 24px; transition: 0.2s;">
                            <div>
                                <div style="font-family: var(--f-mono); font-weight: 700; font-size: 11px; color: var(--red);">#<?php echo htmlspecialchars($order['order_number'] ?? str_pad($order['order_id'], 6, '0', STR_PAD_LEFT)); ?></div>
                                <div style="font-size: 12px; color: var(--mid-gray); margin-top: 4px;"><?php echo date('d M Y', strtotime($order['order_date'])); ?> · <?php echo (int)$order['item_count']; ?> item<?php echo $order['item_count'] != 1 ? 's' : ''; ?> · <?php echo str_replace('_', ' ', $order['payment_method']); ?></div>
                                <div style="display: flex; gap: 6px; margin-top: 12px;">
                                    <?php foreach ($previews as $prev): ?>
                                        <img src="<?php echo $base . getProductImage($prev['image']); ?>" onerror="this.src='<?php echo $base; ?>assets/images/placeholder.jpg'; this.onerror=null;" style="width: 44px; height: 44px; border: 1px solid #eee; object-fit: contain; padding: 4px; background: #fff; border-radius: 6px;" alt="">
                                    <?php endforeach; ?>
                                    <?php if ($order['item_count'] > count($previews)): ?>
                                        <span style="width: 44px; height: 44px; border: 1px solid #eee; background: #f5f5f5; color: var(--mid-gray); font-size: 11px; font-weight: 800; display: flex; align-items: center; justify-content: center; border-radius: 6px;">+<?php echo $order['item_count'] - count($previews); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div>
                                <span style="display: inline-block; font-size: 9px; text-transform: uppercase; padding: 4px 10px; background: <?php echo $s['bg']; ?>; color: <?php echo $s['text']; ?>; border-radius: 4px; font-weight: 800; letter-spacing: 0.05em;">
                                    <?php echo htmlspecialchars($order['order_status']); ?>
                                </span>
                            </div>

                            <div>
                                <div style="font-size: 10px; font-family: var(--f-mono); font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: var(--mid-gray); margin-bottom: 4px;">Total</div>
                                <div style="font-weight: 800; font-size: 16px; color: var(--ink);"><?php echo formatCurrency($order['total_amount']); ?></div>
                            </div>

                            <div style="display: flex; align-items: center; gap: 8px;">
                                <a href="invoice.php?order_id=<?php echo (int)$order['order_id']; ?>" target="_blank" title="Invoice" style="display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; border: 1px solid #ddd; border-radius: 8px; color: var(--ink); text-decoration: none; font-size: 15px; transition: 0.2s;">🧾</a>
                                <a href="order_details.php?id=<?php echo (int)$order['order_id']; ?>" title="View order" style="display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; border: 1px solid #ddd; border-radius: 8px; color: var(--ink); text-decoration: none; font-size: 16px; transition: 0.2s;">→</a>
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
    .order-card:hover { border-color: var(--ink) !important; transform: translateY(-2px); box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
    .account-sidebar a:hover { background: var(--off); color: var(--ink) !important; opacity: 1 !important; }

    @media (max-width: 900px) {
        .account-page { padding: 60px 0 60px !important; }
        .account-grid { grid-template-columns: 1fr !important; gap: 32px !important; }
        .account-sidebar { position: static !important; }
        .order-card { grid-template-columns: 1fr 1fr !important; gap: 16px !important; }
        h1 { font-size: 24px !important; }
    }

    @media (max-width: 520px) {
        .order-card { grid-template-columns: 1fr !important; }
        .order-card > div:last-child { justify-content: flex-start !important; }
    }
</style>

<?php include '../includes/footer.php'; ?>
