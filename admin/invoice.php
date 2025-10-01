<?php
/**
 * Admin: Invoice (Printable)
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

    // Order items + product names
    $stmt = $pdo->prepare('SELECT oi.*, p.name FROM order_items oi JOIN products p ON p.product_id = oi.product_id WHERE oi.order_id = ?');
    $stmt->execute([$order_id]);
    $items = $stmt->fetchAll();
} catch (PDOException $e) {
    http_response_code(500);
    echo 'Error: ' . htmlspecialchars($e->getMessage());
    exit;
}

// Basic store info (customize as needed)
$store = [
    'name' => 'E-Shop',
    'address' => "123 Shopping Street\nCity, State 12345",
    'email' => 'info@eshop.com',
    'phone' => '+1 (555) 123-4567'
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?php echo str_pad($order_id, 6, '0', STR_PAD_LEFT); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            /* Hide everything except the invoice */
            body * {
                visibility: hidden;
            }

            /* Show only the invoice box and its contents */
            .invoice-box,
            .invoice-box *,
            .invoice-box *::before,
            .invoice-box *::after {
                visibility: visible;
            }

            /* Position the invoice at the top of the page */
            .invoice-box {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                margin: 0;
                padding: 20px;
                box-shadow: none;
                border: none;
                background: white;
            }

            /* Reset body margins for clean printing */
            body {
                margin: 0;
                padding: 0;
            }

            /* Hide navigation buttons when printing */
            .no-print {
                display: none !important;
            }

            /* Ensure proper page breaks */
            .invoice-box {
                page-break-inside: avoid;
            }

            /* Optimize table for printing */
            .table {
                page-break-inside: auto;
            }

            .table tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
        }
        .invoice-box {
            max-width: 900px;
            margin: 20px auto;
            background: #fff;
            padding: 20px;
            border: 1px solid #eee;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
        }
        .table th, .table td { vertical-align: middle; }
    </style>
</head>
<body>
<div class="invoice-box">
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h2 class="mb-1">Invoice</h2>
            <div>Order #: <strong>#<?php echo str_pad($order_id, 6, '0', STR_PAD_LEFT); ?></strong></div>
            <div>Date: <?php echo date('M j, Y g:i A', strtotime($order['order_date'])); ?></div>
        </div>
        <div class="text-end">
            <h4 class="mb-1"><?php echo htmlspecialchars($store['name']); ?></h4>
            <div class="small text-muted" style="white-space: pre-line;"><?php echo htmlspecialchars($store['address']); ?></div>
            <div class="small">Email: <?php echo htmlspecialchars($store['email']); ?> | Phone: <?php echo htmlspecialchars($store['phone']); ?></div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-6">
            <h6>Bill To</h6>
            <div><strong><?php echo htmlspecialchars($order['customer_name']); ?></strong></div>
            <div class="small">Email: <?php echo htmlspecialchars($order['customer_email']); ?></div>
            <div class="small">Phone: <?php echo htmlspecialchars($order['customer_phone'] ?? ''); ?></div>
            <div class="small mt-2" style="white-space: pre-line;">Billing Address:<br><?php echo htmlspecialchars($order['billing_address']); ?></div>
        </div>
        <div class="col-md-6">
            <h6>Ship To</h6>
            <div class="small" style="white-space: pre-line;">Shipping Address:<br><?php echo htmlspecialchars($order['shipping_address']); ?></div>
            <div class="small mt-2">Payment Method: <?php echo htmlspecialchars(ucfirst(str_replace('_',' ', $order['payment_method']))); ?></div>
            <div class="small">Status: <?php echo htmlspecialchars(ucfirst($order['status'])); ?></div>
        </div>
    </div>
    <?php
// Prefer dedicated phone column; fallback to extract from notes
$phoneDisplay = $order['phone'] ?? '';
if (empty($phoneDisplay) && !empty($order['notes'])) {
    if (preg_match('/Phone:\s*(.+)/i', $order['notes'], $m)) {
        $phoneDisplay = trim($m[1]);
    }
}
?>
<?php if (!empty($phoneDisplay)): ?>
    <p><strong>Phone:</strong> <?php echo htmlspecialchars($phoneDisplay); ?></p>
<?php endif; ?>

    <div class="table-responsive">
        <table class="table table-bordered">
            <thead class="table-light">
            <tr>
                <th>#</th>
                <th>Item</th>
                <th class="text-end">Price</th>
                <th class="text-center">Qty</th>
                <th class="text-end">Total</th>
            </tr>
            </thead>
            <tbody>
            <?php $i=1; $subtotal=0; foreach ($items as $it): $line = $it['price'] * $it['quantity']; $subtotal += $line; ?>
                <tr>
                    <td><?php echo $i++; ?></td>
                    <td><?php echo htmlspecialchars($it['name']); ?></td>
                    <td class="text-end"><?php echo formatCurrency($it['price']); ?></td>
                    <td class="text-center"><?php echo (int)$it['quantity']; ?></td>
                    <td class="text-end"><strong><?php echo formatCurrency($line); ?></strong></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
            <tr>
                <th colspan="4" class="text-end">Subtotal</th>
                <th class="text-end"><?php echo formatCurrency($subtotal); ?></th>
            </tr>
            <tr>
                <th colspan="4" class="text-end">Shipping</th>
                <th class="text-end"><?php echo formatCurrency(0); ?></th>
            </tr>
            <tr>
                <th colspan="4" class="text-end">Grand Total</th>
                <th class="text-end"><?php echo formatCurrency($order['total_amount']); ?></th>
            </tr>
            </tfoot>
        </table>
    </div>

    <div class="d-flex justify-content-between align-items-center mt-3 no-print">
        <a href="order_details.php?id=<?php echo $order_id; ?>" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Back to Order</a>
        <button class="btn btn-dark" onclick="window.print()"><i class="fas fa-print me-2"></i>Print</button>
    </div>
</div>
</body>
</html>

