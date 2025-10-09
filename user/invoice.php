<?php
/**
 * User: Invoice (Printable)
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

            /* Clean table borders for printing */
            .table-bordered,
            .table-bordered th,
            .table-bordered td {
                border: 1px solid #000 !important;
            }
        }

        /* Screen styles (when not printing) */
        .invoice-box {
            max-width: 900px;
            margin: 20px auto;
            background: #fff;
            padding: 30px;
            border: 1px solid #eee;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
        }
        .table th, .table td { vertical-align: middle; }
        .header-section {
            border-bottom: 3px solid var(--primary-color);
            margin-bottom: 30px;
            padding-bottom: 20px;
        }
        .store-info {
            text-align: right;
            color: var(--gray-600);
        }
        .customer-info {
            background: var(--gray-50);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
<div class="invoice-box">
    <!-- Header -->
    <div class="header-section">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="mb-2"><?php echo htmlspecialchars($store['name']); ?></h1>
                <p class="mb-0 text-muted">Invoice #<?php echo str_pad($order_id, 6, '0', STR_PAD_LEFT); ?></p>
                <p class="mb-0 text-muted">Date: <?php echo date('F j, Y \a\t g:i A', strtotime($order['order_date'])); ?></p>
            </div>
            <div class="col-md-6 store-info">
                <div class="mb-2"><strong><?php echo htmlspecialchars($store['name']); ?></strong></div>
                <div style="white-space: pre-line; font-size: 0.9em;"><?php echo htmlspecialchars($store['address']); ?></div>
                <div>Email: <?php echo htmlspecialchars($store['email']); ?></div>
                <div>Phone: <?php echo htmlspecialchars($store['phone']); ?></div>
            </div>
        </div>
    </div>

    <!-- Customer Information -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="customer-info">
                <h6 class="mb-3">Bill To:</h6>
                <div><strong><?php echo htmlspecialchars($order['customer_name']); ?></strong></div>
                <div>Email: <?php echo htmlspecialchars($order['customer_email']); ?></div>
                <?php if ($order['customer_phone']): ?>
                    <div>Phone: <?php echo htmlspecialchars($order['customer_phone']); ?></div>
                <?php endif; ?>
                <div class="mt-2" style="white-space: pre-line; font-size: 0.9em;">
                    <strong>Billing Address:</strong><br>
                    <?php echo htmlspecialchars($order['billing_address']); ?>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="customer-info">
                <h6 class="mb-3">Ship To:</h6>
                <div style="white-space: pre-line; font-size: 0.9em;">
                    <strong>Shipping Address:</strong><br>
                    <?php echo htmlspecialchars($order['shipping_address']); ?>
                </div>
                <div class="mt-3">
                    <strong>Payment Method:</strong> <?php echo ucfirst(str_replace('_', ' ', $order['payment_method'])); ?><br>
                    <strong>Status:</strong> <?php echo ucfirst($order['status']); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Order Items Table -->
    <div class="table-responsive">
        <table class="table table-bordered">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Item Description</th>
                    <th class="text-center">Quantity</th>
                    <th class="text-end">Unit Price</th>
                    <th class="text-end">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; $subtotal = 0; ?>
                <?php foreach ($items as $item): ?>
                    <?php $line_total = $item['price'] * $item['quantity']; $subtotal += $line_total; ?>
                    <tr>
                        <td><?php echo $i++; ?></td>
                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                        <td class="text-center"><?php echo (int)$item['quantity']; ?></td>
                        <td class="text-end"><?php echo formatCurrency($item['price']); ?></td>
                        <td class="text-end"><strong><?php echo formatCurrency($line_total); ?></strong></td>
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
                <tr class="table-primary">
                    <th colspan="4" class="text-end">Grand Total</th>
                    <th class="text-end fs-5"><?php echo formatCurrency($order['total_amount']); ?></th>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Footer -->
    <div class="mt-4 p-3 bg-light rounded">
        <div class="row">
            <div class="col-md-8">
                <p class="mb-1"><strong>Thank you for your business!</strong></p>
                <p class="mb-0 text-muted">For questions about this order, please contact us.</p>
            </div>
            <div class="col-md-4 text-md-end">
                <small class="text-muted">
                    Generated on <?php echo date('F j, Y'); ?><br>
                    Order #<?php echo str_pad($order_id, 6, '0', STR_PAD_LEFT); ?>
                </small>
            </div>
        </div>
    </div>

    <!-- Print Actions (hidden when printing) -->
    <div class="d-flex justify-content-between align-items-center mt-4 no-print">
        <a href="order_details.php?id=<?php echo $order_id; ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Order Details
        </a>
        <div>
            <a href="orders.php" class="btn btn-outline-primary me-2">
                <i class="fas fa-list me-2"></i>My Orders
            </a>
            <button class="btn btn-primary" onclick="window.print()">
                <i class="fas fa-print me-2"></i>Print Invoice
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
