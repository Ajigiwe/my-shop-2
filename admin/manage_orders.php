<?php
/**
 * Order Management — Avazonia style
 */
require_once '../includes/db.php';
session_start();
require_once '../includes/admin_guard.php';

$page_title = 'Manage Orders';
$errors = [];
$success = '';

$valid_status = ['pending', 'paid', 'processing', 'shipped', 'arrived', 'delivered', 'approved', 'paid-full', 'cancelled', 'refunded'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Handle Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update_status') {
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission. Please refresh and try again.';
    } else {
    $order_id = (int)($_POST['order_id'] ?? 0);
    $status = sanitizeInput($_POST['status'] ?? '');
    if ($order_id > 0 && in_array($status, $valid_status)) {
        try {
            $stmt = $pdo->prepare('UPDATE orders SET order_status = ? WHERE order_id = ?');
            $stmt->execute([$status, $order_id]);
            $success = 'Order #'.$order_id.' updated to '.ucfirst($status);
            
            // Send status update email
            $stmt = $pdo->prepare('SELECT o.order_number, COALESCE(o.email, u.email) as customer_email, u.name as customer_name FROM orders o JOIN users u ON o.user_id = u.user_id WHERE o.order_id = ?');
            $stmt->execute([$order_id]);
            $order = $stmt->fetch();
            
            if ($order && !empty($order['customer_email'])) {
                require_once '../includes/email_config.php';
                // File-based logging for status email attempt
                $log_file = dirname(__DIR__) . '/logs/email_' . date('Y-m-d') . '.log';
                $pre = "[" . date('Y-m-d H:i:s') . "] Admin triggered status email: order={$order['order_number']}, to={$order['customer_email']}, status={$status} - attempt\n";
                file_put_contents($log_file, $pre, FILE_APPEND);

                $email_sent = sendStatusUpdateEmail($order['order_number'], $status, $order['customer_email'], $order['customer_name']);

                $post = "[" . date('Y-m-d H:i:s') . "] Admin status email result: order={$order['order_number']}, to={$order['customer_email']}, status={$status}, result=" . ($email_sent ? 'sent' : 'failed') . "\n";
                file_put_contents($log_file, $post, FILE_APPEND);
                error_log("Status update email send result for order {$order['order_number']}: " . ($email_sent ? 'sent' : 'failed'));
                if (!$email_sent) {
                    $errors[] = 'Order status updated, but notification email could not be sent. Please check your email configuration.';
                }
            }
        } catch (PDOException $e) {
            $errors[] = 'Update failed: ' . $e->getMessage();
        }
    }
    } // end else (CSRF valid)
}

// Fetch Orders
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status_filter'] ?? '';

try {
    $query = "SELECT o.*, u.name as customer_name, (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.order_id) as item_count 
              FROM orders o JOIN users u ON u.user_id = o.user_id WHERE 1=1";
    $params = [];

    if ($search) {
        $query .= " AND (u.name LIKE ? OR u.email LIKE ? OR o.order_number LIKE ?)";
        $s = "%$search%";
        $params = array_merge($params, [$s, $s, $s]);
    }

    if ($status_filter) {
        $query .= " AND o.order_status = ?";
        $params[] = $status_filter;
    }

    $query .= " ORDER BY o.order_date DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $orders = $stmt->fetchAll();
} catch (PDOException $e) {
    $errors[] = 'Fetch failed: ' . $e->getMessage();
}

include 'includes/avazonia_header.php';
?>

<div class="admin-header">
    <h1><?php echo htmlspecialchars($page_title); ?></h1>
    <div class="header-actions">
        <a href="export_orders.php" class="btn-ink">Export CSV</a>
    </div>
</div>

<?php if ($success): ?>
    <div class="alert-box alert-success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>
<?php foreach ($errors as $err): ?>
    <div class="alert-box alert-error"><?php echo htmlspecialchars($err); ?></div>
<?php endforeach; ?>

<div class="panel">
    <div class="panel-header">
        <div class="panel-title">Order History <span style="opacity: 0.4;">(<?php echo count($orders); ?>)</span></div>
    </div>
    <div style="padding: 24px; border-bottom: 1px solid var(--light-gray);">
        <form method="GET" class="filter-bar" style="margin-bottom: 0;">
            <div class="filter-group">
                <span class="flabel">Search</span>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Order #, Customer..." style="width: 280px;">
            </div>
            <div class="filter-group">
                <span class="flabel">Status</span>
                <select name="status_filter">
                    <option value="">All Statuses</option>
                    <?php foreach($valid_status as $s): ?>
                        <option value="<?php echo $s; ?>" <?php echo $status_filter === $s ? 'selected' : ''; ?>><?php echo ucfirst($s); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display: flex; gap: 8px;">
                <button type="submit" class="btn-ink" style="height: 44px;">Apply</button>
                <?php if($search || $status_filter): ?>
                    <a href="manage_orders.php" class="btn-ink" style="height: 44px; background: transparent; color: var(--ink); border: 1px solid var(--ink);">Clear</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    <div class="table-container" style="border: none; margin-bottom: 0; border-radius: 0;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Order Details</th>
                    <th>Customer</th>
                    <th>Items</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($orders as $o): ?>
                <tr>
                    <td>
                        <div style="font-weight: 800;"><?php echo htmlspecialchars($o['order_number'] ?: str_pad($o['order_id'], 6, '0', STR_PAD_LEFT)); ?></div>
                        <div style="font-size: 11px; opacity: 0.5; font-family: var(--f-mono);"><?php echo date('M d, Y', strtotime($o['order_date'])); ?></div>
                    </td>
                    <td>
                        <div style="font-weight: 700;"><?php echo htmlspecialchars($o['customer_name']); ?></div>
                        <div style="font-size: 11px; opacity: 0.5;"><?php echo htmlspecialchars($o['email'] ?? ''); ?></div>
                    </td>
                    <td>
                        <span class="status-badge" style="background: var(--off); color: var(--ink);"><?php echo (int)$o['item_count']; ?> Items</span>
                    </td>
                    <td style="font-weight: 800;"><?php echo formatCurrency($o['total_amount']); ?></td>
                    <td>
                        <form method="POST" onchange="this.submit()">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="order_id" value="<?php echo $o['order_id']; ?>">
                            <select name="status" style="border: none; padding: 4px 10px; border-radius: 99px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; cursor: pointer; background: var(--off); color: var(--ink);">
                                <?php foreach($valid_status as $s): ?>
                                    <option value="<?php echo $s; ?>" <?php echo $o['order_status'] === $s ? 'selected' : ''; ?>><?php echo ucfirst($s); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </td>
                    <td style="text-align: right;">
                        <div style="display: flex; justify-content: flex-end; gap: 8px;">
                            <a href="order_details.php?id=<?php echo $o['order_id']; ?>" class="action-btn">View</a>
                            <a href="invoice.php?order_id=<?php echo $o['order_id']; ?>" target="_blank" class="action-btn">Invoice</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($orders)): ?>
                <tr>
                    <td colspan="6">
                        <div class="empty-state">
                            <div class="empty-icon">○</div>
                            <p>No orders found matching your criteria.</p>
                        </div>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/avazonia_footer.php'; ?>
