<?php
/**
 * Storefront: Track Order (Avazonia style)
 * Public lookup by order reference + email/phone
 */
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$page_title = 'Track Order';
$errors = [];
$order = null;
$items = [];
$searched = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $searched = true;
    $order_number = sanitizeInput($_POST['order_number'] ?? '');
    $identity = sanitizeInput($_POST['identity'] ?? '');

    if (empty($order_number)) {
        $errors[] = 'Please enter your order number';
    }
    if (empty($identity)) {
        $errors[] = 'Please enter the email or phone used at checkout';
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_number = ? AND (email = ? OR phone = ?) LIMIT 1");
            $stmt->execute([$order_number, $identity, $identity]);
            $order = $stmt->fetch();

            if ($order) {
                $stmt = $pdo->prepare("
                    SELECT oi.*, p.name, p.image
                    FROM order_items oi
                    JOIN products p ON p.product_id = oi.product_id
                    WHERE oi.order_id = ?
                ");
                $stmt->execute([$order['order_id']]);
                $items = $stmt->fetchAll();
            } else {
                $errors[] = 'No order found. Check your order number and email/phone and try again.';
            }
        } catch(PDOException $e) {
            error_log("Track order error: " . $e->getMessage());
            $errors[] = 'An error occurred. Please try again.';
        }
    }
}

include 'includes/header.php';
?>

<style>
    .tracking-result-card {
        background: #fff;
        border: 1px solid #eee;
        border-radius: 16px;
        padding: 48px;
        text-align: left;
        box-shadow: 0 20px 60px rgba(0,0,0,0.06);
    }
    .tracking-header-info { display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap; margin-bottom: 48px; }
    .tracking-ref h4 { font-family: var(--f-mono); font-size: 10px; font-weight: 700; color: var(--mid-gray); text-transform: uppercase; letter-spacing: 0.1em; margin: 0 0 6px; }
    .tracking-ref h3 { font-family: var(--f-display); font-size: 24px; font-weight: 900; color: var(--ink); margin: 0; }
    .tracking-status-badge { display: inline-block; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; padding: 8px 16px; border-radius: 100px; }

    .tracking-timeline { display: flex; align-items: flex-start; justify-content: space-between; position: relative; padding: 8px 0 8px; margin-bottom: 48px; }
    .timeline-progress { position: absolute; top: 24px; left: 8%; right: 8%; height: 3px; background: var(--light-gray); z-index: 0; }
    .timeline-progress::after { content: ''; display: block; height: 100%; background: var(--red); width: 0%; transition: width 1s ease; }
    .tracking-step { display: flex; flex-direction: column; align-items: center; gap: 12px; position: relative; z-index: 1; width: 25%; }
    .step-dot { width: 48px; height: 48px; border-radius: 50%; background: #fff; border: 2px solid var(--light-gray); color: var(--light-gray); display: flex; align-items: center; justify-content: center; transition: 0.3s; }
    .step-dot svg { width: 20px; height: 20px; }
    .tracking-step span { font-family: var(--f-mono); font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; color: var(--mid-gray); }
    .tracking-step.active .step-dot { border-color: var(--red); background: var(--red); color: #fff; }
    .tracking-step.active span { color: var(--ink); }
    .tracking-step.current .step-dot { box-shadow: 0 0 0 6px rgba(229,0,26,0.12); }

    .tracking-details-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 32px; }
    .detail-label { font-family: var(--f-mono); font-size: 10px; font-weight: 700; color: var(--mid-gray); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 8px; }
    .detail-val { font-family: var(--f-display); font-weight: 800; font-size: 15px; color: var(--ink); }

    @media (max-width: 640px) {
        .tracking-result-card { padding: 28px 20px; }
        .tracking-details-grid { grid-template-columns: 1fr; gap: 20px; }
        .tracking-header-info { margin-bottom: 32px; }
    }
</style>

<section class="page-hero" style="background: var(--red); padding: 100px 0 60px; text-align: center; color: #fff;">
    <div class="container">
        <h1 style="font-family: var(--f-display); font-size: 56px; font-weight: 900; margin-bottom: 16px;">TRACK YOUR ORDER</h1>
        <p style="font-family: var(--f-mono); font-size: 13px; font-weight: 700; opacity: 0.8; letter-spacing: 0.1em; text-transform: uppercase;">Real-Time Status Updates</p>
    </div>
</section>

<section class="page-content" style="padding: 100px 0; background: #fff;">
    <div class="container" style="max-width: 800px; text-align: center;">

        <?php if (!$searched || !$order): ?>
            <div id="tracking-search-area">
                <h2 style="font-family: var(--f-display); font-size: 28px; font-weight: 800; margin-bottom: 16px; color: var(--ink);">Where is my package?</h2>
                <p style="color: var(--mid-gray); margin-bottom: 48px;">Enter your Order ID and the Email or Phone number used for the order.</p>

                <form method="POST" action="track-order.php" style="display: flex; flex-direction: column; gap: 16px; max-width: 500px; margin: 0 auto;">
                    <input type="text" name="order_number" value="<?php echo htmlspecialchars($_POST['order_number'] ?? ''); ?>" placeholder="ORDER ID (e.g. ORD-...)  *" required style="height: 64px; padding: 0 24px; border: 2px solid #EEE; border-radius: 12px; font-family: var(--f-display); font-size: 16px; font-weight: 700; outline: none; transition: 0.2s; box-sizing: border-box; width: 100%;">
                    <input type="text" name="identity" value="<?php echo htmlspecialchars($_POST['identity'] ?? ''); ?>" placeholder="EMAIL OR PHONE NUMBER" required style="height: 64px; padding: 0 24px; border: 2px solid #EEE; border-radius: 12px; font-family: var(--f-display); font-size: 16px; font-weight: 700; outline: none; transition: 0.2s; box-sizing: border-box; width: 100%;">
                    <button type="submit" style="height: 64px; background: var(--red); color: #fff; border: none; border-radius: 12px; font-family: var(--f-display); font-size: 15px; font-weight: 900; text-transform: uppercase; cursor: pointer; transition: transform 0.2s;">Track My Order</button>
                </form>

                <?php if (!empty($errors)): ?>
                    <div style="margin-top: 20px; color: var(--red); font-weight: 700;">
                        <?php foreach ($errors as $error): ?>
                            <div><?php echo htmlspecialchars($error); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <?php
            $current_status = strtolower($order['order_status']);
            $steps = ['pending', 'processing', 'confirmed', 'shipped', 'delivered'];
            $current_idx = array_search($current_status, $steps);
            if ($current_idx === false) $current_idx = 0;
            $terminal = in_array($current_status, ['cancelled', 'failed', 'refunded']);

            $statusPills = [
                'pending' => ['#FFF7E6', '#FA8C16'], 'processing' => ['#F9F0FF', '#722ED1'],
                'confirmed' => ['#E6F7FF', '#1890FF'], 'shipped' => ['#E6F7FF', '#1890FF'],
                'delivered' => ['#F6FFED', '#52C41A'], 'cancelled' => ['#FFF1F0', '#F5222D'],
                'failed' => ['#FFF1F0', '#F5222D'], 'refunded' => ['#FFF1F0', '#F5222D'],
            ];
            $pill = $statusPills[$current_status] ?? ['#F5F5F5', '#A1A1A1'];
            ?>
            <div class="tracking-result-card">
                <div class="tracking-header-info">
                    <div class="tracking-ref">
                        <h4>Order Reference</h4>
                        <h3>#<?php echo htmlspecialchars($order['order_number']); ?></h3>
                    </div>
                    <div class="tracking-status-badge" style="background: <?php echo $pill[0]; ?>; color: <?php echo $pill[1]; ?>;"><?php echo htmlspecialchars($order['order_status']); ?></div>
                </div>

                <?php if ($terminal): ?>
                    <div style="display: flex; flex-direction: column; align-items: center; gap: 8px; padding: 24px 0 40px; color: var(--mid-gray); font-size: 15px;">
                        <span style="font-size: 44px;">🚫</span>
                        This order was <strong style="color: var(--red); text-transform: uppercase;"><?php echo htmlspecialchars($current_status); ?></strong> on <?php echo date('F j, Y', strtotime($order['updated_at'])); ?>
                    </div>
                <?php else: ?>
                    <div class="tracking-timeline">
                        <div class="timeline-progress"><div style="height:100%; background: var(--red); width: <?php echo count($steps) > 1 ? (($current_idx / (count($steps) - 1)) * 100) : 0; ?>%; transition: width 1s ease;"></div></div>
                        <?php foreach ($steps as $idx => $step): ?>
                            <div class="tracking-step <?php echo ($idx <= $current_idx) ? 'active' : ''; ?> <?php echo ($idx === $current_idx) ? 'current' : ''; ?>">
                                <div class="step-dot">
                                    <?php if ($step === 'pending'): ?><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
                                    <?php elseif ($step === 'processing'): ?><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                                    <?php elseif ($step === 'confirmed'): ?><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"></polyline><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
                                    <?php elseif ($step === 'shipped'): ?><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
                                    <?php else: ?><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                                    <?php endif; ?>
                                </div>
                                <span><?php echo $step; ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="tracking-details-grid">
                    <div>
                        <div class="detail-label">Shipped To</div>
                        <div class="detail-val"><?php echo htmlspecialchars(trim(strtok($order['shipping_address'] ?? '', ',')) ?: '—'); ?></div>
                    </div>
                    <div>
                        <div class="detail-label">Order Date</div>
                        <div class="detail-val"><?php echo date('M j, Y', strtotime($order['order_date'])); ?></div>
                    </div>
                    <div style="grid-column: 1 / -1; border-top: 1px solid #EEE; padding-top: 24px; margin-top: 8px;">
                        <div class="detail-label">Items Summary</div>
                        <div class="detail-val" style="font-size: 14px; opacity: 0.8;">
                            <?php
                            $summaries = [];
                            foreach ($items as $it) { $summaries[] = (int)$it['quantity'] . 'x ' . htmlspecialchars($it['name']); }
                            echo $summaries ? implode(', ', $summaries) : '—';
                            ?>
                        </div>
                    </div>
                    <div style="grid-column: 1 / -1;">
                        <div class="detail-label">Total</div>
                        <div class="detail-val" style="font-size: 22px; color: var(--red);"><?php echo formatCurrency($order['total_amount']); ?></div>
                    </div>
                </div>

                <div style="margin-top: 40px; text-align: center;">
                    <a href="track-order.php" style="background: none; border: none; color: var(--red); font-weight: 800; cursor: pointer; text-decoration: underline;">Track another order</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
