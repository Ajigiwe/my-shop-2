<?php
/**
 * Admin: Order Details & Management
 * - Admin view for detailed order management
 * - Update order status, generate invoices, view customer details
 * - Full order lifecycle management
 */

// Include database connection and admin guard
require_once '../includes/db.php';
require_once '../includes/email_config.php';
session_start();
require_once '../includes/admin_guard.php';

$order_id = (int)($_GET['id'] ?? 0);
if ($order_id <= 0) {
    header('Location: manage_orders.php');
    exit();
}

$page_title = 'Order Details #' . str_pad($order_id, 6, '0', STR_PAD_LEFT);
$errors = [];
$success = '';

// Valid order statuses
$valid_statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'update_status') {
            $new_status = sanitizeInput($_POST['status'] ?? '');

            if (!in_array($new_status, $valid_statuses)) {
                $errors[] = 'Invalid status selected';
            } else {
                // Get current order status before updating
                $current_order = $pdo->prepare('SELECT status FROM orders WHERE order_id = ?');
                $current_order->execute([$order_id]);
                $current_status = $current_order->fetch()['status'];

                // Update order status
                $stmt = $pdo->prepare('UPDATE orders SET status = ? WHERE order_id = ?');
                $stmt->execute([$new_status, $order_id]);
                $success = 'Order status updated successfully';

                // If status was updated to "delivered", send invoice email
                if ($current_status !== 'delivered' && $new_status === 'delivered') {
                    // Get order details for email
                    $stmt = $pdo->prepare('SELECT o.*, u.name, u.email FROM orders o JOIN users u ON u.user_id = o.user_id WHERE o.order_id = ?');
                    $stmt->execute([$order_id]);
                    $order_for_email = $stmt->fetch();

                    if ($order_for_email && !empty($order_for_email['email'])) {
                        $email_sent = sendInvoiceEmail(
                            $order_for_email['email'],
                            $order_for_email['name'],
                            $order_id,
                            $order_for_email
                        );

                        if ($email_sent) {
                            $success .= ' Invoice has been sent to the customer.';
                        } else {
                            $errors[] = 'Order status updated but failed to send invoice email. Please check email configuration.';
                        }
                    }
                }

                // Refresh order data
                $stmt = $pdo->prepare('SELECT o.*, u.name, u.email, u.phone FROM orders o JOIN users u ON u.user_id = o.user_id WHERE o.order_id = ?');
                $stmt->execute([$order_id]);
                $order = $stmt->fetch();
            }
        } elseif ($action === 'send_invoice') {
            $send_order_id = (int)($_POST['order_id'] ?? 0);

            if ($send_order_id === $order_id) {
                // Get order details for email
                $stmt = $pdo->prepare('SELECT o.*, u.name, u.email FROM orders o JOIN users u ON u.user_id = o.user_id WHERE o.order_id = ?');
                $stmt->execute([$order_id]);
                $order_for_email = $stmt->fetch();

                if ($order_for_email && !empty($order_for_email['email'])) {
                    $email_sent = sendInvoiceEmail(
                        $order_for_email['email'],
                        $order_for_email['name'],
                        $order_id,
                        $order_for_email
                    );

                    if ($email_sent) {
                        $success = 'Invoice has been sent to the customer.';
                    } else {
                        $errors[] = 'Failed to send invoice email. Please check email configuration.';
                    }
                } else {
                    $errors[] = 'Customer email not found.';
                }
            }
        }
    } catch (PDOException $e) {
        error_log('Order update error: ' . $e->getMessage());
        $errors[] = 'Database error occurred';
    }
}

// Fetch order details
try {
    // Order with customer info
    $stmt = $pdo->prepare('SELECT o.*, u.name AS customer_name, u.email AS customer_email, u.phone AS customer_phone
                          FROM orders o JOIN users u ON u.user_id = o.user_id WHERE o.order_id = ?');
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();

    if (!$order) {
        header('Location: manage_orders.php');
        exit();
    }

    // Order items with product details
    $stmt = $pdo->prepare('SELECT oi.*, p.name, p.image FROM order_items oi JOIN products p ON p.product_id = oi.product_id WHERE oi.order_id = ? ORDER BY oi.order_item_id');
    $stmt->execute([$order_id]);
    $order_items = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log('Error fetching order details: ' . $e->getMessage());
    $order = null;
    $order_items = [];
}
?>

<?php include '../includes/header.php'; ?>

<div class="container py-4">
    <?php if ($order): ?>
        <!-- Success/Error Messages -->
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php foreach ($errors as $error): ?>
                    <?php echo $error; ?><br>
                <?php endforeach; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-12">
                <!-- Order Header -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h4 class="mb-0">
                                    <i class="fas fa-receipt me-2"></i>
                                    Order #<?php echo str_pad($order_id, 6, '0', STR_PAD_LEFT); ?>
                                </h4>
                            </div>
                            <div class="col-md-6 text-md-end">
                                <div class="d-flex gap-2 justify-content-md-end flex-wrap">
                                    <a href="manage_orders.php" class="btn btn-outline-light btn-sm">
                                        <i class="fas fa-arrow-left me-1"></i>Back to Orders
                                    </a>
                                    <a href="invoice.php?order_id=<?php echo $order_id; ?>" class="btn btn-light btn-sm" target="_blank">
                                        <i class="fas fa-file-invoice me-1"></i>View Invoice
                                    </a>
                                    <button onclick="window.print()" class="btn btn-light btn-sm">
                                        <i class="fas fa-print me-1"></i>Print
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Customer:</strong></p>
                                <p class="text-muted">
                                    <a href="mailto:<?php echo htmlspecialchars($order['customer_email']); ?>">
                                        <?php echo htmlspecialchars($order['customer_name']); ?>
                                    </a>
                                    <?php if ($order['customer_phone']): ?>
                                        <br><small><?php echo htmlspecialchars($order['customer_phone']); ?></small>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div class="col-md-3">
                                <p class="mb-1"><strong>Order Date:</strong></p>
                                <p class="text-muted"><?php echo date('M j, Y g:i A', strtotime($order['order_date'])); ?></p>
                            </div>
                            <div class="col-md-3">
                                <p class="mb-1"><strong>Payment:</strong></p>
                                <p class="text-muted"><?php
                                    $payment_methods = [
                                        'cash_on_delivery' => 'Cash on Delivery',
                                        'paypal' => 'PayPal',
                                        'paystack' => 'Paystack'
                                    ];
                                    echo htmlspecialchars($payment_methods[$order['payment_method']] ?? $order['payment_method']);
                                ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Order Items -->
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Order Items</h5>
                                <span class="badge bg-primary fs-6"><?php echo count($order_items); ?> items</span>
                            </div>
                            <div class="card-body">
                                <?php if (empty($order_items)): ?>
                                    <p class="text-muted text-center py-4">No items found in this order.</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Product</th>
                                                    <th>Price</th>
                                                    <th>Quantity</th>
                                                    <th>Total</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($order_items as $item): ?>
                                                    <tr>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <img src="../assets/images/<?php echo htmlspecialchars($item['image'] ?? 'placeholder.jpg'); ?>"
                                                                     alt="<?php echo htmlspecialchars($item['name']); ?>"
                                                                     class="me-3 rounded" style="width: 50px; height: 50px; object-fit: cover;">
                                                                <div>
                                                                    <h6 class="mb-0"><?php echo htmlspecialchars($item['name']); ?></h6>
                                                                    <small class="text-muted">Item #<?php echo $item['order_item_id']; ?></small>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td><?php echo formatCurrency($item['price']); ?></td>
                                                        <td>
                                                            <span class="badge bg-light text-dark"><?php echo $item['quantity']; ?></span>
                                                        </td>
                                                        <td><strong><?php echo formatCurrency($item['price'] * $item['quantity']); ?></strong></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                            <tfoot class="table-light">
                                                <tr>
                                                    <th colspan="3" class="text-end">Order Total:</th>
                                                    <th><?php echo formatCurrency($order['total_amount']); ?></th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Order Management -->
                    <div class="col-lg-4">
                        <!-- Status Update -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Update Order Status</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="update_status">
                                    <div class="mb-3">
                                        <label for="status" class="form-label">Status</label>
                                        <select class="form-select" name="status" id="status" required>
                                            <option value="">Select Status</option>
                                            <?php foreach ($valid_statuses as $status): ?>
                                                <option value="<?php echo $status; ?>" <?php echo ($order['status'] === $status) ? 'selected' : ''; ?>>
                                                    <?php echo ucfirst($status); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-save me-2"></i>Update Status
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Customer Information -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Customer Information</h5>
                            </div>
                            <div class="card-body">
                                <p class="mb-2"><strong>Name:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
                                <p class="mb-2"><strong>Email:</strong>
                                    <a href="mailto:<?php echo htmlspecialchars($order['customer_email']); ?>">
                                        <?php echo htmlspecialchars($order['customer_email']); ?>
                                    </a>
                                </p>
                                <?php if ($order['customer_phone']): ?>
                                    <p class="mb-2"><strong>Phone:</strong>
                                        <a href="tel:<?php echo htmlspecialchars($order['customer_phone']); ?>">
                                            <?php echo htmlspecialchars($order['customer_phone']); ?>
                                        </a>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Addresses -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Shipping Address</h5>
                            </div>
                            <div class="card-body">
                                <address class="mb-0">
                                    <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?>
                                </address>
                            </div>
                        </div>

                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Billing Address</h5>
                            </div>
                            <div class="card-body">
                                <address class="mb-0">
                                    <?php echo nl2br(htmlspecialchars($order['billing_address'])); ?>
                                </address>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Quick Actions</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <a href="invoice.php?order_id=<?php echo $order_id; ?>" class="btn btn-outline-primary" target="_blank">
                                        <i class="fas fa-file-invoice me-2"></i>Generate Invoice
                                    </a>
                                    <button type="button" class="btn btn-outline-info" onclick="sendInvoiceEmail(<?php echo $order_id; ?>)">
                                        <i class="fas fa-envelope me-2"></i>Send Invoice to Customer
                                    </button>
                                    <a href="mailto:<?php echo htmlspecialchars($order['customer_email']); ?>?subject=Order%20Update%20-%20Order%20%23<?php echo $order_id; ?>"
                                       class="btn btn-outline-info">
                                        <i class="fas fa-envelope me-2"></i>Email Customer
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Status History (if you want to add this later) -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">Order Timeline</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php
                            $status_timeline = [
                                'pending' => 'Order Placed',
                                'processing' => 'Processing Started',
                                'shipped' => 'Order Shipped',
                                'delivered' => 'Order Delivered',
                                'cancelled' => 'Order Cancelled'
                            ];

                            $current_status = $order['status'];
                            $status_keys = array_keys($valid_statuses);
                            $current_index = array_search($current_status, $status_keys);
                            ?>

                            <?php foreach ($valid_statuses as $status): ?>
                                <div class="col-md-4 mb-3">
                                    <div class="status-badge text-center p-3 <?php echo ($status === $current_status) ? 'active' : ''; ?>">
                                        <i class="fas fa-<?php
                                            echo match($status) {
                                                'pending' => 'clock',
                                                'processing' => 'cog',
                                                'shipped' => 'truck',
                                                'delivered' => 'check-circle',
                                                'cancelled' => 'times-circle'
                                            };
                                        ?> fa-2x mb-2"></i>
                                        <h6><?php echo $status_timeline[$status]; ?></h6>
                                        <small class="text-muted">
                                            <?php echo ($status === $current_status) ? 'Current Status' : 'Not reached'; ?>
                                        </small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i>
            Order not found.
        </div>
        <div class="text-center mt-4">
            <a href="manage_orders.php" class="btn btn-primary">Back to Manage Orders</a>
        </div>
    <?php endif; ?>
</div>

<style>
.status-badge {
    border: 2px solid #e9ecef;
    border-radius: 10px;
    transition: all 0.3s ease;
}

.status-badge.active {
    border-color: var(--primary-color);
    background-color: rgba(99, 102, 241, 0.1);
}

address {
    font-style: normal;
    line-height: 1.6;
}

.table th {
    border-top: none;
    font-weight: 600;
    color: var(--gray-700);
}

.badge {
    font-size: 0.875rem;
}
</style>

<script>
function sendInvoiceEmail(orderId) {
    if (confirm('Are you sure you want to send the invoice email to the customer?')) {
        // Create a form to submit the request
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';

        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'send_invoice';

        const orderIdInput = document.createElement('input');
        orderIdInput.type = 'hidden';
        orderIdInput.name = 'order_id';
        orderIdInput.value = orderId;

        form.appendChild(actionInput);
        form.appendChild(orderIdInput);
        document.body.appendChild(form);

        form.submit();
    }
}
</script>



