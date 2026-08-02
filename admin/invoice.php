<?php
/**
 * Admin: Invoice (Printable) — Avazonia design
 * - Admin-only printable invoice view for a single order
 * - Joins order with user and items to render a clean invoice layout
 * - Ready for printing; can be used as a base for PDF export libraries later
 */
require_once '../includes/db.php';
session_start();
require_once '../includes/admin_guard.php';

$order_id = (int)($_GET['order_id'] ?? 0);
if ($order_id <= 0) {
    http_response_code(400);
    echo 'Invalid order ID';
    exit;
}

$settings = loadSiteSettings($pdo);
$site_name = $settings['site_name'] ?? 'ASO Online Market';
$site_desc = $settings['site_description'] ?? '';

// Fetch order and items
try {
    // Order + customer info
    $stmt = $pdo->prepare('SELECT o.*, u.name AS customer_name, u.email AS customer_email, u.phone AS customer_phone
                           FROM orders o JOIN users u ON u.user_id = o.user_id WHERE o.order_id = ?');
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();
    if (!$order) {
        http_response_code(404);
        echo 'Order not found';
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

$order_number = $order['order_number'] ?? str_pad($order_id, 6, '0', STR_PAD_LEFT);
$order_date = $order['order_date'];
$payment_method = ucwords(str_replace('_', ' ', $order['payment_method'] ?? 'not specified'));
$order_status = ucfirst($order['order_status'] ?? 'pending');

// Prefer dedicated phone column; fallback to extract from order_notes
$phoneDisplay = $order['phone'] ?? '';
if (empty($phoneDisplay) && !empty($order['order_notes'])) {
    if (preg_match('/Phone:\s*(.+)/i', $order['order_notes'], $m)) {
        $phoneDisplay = trim($m[1]);
    }
}

$subtotal = 0;
foreach ($items as $it) {
    $subtotal += $it['price'] * $it['quantity'];
}
$shipping = max(0, (float)$order['total_amount'] - $subtotal);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?php echo htmlspecialchars($order_number); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Outfit:wght@600;700;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --ink: #0B3D2E;
            --red: #00A854;
            --off: #F4F1EC;
            --mid-gray: #6B6B6B;
            --light-gray: #E5E1DA;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: var(--off);
            font-family: 'Plus Jakarta Sans', -apple-system, sans-serif;
            color: var(--ink);
            padding: 40px 20px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .invoice-box {
            max-width: 900px;
            margin: 0 auto;
            background: #fff;
            border: 2px solid var(--ink);
            border-radius: 16px;
            overflow: hidden;
        }
        .invoice-header {
            background: var(--ink);
            color: #fff;
            padding: 28px 40px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 24px;
        }
        .brand {
            font-family: 'Outfit', sans-serif;
            font-weight: 900;
            font-size: 24px;
            letter-spacing: -0.04em;
            text-transform: uppercase;
        }
        .brand span { color: var(--red); }
        .brand-sub { font-size: 11px; opacity: 0.7; letter-spacing: 0.08em; text-transform: uppercase; margin-top: 4px; }
        .invoice-title { text-align: right; }
        .invoice-title h1 {
            font-family: 'Outfit', sans-serif;
            font-weight: 900;
            font-size: 32px;
            letter-spacing: -0.03em;
            text-transform: uppercase;
            line-height: 1;
        }
        .invoice-title div { font-size: 13px; opacity: 0.8; margin-top: 6px; font-family: monospace; }
        .invoice-body { padding: 40px; }
        .meta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 32px;
        }
        .meta-block h6 {
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--mid-gray);
            margin-bottom: 10px;
        }
        .meta-block p { font-size: 13px; line-height: 1.7; color: var(--ink); }
        .meta-block p strong { display: block; font-size: 15px; margin-bottom: 2px; }
        table.inv-table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        .inv-table th {
            background: var(--off);
            padding: 12px 14px;
            text-align: left;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--ink);
            border-bottom: 2px solid var(--ink);
        }
        .inv-table th.num, .inv-table td.num { text-align: right; }
        .inv-table th.center, .inv-table td.center { text-align: center; }
        .inv-table td { padding: 14px; border-bottom: 1px solid var(--light-gray); font-size: 14px; }
        .inv-table tbody tr:last-child td { border-bottom: 2px solid var(--ink); }
        .inv-table tfoot td {
            padding: 10px 14px;
            font-size: 13px;
            text-align: right;
        }
        .inv-table tfoot .grand-total td {
            font-size: 16px;
            font-weight: 800;
            color: var(--red);
            padding-top: 16px;
            border-top: 2px solid var(--ink);
            border-bottom: none;
        }
        .no-print {
            margin-top: 32px;
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 2px solid var(--ink);
            background: #fff;
            color: var(--ink);
            border-radius: 12px;
            padding: 12px 22px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 800;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            text-decoration: none;
            cursor: pointer;
            transition: background .2s, color .2s;
        }
        .btn-primary { background: var(--ink); color: #fff; }
        .btn-primary:hover { background: var(--red); border-color: var(--red); }
        .btn:hover { background: var(--ink); color: #fff; }
        .invoice-footer {
            padding: 20px 40px;
            border-top: 1px solid var(--light-gray);
            text-align: center;
            font-size: 11px;
            color: var(--mid-gray);
        }
        @media print {
            body { background: #fff; padding: 0; }
            .invoice-box { border: none; border-radius: 0; }
            .invoice-header { background: var(--ink) !important; }
            .no-print { display: none !important; }
            .invoice-box { page-break-inside: avoid; }
            .inv-table tr { page-break-inside: avoid; }
        }
        @media (max-width: 600px) {
            .invoice-header { flex-direction: column; padding: 24px; }
            .invoice-title { text-align: left; }
            .invoice-body { padding: 24px; }
            .meta-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="invoice-box">
    <div class="invoice-header">
        <div>
            <div class="brand"><?php echo htmlspecialchars($site_name); ?><span>.</span></div>
            <?php if ($site_desc): ?>
                <div class="brand-sub"><?php echo htmlspecialchars($site_desc); ?></div>
            <?php endif; ?>
        </div>
        <div class="invoice-title">
            <h1>Invoice</h1>
            <div>#<?php echo htmlspecialchars($order_number); ?></div>
        </div>
    </div>

    <div class="invoice-body">
        <div class="meta-grid">
            <div class="meta-block">
                <h6>Bill To</h6>
                <p>
                    <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong>
                    <?php echo htmlspecialchars($order['customer_email']); ?><br>
                    <?php echo htmlspecialchars($phoneDisplay ?: ($order['customer_phone'] ?? '')); ?><br>
                    <br>
                    <strong style="font-size:11px;color:var(--mid-gray);">Billing Address</strong><br>
                    <?php echo nl2br(htmlspecialchars($order['billing_address'])); ?>
                </p>
            </div>
            <div class="meta-block">
                <h6>Ship To</h6>
                <p>
                    <strong style="font-size:11px;color:var(--mid-gray);">Shipping Address</strong><br>
                    <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?>
                </p>
                <p style="margin-top:12px;">
                    Date: <?php echo date('M j, Y g:i A', strtotime($order_date)); ?><br>
                    Payment: <?php echo htmlspecialchars($payment_method); ?><br>
                    Status: <strong><?php echo htmlspecialchars($order_status); ?></strong>
                </p>
            </div>
        </div>

        <table class="inv-table">
            <thead>
            <tr>
                <th style="width:40px;">#</th>
                <th>Item</th>
                <th class="num">Price</th>
                <th class="center">Qty</th>
                <th class="num">Total</th>
            </tr>
            </thead>
            <tbody>
            <?php $i = 1; foreach ($items as $it): $line = $it['price'] * $it['quantity']; ?>
                <tr>
                    <td><?php echo $i++; ?></td>
                    <td><strong><?php echo htmlspecialchars($it['name']); ?></strong></td>
                    <td class="num"><?php echo formatCurrency($it['price']); ?></td>
                    <td class="center"><?php echo (int)$it['quantity']; ?></td>
                    <td class="num"><strong><?php echo formatCurrency($line); ?></strong></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
            <tr>
                <td colspan="4" style="color:var(--mid-gray);">Subtotal</td>
                <td class="num"><?php echo formatCurrency($subtotal); ?></td>
            </tr>
            <tr>
                <td colspan="4" style="color:var(--mid-gray);">Shipping</td>
                <td class="num"><?php echo formatCurrency($shipping); ?></td>
            </tr>
            <tr class="grand-total">
                <td colspan="4">Grand Total</td>
                <td class="num"><?php echo formatCurrency($order['total_amount']); ?></td>
            </tr>
            </tfoot>
        </table>

        <div class="no-print">
            <a href="order_details.php?id=<?php echo $order_id; ?>" class="btn">← Back to Order</a>
            <button class="btn btn-primary" onclick="window.print()">Print</button>
        </div>
    </div>

    <div class="invoice-footer">
        <?php echo htmlspecialchars($site_name); ?> — Generated <?php echo date('M j, Y g:i A'); ?>
    </div>
</div>
</body>
</html>
