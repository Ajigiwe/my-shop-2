<?php
/**
 * User: Invoice (Printable) — Avazonia
 * - User-accessible printable invoice view for their own orders
 * - Joins order with user and items to render a clean invoice layout
 * - Ready for printing; can be used as a base for PDF export libraries later
 */

// Include database connection
require_once '../includes/db.php';

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$order_id = (int)($_GET['order_id'] ?? 0);
if ($order_id <= 0) {
    http_response_code(400);
    echo 'Invalid order ID';
    exit();
}

// Fetch order and items - ensure user owns this order
try {
    // Order + customer info (ensure user owns this order)
    $stmt = $pdo->prepare('SELECT o.*, u.name AS customer_name, u.email AS customer_email, u.phone AS customer_phone
                           FROM orders o JOIN users u ON u.user_id = o.user_id
                           WHERE o.order_id = ? AND o.user_id = ?');
    $stmt->execute([$order_id, $_SESSION['user_id']]);
    $order = $stmt->fetch();

    if (!$order) {
        http_response_code(404);
        echo 'Order not found or you do not have permission to view this invoice.';
        exit;
    }

    // Order items + product names with prices
    $stmt = $pdo->prepare('SELECT oi.*, p.name, oi.price, (oi.price * oi.quantity) as total_price 
                          FROM order_items oi 
                          JOIN products p ON p.product_id = oi.product_id 
                          WHERE oi.order_id = ?');
    $stmt->execute([$order_id]);
    $items = $stmt->fetchAll();
} catch (PDOException $e) {
    http_response_code(500);
    echo 'Error: ' . htmlspecialchars($e->getMessage());
    exit;
}

// Basic store info (customize as needed)
$store = [
    'name' => 'ASO Online Market',
    'address' => "123 Shopping Street\nCity, State 12345",
    'email' => 'info@asomarket.com',
    'phone' => '+1 (555) 123-4567'
];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?php echo str_pad($order_id, 6, '0', STR_PAD_LEFT); ?> - <?php echo htmlspecialchars($store['name']); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica Neue', Arial, sans-serif; background: #F9F9F9; color: #1A1A1A; font-size: 13px; line-height: 1.6; }
        .invoice-box { max-width: 800px; margin: 40px auto; background: #fff; border: 1px solid #EEEEEE; }
        .invoice-inner { padding: 48px; }

        .brand-row { display: flex; justify-content: space-between; align-items: flex-start; gap: 24px; border-bottom: 3px solid #1A1A1A; padding-bottom: 24px; margin-bottom: 32px; }
        .eyebrow { font-family: 'Courier New', monospace; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: #E8002D; margin-bottom: 8px; }
        .brand-name { font-size: 24px; font-weight: 800; text-transform: uppercase; letter-spacing: -0.02em; }
        .brand-name span { color: #E8002D; }
        .meta { text-align: right; font-size: 12px; color: #666666; }
        .meta strong { color: #1A1A1A; font-size: 14px; }
        .meta div { margin-top: 2px; }

        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 32px; }
        .card { border: 1px solid #EEEEEE; padding: 20px; }
        .card h3 { font-family: 'Courier New', monospace; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #1A1A1A; margin-bottom: 12px; }
        .card div { margin-top: 2px; }
        .muted { color: #888888; }
        .mt-2 { margin-top: 10px !important; }
        .mb-2 { margin-bottom: 10px !important; }

        table.items { width: 100%; border-collapse: collapse; }
        table.items thead th { font-family: 'Courier New', monospace; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #888888; text-align: left; padding: 10px 12px; border-bottom: 2px solid #1A1A1A; }
        table.items tbody td { padding: 12px; border-bottom: 1px solid #F0F0F0; }
        table.items .num { text-align: right; }
        table.items tbody td.num { font-weight: 700; }

        .totals { margin-left: auto; width: 300px; margin-top: 24px; }
        .t-row { display: flex; justify-content: space-between; padding: 6px 12px; color: #666666; font-size: 13px; }
        .t-row span:last-child { color: #1A1A1A; font-weight: 700; }
        .t-row.total { font-weight: 800; font-size: 18px; color: #1A1A1A; border-top: 2px solid #1A1A1A; margin-top: 8px; padding-top: 12px; }
        .t-row.total span:last-child { color: #E8002D; }

        .footer { border-top: 1px solid #EEEEEE; margin-top: 40px; padding-top: 20px; display: flex; justify-content: space-between; gap: 24px; color: #888888; font-size: 11px; }
        .footer strong { color: #1A1A1A; }

        .no-print { display: flex; gap: 12px; justify-content: center; margin-top: 32px; }
        .btn-print { display: inline-flex; align-items: center; gap: 8px; background: #E8002D; color: #fff; border: none; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; padding: 12px 24px; cursor: pointer; font-size: 12px; text-decoration: none; }
        .btn-ghost { display: inline-flex; align-items: center; gap: 8px; border: 1px solid #DDDDDD; color: #1A1A1A; background: #fff; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; padding: 12px 24px; text-decoration: none; font-size: 12px; }
        .btn-ghost:hover { border-color: #E8002D; color: #E8002D; }

        @media print {
            body * { visibility: hidden; }
            .invoice-box, .invoice-box * { visibility: visible; }
            .invoice-box { position: absolute; left: 0; top: 0; width: 100%; margin: 0; border: none; box-shadow: none; background: #fff; }
            .invoice-inner { padding: 20px; }
            .no-print { display: none !important; }
            body { margin: 0; padding: 0; background: #fff; }
        }
    </style>
</head>
<body>
<div class="invoice-box">
    <div class="invoice-inner">
        <!-- Brand row -->
        <div class="brand-row">
            <div>
                <div class="eyebrow">Invoice</div>
                <div class="brand-name"><?php echo htmlspecialchars($store['name']); ?><span>.</span></div>
            </div>
            <div class="meta">
                <div><strong>Invoice #<?php echo str_pad($order_id, 6, '0', STR_PAD_LEFT); ?></strong></div>
                <div>Date: <?php echo date('F j, Y \a\t g:i A', strtotime($order['order_date'])); ?></div>
                <div style="white-space: pre-line;"><?php echo htmlspecialchars($store['address']); ?></div>
                <div><?php echo htmlspecialchars($store['email']); ?> · <?php echo htmlspecialchars($store['phone']); ?></div>
            </div>
        </div>

        <!-- Bill To / Ship To -->
        <div class="grid-2">
            <div class="card">
                <h3>Bill to</h3>
                <div><strong><?php echo htmlspecialchars($order['customer_name']); ?></strong></div>
                <div class="muted"><?php echo htmlspecialchars($order['customer_email']); ?></div>
                <?php if ($order['customer_phone']): ?>
                    <div class="muted"><?php echo htmlspecialchars($order['customer_phone']); ?></div>
                <?php endif; ?>
                <div class="mt-2 muted" style="white-space: pre-line;"><?php echo htmlspecialchars($order['billing_address']); ?></div>
            </div>
            <div class="card">
                <h3>Ship to</h3>
                <div style="white-space: pre-line;"><?php echo htmlspecialchars($order['shipping_address']); ?></div>
                <div class="mt-2">Payment: <?php echo ucfirst(str_replace('_', ' ', $order['payment_method'])); ?></div>
                <div>Status: <strong><?php echo ucfirst($order['order_status']); ?></strong></div>
            </div>
        </div>

        <!-- Order items -->
        <table class="items">
            <thead>
                <tr>
                    <th style="width:40px;">#</th>
                    <th>Item description</th>
                    <th class="num">Qty</th>
                    <th class="num">Unit price</th>
                    <th class="num">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; $subtotal = 0; ?>
                <?php foreach ($items as $item): ?>
                    <?php $line_total = $item['price'] * $item['quantity']; $subtotal += $line_total; ?>
                    <tr>
                        <td><?php echo $i++; ?></td>
                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                        <td class="num"><?php echo (int)$item['quantity']; ?></td>
                        <td class="num"><?php echo formatCurrency($item['price']); ?></td>
                        <td class="num"><?php echo formatCurrency($line_total); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Totals -->
        <?php $shipping_fee = max(0, (float)$order['total_amount'] - $subtotal); ?>
        <div class="totals">
            <div class="t-row"><span>Subtotal</span><span><?php echo formatCurrency($subtotal); ?></span></div>
            <div class="t-row"><span>Shipping</span><span><?php echo $shipping_fee > 0 ? formatCurrency($shipping_fee) : 'FREE'; ?></span></div>
            <div class="t-row total"><span>Grand total</span><span><?php echo formatCurrency($order['total_amount']); ?></span></div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div>
                <strong>Thank you for your business!</strong><br>
                For questions about this order, please contact us.
            </div>
            <div style="text-align:right;">
                Generated on <?php echo date('F j, Y'); ?><br>
                Order #<?php echo str_pad($order_id, 6, '0', STR_PAD_LEFT); ?>
            </div>
        </div>

        <!-- Print actions (hidden when printing) -->
        <div class="no-print">
            <a href="order_details.php?id=<?php echo $order_id; ?>" class="btn-ghost">
                <span>&#8592;</span> Back to order details
            </a>
            <a href="orders.php" class="btn-ghost">My orders</a>
            <button class="btn-print" onclick="window.print()">
                <span>&#128424;</span> Print invoice
            </button>
        </div>
    </div>
</div>

<!-- Auto-print script (optional) -->
<script>
if (window.location.search.includes('print=1')) {
    window.onload = function() {
        window.print();
    };
}
</script>
</body>
</html>
