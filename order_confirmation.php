<?php
/**
 * Order Confirmation Page (Avazonia success layout)
 */
require_once 'includes/db.php';
require_once 'includes/email_config.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if (isset($_GET['order_id'])) {
    $order_id = (int)$_GET['order_id'];
    $_SESSION['last_order_id'] = $order_id;
} elseif (isset($_SESSION['last_order_id'])) {
    $order_id = $_SESSION['last_order_id'];
} else {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("
        SELECT o.*, u.name as customer_name, COALESCE(o.email, u.email) as customer_email
        FROM orders o
        JOIN users u ON o.user_id = u.user_id
        WHERE o.order_id = ? AND o.user_id = ?
    ");
    $stmt->execute([$order_id, $user_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (empty($order)) {
        header('Location: index.php');
        exit();
    }

    $stmt = $pdo->prepare("
        SELECT oi.*, p.name as product_name, oi.price as product_price, p.image
        FROM order_items oi
        LEFT JOIN products p ON oi.product_id = p.product_id
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$order_id]);
    $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Send confirmation email if not already sent for this session's order
    if (!isset($_SESSION['email_sent_for_order']) || $_SESSION['email_sent_for_order'] != $order_id) {
        $email_details = $order;
        $email_details['items'] = [];
        foreach ($order_items as $item) {
            $email_details['items'][] = [
                'name' => $item['product_name'],
                'price' => $item['product_price'],
                'quantity' => $item['quantity']
            ];
        }

        if (sendOrderConfirmationEmail($order['customer_email'], $order['customer_name'], $order_id, $email_details)) {
            $_SESSION['email_sent_for_order'] = $order_id;
        }
    }

} catch(PDOException $e) {
    error_log("Error processing order confirmation: " . $e->getMessage());
    header('Location: index.php');
    exit();
}

unset($_SESSION['last_order_id']);

$is_bank_transfer = ($order['payment_method'] === 'bank_transfer' || $order['payment_method'] === 'in_person');

// Shipping is stored inside total_amount (subtotal + delivery fee)
$subtotal = 0;
foreach ($order_items as $item) {
    $subtotal += (float)$item['product_price'] * (int)$item['quantity'];
}
$shipping = max(0, (float)$order['total_amount'] - $subtotal);

$page_title = 'Order Confirmation';
include 'includes/header.php';
?>

<div class="success-page" style="padding: 120px 24px; text-align: center; background: #fff; min-height: 80vh;">
    <div class="container" style="max-width: 720px;">
        <div style="width: 80px; height: 80px; background: rgba(22,163,74,.1); border: 2px solid #16a34a; border-radius: 100px; display: flex; align-items: center; justify-content: center; margin: 0 auto 32px; font-size: 32px; color: #16a34a; animation: scaleIn 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);">
            ✓
        </div>

        <h1 style="font-family: var(--f-display); font-size: clamp(40px, 8vw, 64px); font-weight: 700; letter-spacing: -0.04em; line-height: 0.9; margin-bottom: 12px;">
            Order<br><span style="color: #16a34a;">Confirmed!</span>
        </h1>

        <p style="font-family: var(--f-mono); font-size: 10px; color: #aaa; letter-spacing: 0.1em; text-transform: uppercase; margin-bottom: 48px;">
            Thanks for shopping with us — your order has been placed successfully
        </p>

        <div style="background: #f9f9f9; border: 1px solid #eee; padding: 32px; border-radius: 12px; margin-bottom: 48px; position: relative; overflow: hidden; text-align: left;">
            <div style="font-family: var(--f-mono); font-size: 9px; color: #999; margin-bottom: 8px; text-transform: uppercase;">Order Reference</div>
            <div style="font-family: var(--f-display); font-size: 32px; font-weight: 800; color: var(--ink);">#<?php echo htmlspecialchars($order['order_number']); ?></div>
            <div style="position: absolute; top: -10px; right: -10px; font-size: 80px; opacity: 0.03; font-weight: 900; pointer-events: none;">SUCCESS</div>
        </div>

        <?php if ($is_bank_transfer):
    $is_in_person = ($order['payment_method'] === 'in_person');
?>
        <div style="background: #fff8e1; border: 1px solid #ffe082; padding: 32px; border-radius: 12px; margin-bottom: 32px; text-align: left;">
            <div style="font-family: var(--f-mono); font-size: 9px; color: #b7791f; text-transform: uppercase; margin-bottom: 8px; font-weight: 700;">🏦 <?php echo $is_in_person ? 'Pay In Person' : 'Pay by Bank Transfer'; ?></div>
            <p style="font-size: 14px; line-height: 1.6; color: #856404; margin-bottom: 20px;">
                <?php echo $is_in_person ? 'Your order is reserved for 24 hours. Please transfer the total below to the account and reply to your confirmation email with the payment receipt.' : 'Your order is reserved for 24 hours. Please transfer the total below to the account and reply to your confirmation email with the payment receipt.'; ?>
            </p>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; font-size: 13px;">
                <div style="background: #fff; border: 1px solid #eee; border-radius: 8px; padding: 14px 16px;">
                    <div style="font-family: var(--f-mono); font-size: 8px; color: #999; text-transform: uppercase; margin-bottom: 4px;">Account name</div>
                    <div style="font-weight: 700; color: #111;"><?php echo htmlspecialchars($settings['bank_account_name'] ?? 'ASO Online Market'); ?></div>
                </div>
                <div style="background: #fff; border: 1px solid #eee; border-radius: 8px; padding: 14px 16px;">
                    <div style="font-family: var(--f-mono); font-size: 8px; color: #999; text-transform: uppercase; margin-bottom: 4px;">Account number</div>
                    <div style="font-weight: 700; color: #111;"><?php echo htmlspecialchars($settings['bank_account_number'] ?? '0000000000'); ?></div>
                </div>
                <div style="background: #fff; border: 1px solid #eee; border-radius: 8px; padding: 14px 16px;">
                    <div style="font-family: var(--f-mono); font-size: 8px; color: #999; text-transform: uppercase; margin-bottom: 4px;">Bank</div>
                    <div style="font-weight: 700; color: #111;"><?php echo htmlspecialchars($settings['bank_name'] ?? '—'); ?></div>
                </div>
                <div style="background: #fff; border: 1px solid #eee; border-radius: 8px; padding: 14px 16px;">
                    <div style="font-family: var(--f-mono); font-size: 8px; color: #999; text-transform: uppercase; margin-bottom: 4px;">Amount</div>
                    <div style="font-weight: 700; color: var(--red);"><?php echo formatCurrency($order['total_amount']); ?></div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div style="background: #fff; border: 1px solid #eee; border-radius: 12px; padding: 32px; margin-bottom: 48px; text-align: left;">
            <div style="font-family: var(--f-display); font-weight: 700; font-size: 20px; color: #111; margin-bottom: 24px;">Order Summary</div>

            <div style="border-bottom: 1px solid #f0f0f0; padding-bottom: 24px; margin-bottom: 24px;">
                <?php foreach ($order_items as $item): ?>
                    <div style="display: flex; gap: 16px; align-items: flex-start; margin-bottom: 16px;">
                        <div style="width: 44px; height: 44px; border: 1px solid #eee; border-radius: 4px; overflow: hidden; flex-shrink: 0; background: #f9f9f9;">
                            <img src="<?php echo htmlspecialchars(getProductImage($item['image'] ?? 'placeholder.jpg')); ?>" style="width: 100%; height: 100%; object-fit: contain; padding: 4px;" alt="">
                        </div>
                        <div style="flex: 1; min-width: 0;">
                            <div style="font-family: var(--f-display); font-weight: 700; font-size: 14px; color: #111;"><?php echo htmlspecialchars($item['product_name']); ?></div>
                            <div style="font-family: var(--f-mono); font-size: 9px; color: #aaa; margin-top: 4px; text-transform: uppercase;">QTY: <?php echo (int)$item['quantity']; ?> × <?php echo formatCurrency($item['product_price']); ?></div>
                        </div>
                        <div style="font-family: var(--f-display); font-weight: 700; font-size: 15px;"><?php echo formatCurrency((float)$item['product_price'] * (int)$item['quantity']); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div style="display: flex; justify-content: space-between; padding: 6px 0; font-size: 12px;">
                <span style="color: #999;">Subtotal</span><span style="font-weight: 700; color: #111;"><?php echo formatCurrency($subtotal); ?></span>
            </div>
            <div style="display: flex; justify-content: space-between; padding: 6px 0; font-size: 12px;">
                <span style="color: #999;">Delivery</span><span style="font-weight: 700; color: #111;"><?php echo $shipping > 0 ? formatCurrency($shipping) : 'FREE'; ?></span>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center; border-top: 2px solid #111; margin-top: 12px; padding-top: 20px;">
                <span style="font-family: var(--f-display); font-weight: 800; font-size: 16px; text-transform: uppercase;">Order Total</span>
                <span style="font-family: var(--f-display); font-weight: 900; font-size: 30px; letter-spacing: -.03em; color: var(--red);"><?php echo formatCurrency($order['total_amount']); ?></span>
            </div>
        </div>

        <div style="display: flex; flex-direction: column; gap: 16px; align-items: center;">
            <a href="shop.php" class="btn-ink" style="width: 100%; max-width: 300px; height: 56px; display: flex; align-items: center; justify-content: center; font-family: var(--f-display); font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; text-decoration: none;">
                Continue Shopping →
            </a>
            <div style="display: flex; gap: 24px; flex-wrap: wrap; justify-content: center;">
                <a href="user/invoice.php?order_id=<?php echo $order_id; ?>" target="_blank" style="font-family: var(--f-mono); font-size: 11px; color: var(--mid-gray); text-decoration: underline; text-underline-offset: 4px; text-transform: uppercase; letter-spacing: 0.05em;">View Invoice</a>
                <a href="user/orders.php" style="font-family: var(--f-mono); font-size: 11px; color: var(--mid-gray); text-decoration: underline; text-underline-offset: 4px; text-transform: uppercase; letter-spacing: 0.05em;">View Order Status in Account</a>
            </div>
        </div>
    </div>
</div>

<style>
@keyframes scaleIn {
    from { transform: scale(0); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}

.success-page .btn-ink {
    background: var(--ink);
    color: #fff;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.success-page .btn-ink:hover {
    background: var(--red);
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(229, 0, 26, 0.15);
}
</style>

<?php include 'includes/footer.php'; ?>
