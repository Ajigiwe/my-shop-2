<?php
/**
 * Admin: Order Details & Management — Avazonia style
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
                $current_order = $pdo->prepare('SELECT order_status FROM orders WHERE order_id = ?');
                $current_order->execute([$order_id]);
                $current_order_data = $current_order->fetch();
                $current_status = $current_order_data['order_status'] ?? 'pending';

                // Update order status using order_status column
                $stmt = $pdo->prepare('UPDATE orders SET order_status = ? WHERE order_id = ?');
                $stmt->execute([$new_status, $order_id]);
                $success = 'Order status updated successfully';

                // Fetch fresh order and customer info to send notifications
                $stmt = $pdo->prepare('SELECT o.*, u.name, u.email, o.order_status as current_status FROM orders o JOIN users u ON u.user_id = o.user_id WHERE o.order_id = ?');
                $stmt->execute([$order_id]);
                $order_for_email = $stmt->fetch();

                if ($order_for_email && !empty($order_for_email['email'])) {
                    $status_email_sent = sendStatusUpdateEmail($order_for_email['order_number'], $new_status, $order_for_email['email'], $order_for_email['name']);
                    if ($status_email_sent) {
                        $success .= ' Customer notified by email about status change.';
                    } else {
                        $errors[] = 'Order status updated but failed to send status notification email. Please check email configuration.';
                    }
                } else {
                    $errors[] = 'Could not find customer email for this order. Status notification not sent.';
                }

                // If status was updated to "delivered", also send invoice email
                if ($current_status !== 'delivered' && $new_status === 'delivered') {
                    $stmt = $pdo->prepare('SELECT o.*, u.name, u.email, o.order_status as current_status FROM orders o JOIN users u ON u.user_id = o.user_id WHERE o.order_id = ?');
                    $stmt->execute([$order_id]);
                    $order_for_email = $stmt->fetch();

                    if ($order_for_email && !empty($order_for_email['email'])) {
                        $stmt = $pdo->prepare('SELECT oi.*, p.name as product_name, oi.price as product_price 
                                            FROM order_items oi 
                                            JOIN products p ON oi.product_id = p.product_id 
                                            WHERE oi.order_id = ?');
                        $stmt->execute([$order_id]);
                        $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        $status_to_use = $order_for_email['current_status'] ?? $new_status;
                        
                        $order_details = [
                            'order_id' => $order_id,
                            'items' => $order_items,
                            'subtotal' => $order_for_email['total_amount'],
                            'shipping' => 0,
                            'total' => $order_for_email['total_amount'],
                            'order_date' => $order_for_email['order_date'],
                            'status' => $status_to_use,
                            'payment_method' => $order_for_email['payment_method'] ?? 'not_specified'
                        ];
                        
                        $email_sent = sendInvoiceEmail(
                            $order_for_email['email'],
                            $order_for_email['name'],
                            $order_id,
                            $order_details
                        );
                        
                        if ($email_sent) {
                            $success .= ' Invoice has been sent to the customer.';
                        } else {
                            $errors[] = 'Status updated but failed to send invoice email. Please check email configuration.';
                        }
                    } else {
                        $errors[] = 'Could not find customer email for this order. Invoice not sent.';
                    }
                }
            }
        } elseif ($action === 'send_invoice') {
            $send_order_id = (int)($_POST['order_id'] ?? 0);

            if ($send_order_id > 0) {
                $stmt = $pdo->prepare('SELECT o.*, u.name, u.email FROM orders o JOIN users u ON u.user_id = o.user_id WHERE o.order_id = ?');
                $stmt->execute([$send_order_id]);
                $order_for_email = $stmt->fetch();

                if ($order_for_email) {
                    if (empty($order_for_email['email'])) {
                        $errors[] = 'Customer email not found for this order.';
                    } else {
                        $stmt = $pdo->prepare('SELECT oi.*, p.name as product_name, oi.price as product_price 
                                             FROM order_items oi 
                                             JOIN products p ON oi.product_id = p.product_id 
                                             WHERE oi.order_id = ?');
                        $stmt->execute([$send_order_id]);
                        $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        $order_details = [
                            'order_id' => $send_order_id,
                            'items' => $order_items,
                            'subtotal' => $order_for_email['total_amount'],
                            'shipping' => 0,
                            'total' => $order_for_email['total_amount'],
                            'order_date' => $order_for_email['order_date'],
                            'status' => $order_for_email['order_status'] ?? 'processing',
                            'payment_method' => $order_for_email['payment_method'] ?? 'not_specified'
                        ];
                        
                        $email_sent = sendInvoiceEmail(
                            $order_for_email['email'],
                            $order_for_email['name'],
                            $send_order_id,
                            $order_details
                        );

                        if ($email_sent) {
                            $success = 'Invoice has been sent to ' . htmlspecialchars($order_for_email['email']);
                        } else {
                            $errors[] = 'Failed to send invoice email. Please check the email configuration.';
                        }
                    }
                } else {
                    $errors[] = 'Could not find customer email address for this order.';
                }
            } else {
                $errors[] = 'Invalid order ID for invoice sending.';
            }
        }
    } catch (PDOException $e) {
        $errors[] = 'Database error occurred';
    }
}

// Fetch order details
try {
    $stmt = $pdo->prepare('SELECT o.*, u.name AS customer_name, u.email AS customer_email, u.phone AS customer_phone
                          FROM orders o JOIN users u ON u.user_id = o.user_id WHERE o.order_id = ?');
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();

    if (!$order) {
        header('Location: manage_orders.php');
        exit();
    }

    $stmt = $pdo->prepare('SELECT oi.*, p.name, p.image, oi.price, (oi.price * oi.quantity) as total_price 
                          FROM order_items oi 
                          JOIN products p ON p.product_id = oi.product_id 
                          WHERE oi.order_id = ? 
                          ORDER BY oi.order_item_id');
    $stmt->execute([$order_id]);
    $order_items = $stmt->fetchAll();

} catch (PDOException $e) {
    $order = null;
    $order_items = [];
}

$page_title = 'Order #' . str_pad($order_id, 6, '0', STR_PAD_LEFT);
include 'includes/avazonia_header.php';
?>

<div class="admin-header">
    <h1>Order <?php echo htmlspecialchars($order['order_number'] ?? str_pad($order_id, 6, '0', STR_PAD_LEFT)); ?></h1>
    <div class="header-actions">
        <span class="status-badge status-<?php echo htmlspecialchars($order['order_status'] ?? 'pending'); ?>"><?php echo htmlspecialchars($order['order_status'] ?? 'pending'); ?></span>
    </div>
</div>

<?php if ($success): ?>
    <div class="alert-box alert-success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>
<?php foreach ($errors as $err): ?>
    <div class="alert-box alert-error"><?php echo htmlspecialchars($err); ?></div>
<?php endforeach; ?>

<div style="display: grid; grid-template-columns: minmax(0, 1fr) 340px; gap: 40px; align-items: start;">
    <div style="display: flex; flex-direction: column; gap: 40px; min-width: 0;">
        <!-- Order Items -->
        <div class="panel">
            <div class="panel-header">
                <div class="panel-title">Order Items <span style="opacity: 0.4;">(<?php echo count($order_items); ?>)</span></div>
            </div>
            <div class="table-container" style="border: none; margin-bottom: 0; border-radius: 0;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th style="text-align: center;">Qty</th>
                            <th style="text-align: right;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($order_items as $item): ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 16px;">
                                    <img src="../assets/images/<?php echo htmlspecialchars($item['image'] ?? 'placeholder.jpg'); ?>" style="width: 48px; height: 48px; object-fit: cover; border: 1px solid var(--light-gray);">
                                    <div>
                                        <div style="font-weight: 800;"><?php echo htmlspecialchars($item['name']); ?></div>
                                        <div style="font-size: 10px; opacity: 0.5; font-family: var(--f-mono);">ID: #<?php echo $item['product_id']; ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo formatCurrency($item['price']); ?></td>
                            <td style="text-align: center;">
                                <span class="status-badge" style="background: var(--off); color: var(--ink);"><?php echo $item['quantity']; ?></span>
                            </td>
                            <td style="text-align: right; font-weight: 800;"><?php echo formatCurrency($item['price'] * $item['quantity']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr style="background: var(--off);">
                            <td colspan="3" style="text-align: right; font-weight: 800;">Grand Total</td>
                            <td style="text-align: right; font-weight: 900; font-family: var(--f-display); font-size: 18px;"><?php echo formatCurrency($order['total_amount']); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Addresses -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px;">
            <div class="panel">
                <div class="panel-header"><div class="panel-title">Shipping Logistics</div></div>
                <div style="padding: 24px;">
                    <div class="field-label">Delivery Address</div>
                    <p style="font-size: 13px; color: var(--ink); margin: 0; line-height: 1.6;"><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
                </div>
            </div>
            <div class="panel">
                <div class="panel-header"><div class="panel-title">Billing Details</div></div>
                <div style="padding: 24px;">
                    <div class="field-label">Billing Address</div>
                    <p style="font-size: 13px; color: var(--ink); margin: 0; line-height: 1.6;"><?php echo nl2br(htmlspecialchars($order['billing_address'])); ?></p>
                </div>
            </div>
        </div>
    </div>

    <div style="display: flex; flex-direction: column; gap: 40px;">
        <!-- Status Update -->
        <div class="panel">
            <div class="panel-header"><div class="panel-title">Workflow Control</div></div>
            <div style="padding: 24px;">
                <form method="POST">
                    <input type="hidden" name="action" value="update_status">
                    <div class="field-group" style="margin-bottom: 16px;">
                        <label class="field-label">Current Status</label>
                        <select class="field-input" name="status">
                            <?php foreach ($valid_statuses as $status): ?>
                                <option value="<?php echo $status; ?>" <?php echo (($order['order_status'] ?? 'pending') === $status) ? 'selected' : ''; ?>>
                                    <?php echo ucfirst($status); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn-red" style="width: 100%; justify-content: center;">Update Progress</button>
                </form>
            </div>
        </div>

        <!-- Customer Summary -->
        <div class="panel">
            <div class="panel-header"><div class="panel-title">Customer Profile</div></div>
            <div style="padding: 24px;">
                <div class="field-group" style="margin-bottom: 16px;">
                    <div class="field-label">Full Name</div>
                    <div style="font-weight: 800;"><?php echo htmlspecialchars($order['customer_name']); ?></div>
                </div>
                <div class="field-group" style="margin-bottom: 16px;">
                    <div class="field-label">Email Address</div>
                    <div style="font-size: 13px;"><?php echo htmlspecialchars($order['customer_email']); ?></div>
                </div>
                <?php if ($order['customer_phone']): ?>
                <div>
                    <div class="field-label">Phone Number</div>
                    <div style="font-size: 13px;"><?php echo htmlspecialchars($order['customer_phone']); ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="panel">
            <div class="panel-header"><div class="panel-title">Quick Actions</div></div>
            <div style="padding: 24px; display: flex; flex-direction: column; gap: 12px;">
                <form method="post" style="display: flex; flex-direction: column; gap: 12px;">
                    <input type="hidden" name="action" value="send_invoice">
                    <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                    <button type="submit" class="btn-ink" style="width: 100%; justify-content: center;">Email Invoice</button>
                </form>
                <a href="invoice.php?order_id=<?php echo $order_id; ?>" target="_blank" class="btn-ink" style="width: 100%; justify-content: center; background: transparent; color: var(--ink); border: 2px solid var(--ink);">Print Invoice</a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/avazonia_footer.php'; ?>
