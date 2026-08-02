<?php
/**
 * User Dashboard (Avazonia account layout)
 */
require_once '../includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$page_title = 'Dashboard';

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    $stmt = $pdo->prepare("SELECT COUNT(*) as total, COALESCE(SUM(total_amount), 0) as spent FROM orders WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $stats = $stmt->fetch();

    $stmt = $pdo->prepare("
        SELECT o.*, 
               (SELECT COUNT(*) FROM order_items WHERE order_id = o.order_id) as item_count 
        FROM orders o 
        WHERE o.user_id = ? 
        ORDER BY o.order_date DESC 
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $recent_orders = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $wishlist_count = $stmt->fetchColumn();
} catch(PDOException $e) {
    error_log("Error: " . $e->getMessage());
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
                <span style="color: var(--ink);">Account</span>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-top: 16px; flex-wrap: wrap; gap: 8px;">
                <h1 style="font-family: var(--f-display); font-weight: 800; font-size: 32px; margin: 0; color: var(--ink); letter-spacing: -0.02em;">Welcome, <?php echo htmlspecialchars(explode(' ', $user['name'] ?? 'Member')[0]); ?></h1>
                <div style="font-family: var(--f-mono); font-size: 11px; font-weight: 700; color: var(--mid-gray);"><?php echo date('l, d M Y'); ?></div>
            </div>
        </nav>

        <div class="account-grid" style="display: grid; grid-template-columns: 240px 1fr; gap: 48px;">

            <!-- Sidebar -->
            <?php include '_sidebar.php'; ?>

            <!-- Main Content -->
            <div style="min-width: 0;">
                <!-- Stats -->
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 32px;">
                    <div style="background: #fff; border: 1px solid #eee; border-radius: 12px; padding: 24px;">
                        <p style="font-family: var(--f-mono); font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: var(--mid-gray); margin: 0 0 8px;">Total spent</p>
                        <p style="font-family: var(--f-display); font-weight: 900; font-size: 26px; color: var(--ink); margin: 0;"><?php echo formatCurrency($stats['spent'] ?? 0); ?></p>
                    </div>
                    <div style="background: #fff; border: 1px solid #eee; border-radius: 12px; padding: 24px;">
                        <p style="font-family: var(--f-mono); font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: var(--mid-gray); margin: 0 0 8px;">Orders</p>
                        <p style="font-family: var(--f-display); font-weight: 900; font-size: 26px; color: var(--ink); margin: 0;"><?php echo (int)($stats['total'] ?? 0); ?></p>
                    </div>
                    <div style="background: #fff; border: 1px solid #eee; border-radius: 12px; padding: 24px;">
                        <p style="font-family: var(--f-mono); font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: var(--mid-gray); margin: 0 0 8px;">Wishlist</p>
                        <p style="font-family: var(--f-display); font-weight: 900; font-size: 26px; color: var(--ink); margin: 0;"><?php echo (int)$wishlist_count; ?></p>
                    </div>
                </div>

                <!-- Recent Orders -->
                <div class="order-history">
                    <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 20px;">
                        <h3 style="font-family: var(--f-display); font-weight: 900; font-size: 18px; text-transform: uppercase;">Recent Orders</h3>
                        <a href="orders.php" style="font-family: var(--f-mono); font-size: 11px; color: var(--red); text-transform: uppercase; letter-spacing: 0.05em; text-decoration: none;">View all →</a>
                    </div>

                    <?php if (empty($recent_orders)): ?>
                        <div style="padding: 80px 40px; text-align: center; background: #fff; border: 1px solid #eee; border-radius: 12px;">
                            <span style="font-size: 40px; display: block; margin-bottom: 16px;">📦</span>
                            <p style="font-weight: 700; font-size: 16px; color: var(--ink); margin-bottom: 8px;">No orders found yet.</p>
                            <p style="font-size: 13px; color: var(--mid-gray); margin-bottom: 24px;">Your orders will appear here once you make your first purchase.</p>
                            <a href="<?php echo $base; ?>shop.php" style="display: inline-block; padding: 12px 32px; background: var(--ink); color: #fff; text-decoration: none; border-radius: 8px; font-weight: 700; font-size: 13px; text-transform: uppercase; letter-spacing: 0.05em;">Start shopping</a>
                        </div>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; gap: 12px;">
                            <?php foreach ($recent_orders as $o):
                                $statusColors = [
                                    'pending'    => ['bg' => '#fff7e6', 'text' => '#fa8c16'],
                                    'processing' => ['bg' => '#f9f0ff', 'text' => '#722ed1'],
                                    'confirmed'  => ['bg' => '#e6f7ff', 'text' => '#1890ff'],
                                    'shipped'    => ['bg' => '#e6f7ff', 'text' => '#1890ff'],
                                    'delivered'  => ['bg' => '#f6ffed', 'text' => '#52c41a'],
                                    'cancelled'  => ['bg' => '#fff1f0', 'text' => '#f5222d']
                                ];
                                $s = $statusColors[strtolower($o['order_status'])] ?? ['bg' => '#f5f5f5', 'text' => '#a1a1a1'];
                            ?>
                            <div class="order-card" style="background: #fff; border: 1px solid #eee; border-radius: 12px; padding: 20px; display: grid; grid-template-columns: 1fr 1fr 1fr auto; align-items: center; gap: 24px; transition: 0.2s;">
                                <div>
                                    <div style="font-family: var(--f-mono); font-weight: 700; font-size: 11px; color: var(--red);">#<?php echo htmlspecialchars($o['order_number'] ?? str_pad($o['order_id'], 6, '0', STR_PAD_LEFT)); ?></div>
                                    <div style="font-size: 12px; color: var(--mid-gray); margin-top: 4px;"><?php echo date('d M Y', strtotime($o['order_date'])); ?> · <?php echo (int)$o['item_count']; ?> item<?php echo $o['item_count'] != 1 ? 's' : ''; ?></div>
                                </div>

                                <div>
                                    <div style="font-weight: 800; font-size: 16px; color: var(--ink);"><?php echo formatCurrency($o['total_amount']); ?></div>
                                    <a href="invoice.php?order_id=<?php echo (int)$o['order_id']; ?>" target="_blank" style="font-family: var(--f-mono); font-size: 9px; color: var(--mid-gray); text-decoration: underline; opacity: 0.6; text-transform: uppercase;">Invoice</a>
                                </div>

                                <div>
                                    <span style="display: inline-block; font-size: 9px; text-transform: uppercase; padding: 4px 10px; background: <?php echo $s['bg']; ?>; color: <?php echo $s['text']; ?>; border-radius: 4px; font-weight: 800; letter-spacing: 0.05em;">
                                        <?php echo htmlspecialchars($o['order_status']); ?>
                                    </span>
                                </div>

                                <div>
                                    <a href="order_details.php?id=<?php echo (int)$o['order_id']; ?>" style="display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; border: 1px solid #ddd; border-radius: 8px; color: var(--ink); text-decoration: none; font-size: 16px; transition: 0.2s;">→</a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
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
    }
</style>

<?php include '../includes/footer.php'; ?>
