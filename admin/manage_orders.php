<?php
/**
 * Modern Order Management
 */
require_once '../includes/db.php';
session_start();
require_once '../includes/admin_guard.php';

$page_title = 'Manage Orders';
$errors = [];
$success = '';

$valid_status = ['pending','processing','confirmed','shipped','delivered','cancelled'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Handle Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update_status') {
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
                sendStatusUpdateEmail($order['order_number'], $status, $order['customer_email'], $order['customer_name']);
            }
        } catch (PDOException $e) {
            $errors[] = 'Update failed: ' . $e->getMessage();
        }
    }
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

include 'includes/header-new.php';
?>

<div class="admin-card animate-up mb-4">
    <div class="card-body p-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="stat-label small mb-1">Search Orders</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0 rounded-start-3"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0 rounded-end-3" placeholder="Order #, Customer..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
            </div>
            <div class="col-md-3">
                <label class="stat-label small mb-1">Status Filter</label>
                <select name="status_filter" class="form-select form-select-sm rounded-3">
                    <option value="">All Statuses</option>
                    <?php foreach($valid_status as $s): ?>
                        <option value="<?php echo $s; ?>" <?php echo $status_filter === $s ? 'selected' : ''; ?>><?php echo ucfirst($s); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn-premium flex-grow-1 py-1 text-[12px]">Apply</button>
                <?php if($search || $status_filter): ?>
                    <a href="manage_orders.php" class="btn-premium-outline py-1 px-3 text-decoration-none small">Clear</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<?php if ($success): ?>
    <div class="alert alert-success border-0 rounded-4 mb-4 small fw-bold animate-up">
        <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
    </div>
<?php endif; ?>

<div class="admin-card animate-up" style="animation-delay: 0.1s;">
    <div class="admin-card-header">
        <h5 class="admin-card-title mb-0">Order History <span class="badge bg-light text-dark ms-2 rounded-pill"><?php echo count($orders); ?></span></h5>
        <a href="export_orders.php" class="btn-premium-outline small py-1 px-3 text-decoration-none"><i class="fas fa-file-csv me-2"></i>Export</a>
    </div>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Order Details</th>
                    <th>Customer</th>
                    <th>Items</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($orders as $o): ?>
                <tr>
                    <td>
                        <div class="fw-black text-[13px]">#<?php echo $o['order_number'] ?: str_pad($o['order_id'], 6, '0', STR_PAD_LEFT); ?></div>
                        <div class="small text-muted fw-bold uppercase tracking-widest text-[9px] mt-0.5"><?php echo date('M d, Y', strtotime($o['order_date'])); ?></div>
                    </td>
                    <td>
                        <div class="fw-bold text-[13px]"><?php echo htmlspecialchars($o['customer_name']); ?></div>
                        <div class="small text-muted text-[11px]"><?php echo htmlspecialchars($o['email'] ?? ''); ?></div>
                    </td>
                    <td>
                        <span class="badge bg-light text-dark border rounded-pill px-2 py-1 small"><?php echo $o['item_count']; ?> Items</span>
                    </td>
                    <td class="fw-black text-[13px]"><?php echo formatCurrency($o['total_amount']); ?></td>
                    <td>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="order_id" value="<?php echo $o['order_id']; ?>">
                            <select name="status" class="form-select status-select rounded-pill fw-black px-3 py-1.5 status-<?php echo $o['order_status']; ?>" onchange="this.form.submit()">
                                <?php foreach($valid_status as $s): ?>
                                    <option value="<?php echo $s; ?>" <?php echo $o['order_status'] === $s ? 'selected' : ''; ?>><?php echo ucfirst($s); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </td>
                    <td class="text-end">
                        <div class="d-flex justify-content-end gap-1">
                            <a href="order_details.php?id=<?php echo $o['order_id']; ?>" class="btn-premium-outline px-2 py-1 text-decoration-none text-[12px]">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="invoice.php?order_id=<?php echo $o['order_id']; ?>" target="_blank" class="btn-premium-outline px-2 py-1 text-decoration-none text-[12px]">
                                <i class="fas fa-file-invoice"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($orders)): ?>
                <tr>
                    <td colspan="6" class="text-center py-5 text-muted fw-bold">No orders found matching your criteria.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer-new.php'; ?>
