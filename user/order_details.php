<?php
/**
 * User: Order Details
 * - Rebuilt to be significantly less bulky.
 * - Minimalist, information-dense, and refined.
 */
require_once '../includes/db.php';
require_once '../includes/functions.php';
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$order_id = (int)($_GET['id'] ?? 0);
if ($order_id <= 0) {
    header('Location: orders.php');
    exit();
}

// Get order details
try {
    $stmt = $pdo->prepare("
        SELECT o.*, u.name, u.email, u.phone 
        FROM orders o 
        JOIN users u ON u.user_id = o.user_id 
        WHERE o.order_id = ? AND o.user_id = ?
    ");
    $stmt->execute([$order_id, $_SESSION['user_id']]);
    $order = $stmt->fetch();

    if (!$order) {
        header('Location: orders.php');
        exit();
    }

    // Get items
    $stmt = $pdo->prepare("
        SELECT oi.*, p.name, p.image 
        FROM order_items oi 
        JOIN products p ON p.product_id = oi.product_id 
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$order_id]);
    $items = $stmt->fetchAll();

} catch(PDOException $e) {
    error_log("Error: " . $e->getMessage());
    header('Location: orders.php');
    exit();
}

$page_title = 'Order Details';
include '../includes/header.php';
?>

<div class="flex-1 bg-[#F9F9F9] min-h-screen">
    <div class="max-w-[1200px] mx-auto px-6 py-8">
        
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-8 bg-white border border-[#EEEEEE] p-6 rounded-xl shadow-sm">
            <div>
                <nav class="flex items-center gap-1.5 text-[9px] font-black text-[#888888] uppercase tracking-widest mb-3">
                    <a href="dashboard.php" class="hover:text-[#1A1A1A]">Dashboard</a>
                    <span class="material-symbols-outlined text-[12px]">chevron_right</span>
                    <a href="orders.php" class="hover:text-[#1A1A1A]">Orders</a>
                    <span class="material-symbols-outlined text-[12px]">chevron_right</span>
                    <span class="text-[#1A1A1A]">#<?php echo str_pad($order_id, 6, '0', STR_PAD_LEFT); ?></span>
                </nav>
                <h1 class="text-[24px] font-black text-[#1A1A1A] tracking-tighter mb-1">Order Summary</h1>
                <p class="text-[12px] text-[#666666] font-medium">Placed on <?php echo date('F j, Y', strtotime($order['order_date'])); ?></p>
            </div>
            <div class="flex items-center gap-3">
                <?php
                $status = strtolower($order['order_status']);
                $status_classes = match($status) {
                    'pending' => 'bg-amber-100 text-amber-700 border-amber-200',
                    'processing' => 'bg-blue-100 text-blue-700 border-blue-200',
                    'shipped' => 'bg-purple-100 text-purple-700 border-purple-200',
                    'delivered' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                    'cancelled' => 'bg-rose-100 text-rose-700 border-rose-200',
                    default => 'bg-gray-100 text-gray-700 border-gray-200'
                };
                ?>
                <span class="px-4 py-2 rounded-lg border <?php echo $status_classes; ?> text-[10px] font-black uppercase tracking-widest">
                    Status: <?php echo $order['order_status']; ?>
                </span>
                <a href="invoice.php?order_id=<?php echo $order_id; ?>" target="_blank" class="h-10 px-6 rounded-lg border border-primary text-[#1A1A1A] font-black text-[11px] uppercase tracking-widest hover:bg-primary hover:text-white transition-all flex items-center gap-2">
                    <span class="material-symbols-outlined text-[16px]">receipt_long</span> Invoice
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Left: Items -->
            <div class="lg:col-span-8 space-y-6">
                <div class="bg-white border border-[#EEEEEE] rounded-xl shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-[#EEEEEE] bg-[#FBFBFB] flex items-center justify-between">
                        <h3 class="text-[12px] font-black text-[#1A1A1A] uppercase tracking-widest">Purchased Items (<?php echo count($items); ?>)</h3>
                    </div>
                    <div class="divide-y divide-[#EEEEEE]">
                        <?php foreach ($items as $item): ?>
                            <div class="p-5 flex items-center justify-between gap-5 hover:bg-[#F9F9F9] transition-colors">
                                <div class="flex items-center gap-5">
                                    <div class="w-12 h-12 rounded-lg border border-[#EEEEEE] bg-[#F9F9F9] p-2 flex items-center justify-center flex-shrink-0">
                                        <img src="../assets/images/<?php echo htmlspecialchars($item['image'] ?? 'placeholder.jpg'); ?>" class="max-w-full max-h-full object-contain">
                                    </div>
                                    <div>
                                        <h4 class="text-[13px] font-black text-[#1A1A1A] leading-tight mb-1"><?php echo htmlspecialchars($item['name']); ?></h4>
                                        <p class="text-[11px] font-bold text-[#888888]">Qty: <?php echo $item['quantity']; ?> × GH₵<?php echo number_format($item['price'], 2); ?></p>
                                    </div>
                                </div>
                                <div class="text-[14px] font-black text-[#1A1A1A]">GH₵<?php echo number_format($item['price'] * $item['quantity'], 2); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <!-- Totals -->
                    <div class="p-6 border-t border-[#EEEEEE] bg-[#FBFBFB]">
                        <div class="max-w-[200px] ml-auto space-y-2">
                            <div class="flex justify-between text-[11px] font-medium text-[#666666]">
                                <span>Subtotal</span>
                                <span>GH₵<?php echo number_format($order['total_amount'], 2); ?></span>
                            </div>
                            <div class="flex justify-between text-[11px] font-medium text-[#666666]">
                                <span>Shipping</span>
                                <span class="text-green-600 font-black">FREE</span>
                            </div>
                            <div class="h-[1px] bg-[#EEEEEE] my-2"></div>
                            <div class="flex justify-between items-center">
                                <span class="text-[13px] font-black text-[#1A1A1A]">Total</span>
                                <span class="text-[18px] font-black text-[#1A1A1A] tracking-tighter">GH₵<?php echo number_format($order['total_amount'], 2); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Simple Tracker -->
                <div class="bg-white border border-[#EEEEEE] rounded-xl p-6 shadow-sm">
                    <h3 class="text-[11px] font-black text-[#888888] uppercase tracking-widest mb-6">Delivery Progress</h3>
                    <div class="flex items-center justify-between relative px-4">
                        <?php
                        $status_steps = ['pending', 'processing', 'shipped', 'delivered'];
                        $current_status = strtolower($order['order_status']);
                        $status_map = ['pending' => 0, 'processing' => 1, 'shipped' => 2, 'delivered' => 3];
                        $current_idx = $status_map[$current_status] ?? 0;
                        ?>
                        <div class="absolute top-4 left-8 right-8 h-1 bg-[#F5F5F5] rounded-full z-0">
                            <div class="h-full bg-primary transition-all duration-1000" style="width: <?php echo ($current_idx / 3) * 100; ?>%"></div>
                        </div>
                        <?php foreach ($status_steps as $idx => $step): ?>
                            <div class="flex flex-col items-center gap-3 relative z-10">
                                <div class="w-8 h-8 rounded-lg flex items-center justify-center border-2 transition-all <?php echo $idx <= $current_idx ? 'bg-primary border-primary text-white shadow-md' : 'bg-white border-[#EEEEEE] text-[#DDDDDD]'; ?>">
                                    <span class="material-symbols-outlined text-[16px]">
                                        <?php echo match($step) { 'pending' => 'receipt', 'processing' => 'package_2', 'shipped' => 'local_shipping', 'delivered' => 'verified' }; ?>
                                    </span>
                                </div>
                                <span class="text-[9px] font-black uppercase tracking-widest <?php echo $idx <= $current_idx ? 'text-[#1A1A1A]' : 'text-[#DDDDDD]'; ?>"><?php echo $step; ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Right: Details -->
            <div class="lg:col-span-4 space-y-6">
                <!-- Shipping Info -->
                <div class="bg-white border border-[#EEEEEE] rounded-xl p-6 shadow-sm">
                    <h3 class="text-[11px] font-black text-[#888888] uppercase tracking-widest mb-5">Shipping Information</h3>
                    <div class="bg-[#FBFBFB] rounded-lg p-4 mb-5 border border-[#F5F5F5]">
                        <p class="text-[13px] font-black text-[#1A1A1A] mb-1"><?php echo htmlspecialchars($order['name']); ?></p>
                        <p class="text-[12px] text-[#666666] font-medium leading-relaxed"><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-[#F9F9F9] border border-[#EEEEEE] flex items-center justify-center text-[#1A1A1A]">
                            <span class="material-symbols-outlined text-[16px]">call</span>
                        </div>
                        <div class="text-[13px] font-black text-[#1A1A1A]"><?php echo htmlspecialchars($order['phone'] ?? 'N/A'); ?></div>
                    </div>
                </div>

                <!-- Payment Info -->
                <div class="bg-white border border-[#EEEEEE] rounded-xl p-6 shadow-sm">
                    <h3 class="text-[11px] font-black text-[#888888] uppercase tracking-widest mb-5">Payment Details</h3>
                    <div class="flex items-center gap-4 p-4 rounded-lg bg-[#F9F9F9] border border-[#EEEEEE]">
                        <div class="w-8 h-6 rounded bg-white border border-[#EEEEEE] flex items-center justify-center text-[#1A1A1A]">
                            <span class="material-symbols-outlined text-[16px]">payments</span>
                        </div>
                        <div>
                            <p class="text-[12px] font-black text-[#1A1A1A] uppercase tracking-tighter"><?php echo str_replace('_', ' ', $order['payment_method']); ?></p>
                            <p class="text-[9px] font-bold text-[#888888]">REF: #<?php echo $order['order_id']; ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
