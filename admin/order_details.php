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
                // Get current status first
                $current_order = $pdo->prepare('SELECT order_status FROM orders WHERE order_id = ?');
                $current_order->execute([$order_id]);
                $current_order_data = $current_order->fetch();
                $current_status = $current_order_data['order_status'] ?? 'pending';

                // Update order status using order_status column
                $stmt = $pdo->prepare('UPDATE orders SET order_status = ? WHERE order_id = ?');
                $stmt->execute([$new_status, $order_id]);
                $success = 'Order status updated successfully';

                // If status was updated to "delivered", send invoice email
                if ($current_status !== 'delivered' && $new_status === 'delivered') {
                    // Get fresh order details after update to ensure we have the latest status
                    $stmt = $pdo->prepare('SELECT o.*, u.name, u.email, o.order_status as current_status FROM orders o JOIN users u ON u.user_id = o.user_id WHERE o.order_id = ?');
                    $stmt->execute([$order_id]);
                    $order_for_email = $stmt->fetch();
                    

                    if ($order_for_email && !empty($order_for_email['email'])) {
                        // Get order items for the invoice
                        $stmt = $pdo->prepare('SELECT oi.*, p.name as product_name, oi.price as product_price 
                                            FROM order_items oi 
                                            JOIN products p ON oi.product_id = p.product_id 
                                            WHERE oi.order_id = ?');
                        $stmt->execute([$order_id]);
                        $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        $status_to_use = $order_for_email['current_status'] ?? $new_status;
                        
                        // Prepare order details array
                        $order_details = [
                            'order_id' => $order_id,
                            'items' => $order_items,
                            'subtotal' => $order_for_email['total_amount'],
                            'shipping' => 0, // Add shipping cost if applicable
                            'total' => $order_for_email['total_amount'],
                            'order_date' => $order_for_email['order_date'],
                            'status' => $status_to_use, // Use the status from the fresh query
                            'payment_method' => $order_for_email['payment_method'] ?? 'not_specified' // Add payment method if available
                        ];
                        
                        // Debug log the final order details
                        
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
                        // Only show error if we couldn't find the order or email
                        $errors[] = 'Could not find customer email for this order. Invoice not sent.';
                    }
                }
            }
        } elseif ($action === 'send_invoice') {
            $send_order_id = (int)($_POST['order_id'] ?? 0);

            if ($send_order_id > 0) {
                // Get order details for email
                $stmt = $pdo->prepare('SELECT o.*, u.name, u.email FROM orders o JOIN users u ON u.user_id = o.user_id WHERE o.order_id = ?');
                $stmt->execute([$send_order_id]);
                $order_for_email = $stmt->fetch();

                if ($order_for_email) {
                    if (empty($order_for_email['email'])) {
                        $errors[] = 'Customer email not found for this order.';
                    } else {
                        // Get order items for the invoice
                        $stmt = $pdo->prepare('SELECT oi.*, p.name as product_name, oi.price as product_price 
                                             FROM order_items oi 
                                             JOIN products p ON oi.product_id = p.product_id 
                                             WHERE oi.order_id = ?');
                        $stmt->execute([$send_order_id]);
                        $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        // Prepare order details with current status
                        $order_details = [
                            'order_id' => $send_order_id,
                            'items' => $order_items,
                            'subtotal' => $order_for_email['total_amount'],
                            'shipping' => 0, // Add shipping cost if applicable
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
    // Order with customer info
    $stmt = $pdo->prepare('SELECT o.*, u.name AS customer_name, u.email AS customer_email, u.phone AS customer_phone
                          FROM orders o JOIN users u ON u.user_id = o.user_id WHERE o.order_id = ?');
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();

    if (!$order) {
        header('Location: manage_orders.php');
        exit();
    }

    // Order items with product details and prices
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
?>

<?php
$page_title = 'Order #' . str_pad($order_id, 6, '0', STR_PAD_LEFT);
include 'includes/header-new.php';
?>

<div class="row g-4">
    <!-- Main Order Info -->
    <div class="col-lg-8">
        <!-- Order Items -->
        <div class="admin-card animate-up mb-4">
            <div class="admin-card-header">
                <h5 class="admin-card-title mb-0">Order Items <span class="badge bg-light text-dark ms-2 rounded-pill"><?php echo count($order_items); ?></span></h5>
            </div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th class="text-center">Qty</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($order_items as $item): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <img src="../assets/images/<?php echo htmlspecialchars($item['image'] ?? 'placeholder.jpg'); ?>" class="rounded-3 shadow-sm me-3" style="width: 48px; height: 48px; object-fit: cover;">
                                    <div>
                                        <div class="fw-black text-[14px]"><?php echo htmlspecialchars($item['name']); ?></div>
                                        <div class="small text-muted fw-bold uppercase tracking-widest text-[9px]">ID: #<?php echo $item['product_id']; ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo formatCurrency($item['price']); ?></td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark rounded-pill px-3"><?php echo $item['quantity']; ?></span>
                            </td>
                            <td class="text-end fw-black"><?php echo formatCurrency($item['price'] * $item['quantity']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="bg-light">
                        <tr>
                            <td colspan="3" class="text-end fw-bold py-3">Grand Total</td>
                            <td class="text-end fw-black text-[#1A1A1A] py-3 fs-5"><?php echo formatCurrency($order['total_amount']); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Addresses -->
        <div class="row g-4">
            <div class="col-md-6">
                <div class="admin-card animate-up" style="animation-delay: 0.1s;">
                    <div class="admin-card-header">
                        <h5 class="admin-card-title mb-0">Shipping Logistics</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="stat-label">Delivery Address</div>
                        <p class="small fw-bold text-muted mb-0"><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="admin-card animate-up" style="animation-delay: 0.2s;">
                    <div class="admin-card-header">
                        <h5 class="admin-card-title mb-0">Billing Details</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="stat-label">Billing Address</div>
                        <p class="small fw-bold text-muted mb-0"><?php echo nl2br(htmlspecialchars($order['billing_address'])); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Management Sidebar -->
    <div class="col-lg-4">
        <!-- Status Update -->
        <div class="admin-card animate-up mb-4">
            <div class="admin-card-header">
                <h5 class="admin-card-title mb-0">Workflow Control</h5>
            </div>
            <div class="card-body p-4">
                <form method="POST">
                    <input type="hidden" name="action" value="update_status">
                    <div class="mb-3">
                        <label class="stat-label">Current Status</label>
                        <select class="form-select status-select rounded-3 fw-black py-2.5 status-<?php echo $order['order_status']; ?>" name="status">
                            <?php foreach ($valid_statuses as $status): ?>
                                <option value="<?php echo $status; ?>" <?php echo (($order['order_status'] ?? 'pending') === $status) ? 'selected' : ''; ?>>
                                    <?php echo ucfirst($status); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn-premium w-100 py-3">Update Progress</button>
                </form>
            </div>
        </div>

        <!-- Customer Summary -->
        <div class="admin-card animate-up mb-4" style="animation-delay: 0.1s;">
            <div class="admin-card-header">
                <h5 class="admin-card-title mb-0">Customer Profile</h5>
            </div>
            <div class="card-body p-4">
                <div class="mb-3">
                    <div class="stat-label">Full Name</div>
                    <div class="fw-black"><?php echo htmlspecialchars($order['customer_name']); ?></div>
                </div>
                <div class="mb-3">
                    <div class="stat-label">Email Address</div>
                    <div class="fw-bold small text-[#1A1A1A]"><?php echo htmlspecialchars($order['customer_email']); ?></div>
                </div>
                <?php if ($order['customer_phone']): ?>
                <div>
                    <div class="stat-label">Phone Number</div>
                    <div class="fw-bold small"><?php echo htmlspecialchars($order['customer_phone']); ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="admin-card animate-up" style="animation-delay: 0.2s;">
            <div class="admin-card-header">
                <h5 class="admin-card-title mb-0">Quick Actions</h5>
            </div>
            <div class="card-body p-4">
                <form method="post" class="mb-2">
                    <input type="hidden" name="action" value="send_invoice">
                    <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                    <button type="submit" class="btn-premium-outline w-100 py-2 mb-2">
                        <i class="fas fa-paper-plane me-2"></i>Email Invoice
                    </button>
                </form>
                <a href="invoice.php?order_id=<?php echo $order_id; ?>" target="_blank" class="btn-premium-outline w-100 py-2 text-decoration-none text-center d-block">
                    <i class="fas fa-print me-2"></i>Print Invoice
                </a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer-new.php'; ?>
