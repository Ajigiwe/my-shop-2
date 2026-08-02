<?php
/**
 * User: Order Details (Avazonia standalone order page)
 */
require_once '../includes/db.php';
require_once '../includes/functions.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$order_id = (int)($_GET['id'] ?? 0);
if ($order_id <= 0) {
    header('Location: orders.php');
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    $stmt = $pdo->prepare("
        SELECT o.*, u.name, u.email, u.phone AS user_phone
        FROM orders o 
        JOIN users u ON u.user_id = o.user_id 
        WHERE o.order_id = ? AND o.user_id = ?
    ");
    $stmt->execute([$order_id, $user_id]);
    $order = $stmt->fetch();

    if (!$order) {
        header('Location: orders.php');
        exit();
    }

    $stmt = $pdo->prepare("
        SELECT oi.*, p.name, p.image 
        FROM order_items oi 
        JOIN products p ON p.product_id = oi.product_id 
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$order_id]);
    $items = $stmt->fetchAll();

} catch(PDOException $e) {
    error_log("Error: " . $e->getMessage());
    header('Location: orders.php');
    exit();
}

$statusColors = [
    'pending'    => ['#9ca3af', 'Pending'],
    'processing' => ['#f59e0b', 'Processing'],
    'confirmed'  => ['#0ea5e9', 'Confirmed'],
    'shipped'    => ['#8b5cf6', 'Shipped'],
    'delivered'  => ['#16a34a', 'Delivered'],
    'cancelled'  => ['#ef4444', 'Cancelled']
];

$currStatus = strtolower($order['order_status'] ?? 'pending');
$sColor = $statusColors[$currStatus][0] ?? '#111';
$sText  = $statusColors[$currStatus][1] ?? ucfirst($currStatus);

$subtotal = 0;
foreach ($items as $item) {
    $subtotal += (float)$item['price'] * (float)$item['quantity'];
}
$shipping = max(0, (float)$order['total_amount'] - $subtotal);
$payLabel = ($order['payment_method'] === 'paystack') ? 'Online / Card' : (($order['payment_method'] === 'pod') ? 'Pay on Delivery' : 'Bank Transfer');

$page_title = 'Order Details';
include '../includes/header.php';
?>

<style>
    .order-page { padding-top: 100px; padding-bottom: 80px; background: #fff; min-height: 80vh; }
    .order-header { border-bottom: 1px solid var(--light-gray); padding-bottom: 32px; margin-bottom: 40px; }
    .order-title { font-family: var(--f-display); font-size: clamp(28px, 6vw, 40px); font-weight: 900; letter-spacing: -.03em; margin-bottom: 16px; word-break: break-word; line-height: 1.1; }
    .order-meta { display: flex; flex-wrap: wrap; gap: 20px 40px; }
    .meta-item { display: flex; flex-direction: column; gap: 4px; min-width: 120px; }
    .meta-label { font-family: var(--f-mono); font-size: 10px; color: var(--mid-gray); text-transform: uppercase; letter-spacing: .1em; }
    .meta-val { font-family: var(--f-display); font-weight: 700; font-size: 14px; }

    .order-grid { display: grid; grid-template-columns: 1fr 2fr; gap: 40px; }
    .order-items { display: flex; flex-direction: column; gap: 24px; }
    .item-card { display: flex; gap: 16px; align-items: start; border-bottom: 1px solid #f0f0f0; padding-bottom: 24px; }
    .item-img { width: 80px; height: 80px; background: var(--off); border-radius: 8px; overflow: hidden; flex-shrink: 0; }
    .item-img img { width: 100%; height: 100%; object-fit: contain; padding: 8px; }
    .item-info { flex: 1; min-width: 0; }
    .item-name { font-family: var(--f-display); font-weight: 800; font-size: 15px; margin-bottom: 4px; line-height: 1.3; }
    .item-ref { font-family: var(--f-mono); font-size: 9px; color: var(--mid-gray); text-transform: uppercase; margin-bottom: 12px; }
    .item-price-row { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; }
    .item-qty { font-family: var(--f-body); font-size: 12px; color: var(--mid-gray); }
    .item-total { font-family: var(--f-display); font-weight: 900; font-size: 15px; }

    .order-summary-card { background: var(--off); padding: 32px; border-radius: 8px; position: sticky; top: 120px; }
    .sum-title { font-family: var(--f-display); font-weight: 900; font-size: 18px; text-transform: uppercase; margin-bottom: 20px; }
    .sum-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid rgba(0,0,0,0.05); }
    .sum-row.total { border-bottom: none; padding-top: 20px; margin-top: 12px; border-top: 2px solid var(--ink); }
    .sum-label { font-family: var(--f-body); font-size: 13px; color: var(--mid-gray); }
    .sum-val { font-family: var(--f-display); font-weight: 700; font-size: 14px; }
    .sum-total-val { font-family: var(--f-display); font-weight: 900; font-size: 24px; }

    .shipping-box { margin-top: 24px; padding: 24px; background: #fdfdfd; border-radius: 8px; border: 1px solid #f0f0f0; }
    .box-title { font-family: var(--f-mono); font-size: 10px; font-weight: 700; text-transform: uppercase; color: var(--ink); border-bottom: 1px solid var(--light-gray); padding-bottom: 8px; margin-bottom: 12px; }
    .box-content { font-family: var(--f-body); font-size: 14px; line-height: 1.6; color: var(--mid-gray); }

    @media (max-width: 900px) {
        .order-page { padding-top: 60px; padding-bottom: 60px; }
        .order-grid { grid-template-columns: 1fr; gap: 40px; }
        .order-summary-card { position: static; padding: 24px; }
        .order-header { margin-bottom: 40px; }
        .item-img { width: 64px; height: 64px; }
    }
</style>

<div class="order-page">
    <div class="container">
        <div class="order-header">
            <div style="margin-bottom: 20px;">
                <a href="orders.php" style="font-family:var(--f-mono); font-size:10px; color:var(--mid-gray); text-transform:uppercase; text-decoration:none;">← Back to Orders</a>
            </div>
            <h1 class="order-title">Order <span style="color:var(--red);">#<?php echo htmlspecialchars($order['order_number'] ?? str_pad($order_id, 6, '0', STR_PAD_LEFT)); ?></span></h1>

            <div class="order-meta">
                <div class="meta-item">
                    <span class="meta-label">Date Placed</span>
                    <span class="meta-val"><?php echo date('F j, Y', strtotime($order['order_date'])); ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Status</span>
                    <span class="meta-val" style="color:<?php echo $sColor; ?>;"><?php echo $sText; ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Customer</span>
                    <span class="meta-val"><?php echo htmlspecialchars($user['name'] ?? 'Customer'); ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Reference</span>
                    <span class="meta-val" style="font-family: var(--f-mono); font-size: 12px;"><?php echo htmlspecialchars($order['payment_reference'] ?? '—'); ?></span>
                </div>
            </div>
        </div>

        <div class="order-grid">
            <div>
                <div class="order-summary-card">
                    <h2 class="sum-title">Payment Summary</h2>
                    <div class="sum-row">
                        <span class="sum-label">Subtotal</span>
                        <span class="sum-val"><?php echo formatCurrency($subtotal); ?></span>
                    </div>
                    <div class="sum-row">
                        <span class="sum-label">Delivery</span>
                        <span class="sum-val"><?php echo $shipping > 0 ? formatCurrency($shipping) : 'FREE'; ?></span>
                    </div>
                    <div class="sum-row">
                        <span class="sum-label">Payment Method</span>
                        <span class="sum-val"><?php echo $payLabel; ?></span>
                    </div>
                    <div class="sum-row total">
                        <span class="sum-label" style="color:var(--ink); font-weight:900; font-size:14px;">Total</span>
                        <span class="sum-total-val"><?php echo formatCurrency($order['total_amount']); ?></span>
                    </div>

                    <?php if (strtolower($order['order_status']) === 'pending' && $order['payment_method'] !== 'bank_transfer'): ?>
                        <div style="margin-top:24px; padding:20px; background: #fff; border-radius:8px; text-align:center;">
                            <p style="font-size:12px; margin-bottom:12px; color:var(--mid-gray);">This order is awaiting payment.</p>
                            <a href="<?php echo $base; ?>checkout_paystack.php?order_id=<?php echo (int)$order['order_id']; ?>" style="display:inline-block; width:100%; padding:14px 20px; background: var(--red); color: #fff; text-align:center; border-radius: 8px; font-family: var(--f-display); font-weight: 800; font-size: 13px; text-transform: uppercase; letter-spacing: 0.05em; text-decoration: none; box-sizing: border-box;">Complete Payment</a>
                        </div>
                    <?php elseif (strtolower($order['order_status']) === 'pending' && $order['payment_method'] === 'bank_transfer'): ?>
                        <div style="margin-top:24px; padding:20px; background: #fff; border-radius:8px; text-align:center;">
                            <p style="font-size:12px; margin-bottom:12px; color:var(--mid-gray);">This order is awaiting bank transfer confirmation.</p>
                            <a href="invoice.php?order_id=<?php echo (int)$order['order_id']; ?>" target="_blank" style="display:inline-block; width:100%; padding:14px 20px; background: var(--ink); color: #fff; text-align:center; border-radius: 8px; font-family: var(--f-display); font-weight: 800; font-size: 13px; text-transform: uppercase; letter-spacing: 0.05em; text-decoration: none; box-sizing: border-box;">View Invoice</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="order-items">
                <h2 class="box-title">Order Items</h2>
                <?php foreach ($items as $item): ?>
                    <div class="item-card">
                        <div class="item-img">
                            <img src="<?php echo $base . getProductImage($item['image'] ?? 'placeholder.jpg'); ?>" onerror="this.src='<?php echo $base; ?>assets/images/placeholder.jpg'; this.onerror=null;" alt="<?php echo htmlspecialchars($item['name'] ?? 'Product'); ?>">
                        </div>
                        <div class="item-info">
                            <h3 class="item-name"><?php echo htmlspecialchars($item['name'] ?? 'Product'); ?></h3>
                            <div class="item-ref">QTY: <?php echo (int)$item['quantity']; ?></div>
                            <div class="item-price-row">
                                <span class="item-qty">Unit Price: <?php echo formatCurrency($item['price']); ?></span>
                                <span class="item-total"><?php echo formatCurrency((float)$item['price'] * (float)$item['quantity']); ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="shipping-box">
                    <h2 class="box-title">Shipping &amp; Delivery</h2>
                    <div class="box-content">
                        <strong><?php echo htmlspecialchars($user['name'] ?? ''); ?></strong><br>
                        <?php echo nl2br(htmlspecialchars($order['shipping_address'] ?? '')); ?><br>
                        <?php echo htmlspecialchars($order['phone'] ?? $order['user_phone'] ?? ''); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
