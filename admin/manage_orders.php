<?php
/**
 * Admin: Manage Orders
 * - Admin-only view to list orders and update status
 * - Supports viewing a single order with items and addresses
 * - Provides CSV export and invoice printing
 * - Enhanced with sorting, search, and date filtering functionality
 */
require_once '../includes/db.php';
session_start();
require_once '../includes/admin_guard.php';

$page_title = 'Manage Orders';
$errors = [];
$success = '';

$valid_status = ['pending','processing','shipped','delivered','cancelled'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Handle sorting parameters
$sort_by = $_GET['sort_by'] ?? 'order_date';
$sort_order = $_GET['sort_order'] ?? 'DESC';

// Handle search and filter parameters
$search = $_GET['search'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Validate sort parameters
$valid_sort_columns = [
    'order_id' => 'o.order_id',
    'customer' => 'u.name',
    'order_date' => 'o.order_date',
    'status' => 'o.status',
    'payment_method' => 'o.payment_method',
    'total_amount' => 'o.total_amount'
];

if (!isset($valid_sort_columns[$sort_by])) {
    $sort_by = 'order_date';
}

$sort_column = $valid_sort_columns[$sort_by];
$sort_order = strtoupper($sort_order) === 'ASC' ? 'ASC' : 'DESC';

// Toggle sort order for next click
$next_sort_order = $sort_order === 'ASC' ? 'DESC' : 'ASC';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Update order status action
        if ($action === 'update_status') {
            $order_id = (int)($_POST['order_id'] ?? 0);
            $status = sanitizeInput($_POST['status'] ?? '');
            if ($order_id <= 0) $errors[] = 'Invalid order ID';
            if (!in_array($status, $valid_status, true)) $errors[] = 'Invalid status value';
            if (empty($errors)) {
                $stmt = $pdo->prepare('UPDATE orders SET status = ? WHERE order_id = ?');
                $stmt->execute([$status, $order_id]);
                $success = 'Order status updated';
            }
        }
    }
} catch (PDOException $e) {
    error_log('Order update error: ' . $e->getMessage());
    $errors[] = 'Database error occurred';
}

// Single order details view
$order = null;
$order_items = [];
if ($action === 'view' && isset($_GET['id'])) {
    $order_id = (int)$_GET['id'];
    try {
        // Fetch base order details (customer name/email)
        $stmt = $pdo->prepare('SELECT o.*, u.name, u.email FROM orders o JOIN users u ON u.user_id = o.user_id WHERE o.order_id = ?');
        $stmt->execute([$order_id]);
        $order = $stmt->fetch();
        if ($order) {
            // Fetch order items with product names/images
            $stmt = $pdo->prepare('SELECT oi.*, p.name, p.image FROM order_items oi JOIN products p ON p.product_id = oi.product_id WHERE oi.order_id = ?');
            $stmt->execute([$order_id]);
            $order_items = $stmt->fetchAll();
        }
    } catch (PDOException $e) {
        error_log('Fetch order details error: ' . $e->getMessage());
    }
}

// Build WHERE clause for search and date filters
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(u.name LIKE ? OR u.email LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
}

if (!empty($date_from)) {
    $where_conditions[] = "DATE(o.order_date) >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $where_conditions[] = "DATE(o.order_date) <= ?";
    $params[] = $date_to;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Orders list with sorting, search, and filtering (default page)
$orders = [];
try {
    // List orders with customer name and item count for quick overview
    $stmt = $pdo->prepare("SELECT o.*, u.name, (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.order_id) AS item_count
                         FROM orders o JOIN users u ON u.user_id = o.user_id
                         $where_clause
                         ORDER BY $sort_column $sort_order");
    $stmt->execute($params);
    $orders = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Fetch orders error: ' . $e->getMessage());
}

// Helper function to create sort URLs
function getSortUrl($column, $current_sort, $current_order, $search = '', $date_from = '', $date_to = '') {
    $new_order = ($column === $current_sort && $current_order === 'ASC') ? 'DESC' : 'ASC';
    $url = "?sort_by=$column&sort_order=$new_order";
    if ($search) $url .= "&search=" . urlencode($search);
    if ($date_from) $url .= "&date_from=" . urlencode($date_from);
    if ($date_to) $url .= "&date_to=" . urlencode($date_to);
    return $url;
}

// Helper function to get sort icon
function getSortIcon($column, $current_sort, $current_order) {
    if ($column !== $current_sort) {
        return '<i class="fas fa-sort text-muted ms-1"></i>';
    }
    return $current_order === 'ASC'
        ? '<i class="fas fa-sort-up text-primary ms-1"></i>'
        : '<i class="fas fa-sort-down text-primary ms-1"></i>';
}
?>

<?php include '../includes/header.php'; ?>

<div class="container py-4">

    <!-- Back to Dashboard Button -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="dashboard.php" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
            </a>
        </div>
        <div>
            <h2 class="mb-0">Manage Orders</h2>
        </div>
        <div>
            <!-- Spacer for centering -->
        </div>
    </div>
            <h2 class="mb-0">Manage Orders</h2>
        </div>
        <div>
            <!-- Spacer for centering -->
        </div>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $e): ?>
                    <li><?php echo htmlspecialchars($e); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($order): ?>
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Order #<?php echo str_pad($order['order_id'], 6, '0', STR_PAD_LEFT); ?></h5>
                <a class="btn btn-outline-secondary btn-sm" href="manage_orders.php<?php echo isset($_GET['sort_by']) ? '?sort_by=' . $_GET['sort_by'] . '&sort_order=' . $_GET['sort_order'] . '&search=' . urlencode($search) . '&date_from=' . urlencode($date_from) . '&date_to=' . urlencode($date_to) : ''; ?>">Back to Orders</a>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Customer:</strong> <?php echo htmlspecialchars($order['name']); ?> (<?php echo htmlspecialchars($order['email']); ?>)</p>
                        <p><strong>Date:</strong> <?php echo date('M j, Y g:i A', strtotime($order['order_date'])); ?></p>
                        <p><strong>Payment:</strong> <?php echo htmlspecialchars($order['payment_method']); ?></p>
                    </div>
                    <div class="col-md-6">
                        <form method="POST" class="text-md-end">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                            <label class="form-label me-2">Status</label>
                            <select name="status" class="form-select d-inline-block w-auto me-2">
                                <?php foreach ($valid_status as $s): ?>
                                    <option value="<?php echo $s; ?>" <?php echo $order['status']===$s?'selected':''; ?>><?php echo ucfirst($s); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button class="btn btn-primary" type="submit"><i class="fas fa-save me-2"></i>Update</button>
                        </form>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-md-6">
                        <h6>Shipping Address</h6>
                        <p class="small mb-0"><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
                    </div>
                    <div class="col-md-6">
                        <h6>Billing Address</h6>
                        <p class="small mb-0"><?php echo nl2br(htmlspecialchars($order['billing_address'])); ?></p>
                    </div>
                </div>
                <hr>
                <h6 class="mb-3">Items (<?php echo count($order_items); ?>)</h6>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Qty</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($order_items as $it): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="../assets/images/<?php echo htmlspecialchars($it['image'] ?? 'placeholder.jpg'); ?>" width="40" height="40" class="rounded me-2">
                                            <span><?php echo htmlspecialchars($it['name']); ?></span>
                                        </div>
                                    </td>
                                    <td><?php echo formatCurrency($it['price']); ?></td>
                                    <td><?php echo (int)$it['quantity']; ?></td>
                                    <td><strong><?php echo formatCurrency($it['price'] * $it['quantity']); ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="text-end mt-3">
                    <h5>Total: <span class="text-primary"><?php echo formatCurrency($order['total_amount']); ?></span></h5>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h5 class="mb-0">All Orders</h5>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="export_orders.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-file-csv me-1"></i>Export CSV
                    </a>
                    <span class="badge bg-primary ms-2"><?php echo count($orders); ?></span>
                </div>
            </div>
        </div>

        <!-- Search and Filter Controls -->
        <div class="card-body border-bottom">
            <form method="GET" class="row g-3">
                <!-- Search by Customer Name -->
                <div class="col-md-4">
                    <label for="search" class="form-label">Search by Customer</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control" id="search" name="search"
                               value="<?php echo htmlspecialchars($search); ?>"
                               placeholder="Enter customer name or email...">
                    </div>
                </div>

                <!-- Date From -->
                <div class="col-md-3">
                    <label for="date_from" class="form-label">From Date</label>
                    <input type="date" class="form-control" id="date_from" name="date_from"
                           value="<?php echo htmlspecialchars($date_from); ?>">
                </div>

                <!-- Date To -->
                <div class="col-md-3">
                    <label for="date_to" class="form-label">To Date</label>
                    <input type="date" class="form-control" id="date_to" name="date_to"
                           value="<?php echo htmlspecialchars($date_to); ?>">
                </div>

                <!-- Action Buttons -->
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter me-1"></i>Filter
                    </button>
                    <a href="manage_orders.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-1"></i>Clear
                    </a>
                </div>
            </form>

            <!-- Active Filters Display -->
            <?php if (!empty($search) || !empty($date_from) || !empty($date_to)): ?>
                <div class="mt-3">
                    <small class="text-muted">Active filters:</small>
                    <div class="mt-1">
                        <?php if (!empty($search)): ?>
                            <span class="badge bg-info me-1">Customer: "<?php echo htmlspecialchars($search); ?>"</span>
                        <?php endif; ?>
                        <?php if (!empty($date_from)): ?>
                            <span class="badge bg-info me-1">From: <?php echo htmlspecialchars($date_from); ?></span>
                        <?php endif; ?>
                        <?php if (!empty($date_to)): ?>
                            <span class="badge bg-info me-1">To: <?php echo htmlspecialchars($date_to); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>
                                <a href="<?php echo getSortUrl('order_id', $sort_by, $sort_order, $search, $date_from, $date_to); ?>" class="text-decoration-none">
                                    # <?php echo getSortIcon('order_id', $sort_by, $sort_order); ?>
                                </a>
                            </th>
                            <th>
                                <a href="<?php echo getSortUrl('customer', $sort_by, $sort_order, $search, $date_from, $date_to); ?>" class="text-decoration-none">
                                    Customer <?php echo getSortIcon('customer', $sort_by, $sort_order); ?>
                                </a>
                            </th>
                            <th>
                                <a href="<?php echo getSortUrl('order_date', $sort_by, $sort_order, $search, $date_from, $date_to); ?>" class="text-decoration-none">
                                    Date <?php echo getSortIcon('order_date', $sort_by, $sort_order); ?>
                                </a>
                            </th>
                            <th>
                                <a href="<?php echo getSortUrl('status', $sort_by, $sort_order, $search, $date_from, $date_to); ?>" class="text-decoration-none">
                                    Status <?php echo getSortIcon('status', $sort_by, $sort_order); ?>
                                </a>
                            </th>
                            <th>
                                <a href="<?php echo getSortUrl('payment_method', $sort_by, $sort_order, $search, $date_from, $date_to); ?>" class="text-decoration-none">
                                    Payment <?php echo getSortIcon('payment_method', $sort_by, $sort_order); ?>
                                </a>
                            </th>
                            <th>Items</th>
                            <th>
                                <a href="<?php echo getSortUrl('total_amount', $sort_by, $sort_order, $search, $date_from, $date_to); ?>" class="text-decoration-none">
                                    Total <?php echo getSortIcon('total_amount', $sort_by, $sort_order); ?>
                                </a>
                            </th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    <i class="fas fa-search fa-2x mb-2"></i>
                                    <p>No orders found matching your criteria.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($orders as $o): ?>
                                <tr>
                                    <td>#<?php echo str_pad($o['order_id'], 6, '0', STR_PAD_LEFT); ?></td>
                                    <td><?php echo htmlspecialchars($o['name']); ?></td>
                                    <td><?php echo date('M j, Y g:i A', strtotime($o['order_date'])); ?></td>
                                    <td>
                                        <span class="badge bg-<?php
                                            echo match($o['status']) {
                                                'pending' => 'warning',
                                                'processing' => 'info',
                                                'shipped' => 'primary',
                                                'delivered' => 'success',
                                                'cancelled' => 'danger',
                                                default => 'secondary'
                                            };
                                        ?>"><?php echo ucfirst($o['status']); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($o['payment_method']); ?></td>
                                    <td><?php echo (int)$o['item_count']; ?></td>
                                    <td><?php echo formatCurrency($o['total_amount']); ?></td>
                                    <td>
                                        <a class="btn btn-sm btn-outline-primary" href="order_details.php?id=<?php echo $o['order_id']; ?>"><i class="fas fa-eye"></i> Details</a>
                                        <a class="btn btn-sm btn-outline-dark" href="invoice.php?order_id=<?php echo $o['order_id']; ?>" target="_blank" title="Invoice"><i class="fas fa-file-invoice"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

</body>
</html>
