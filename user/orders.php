<?php
/**
 * User: My Orders
 * - Rebuilt to be significantly less bulky.
 * - Tighter list layout, reduced padding, and information-dense cards.
 */
require_once '../includes/db.php';
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$page_title = 'My Orders';
$user_id = $_SESSION['user_id'];

// Get user's orders
try {
    $stmt = $pdo->prepare("
        SELECT o.*, 
               (SELECT COUNT(*) FROM order_items WHERE order_id = o.order_id) as item_count 
        FROM orders o 
        WHERE o.user_id = ? 
        ORDER BY o.order_date DESC
    ");
    $stmt->execute([$user_id]);
    $orders = $stmt->fetchAll();
} catch(PDOException $e) {
    error_log("Error: " . $e->getMessage());
    $orders = [];
}

include '../includes/header.php';
?>

<div class="flex-1 bg-[#F9F9F9] min-h-screen">
    <div class="max-w-[1200px] mx-auto px-6 py-8">
        
        <!-- Compact Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
            <div>
                <nav class="flex items-center gap-1.5 text-[9px] font-black text-[#888888] uppercase tracking-widest mb-3">
                    <a href="../index.php" class="hover:text-[#1A1A1A]">Home</a>
                    <span class="material-symbols-outlined text-[12px]">chevron_right</span>
                    <a href="dashboard.php" class="hover:text-[#1A1A1A]">Dashboard</a>
                    <span class="material-symbols-outlined text-[12px]">chevron_right</span>
                    <span class="text-[#1A1A1A]">My Orders</span>
                </nav>
                <h1 class="text-[24px] font-black text-[#1A1A1A] tracking-tighter">My <span class="text-[#888888]">Orders.</span></h1>
            </div>
            <div class="bg-white border border-[#EEEEEE] px-5 py-3 rounded-lg shadow-sm">
                <div class="text-[9px] font-black text-[#888888] uppercase tracking-widest mb-0.5">Total Purchases</div>
                <div class="text-[18px] font-black text-[#1A1A1A]"><?php echo count($orders); ?> Orders</div>
            </div>
        </div>

        <?php if (empty($orders)): ?>
            <div class="bg-white rounded-xl border border-[#EEEEEE] p-12 text-center shadow-sm">
                <span class="material-symbols-outlined text-[40px] text-[#DDDDDD] mb-4">shopping_basket</span>
                <p class="text-[14px] font-bold text-[#888888]">No orders found yet.</p>
                <a href="../shop.php" class="inline-block mt-6 px-6 py-2.5 bg-primary text-white rounded-lg font-black text-[11px] uppercase tracking-widest">Shop Now</a>
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($orders as $order): ?>
                    <div class="bg-white border border-[#EEEEEE] rounded-xl shadow-sm hover:shadow-md transition-all group">
                        <div class="flex flex-col lg:flex-row items-center">
                            <!-- Left: Order Meta -->
                            <div class="p-5 lg:w-[220px] border-b lg:border-b-0 lg:border-r border-[#EEEEEE] bg-[#FBFBFB] rounded-t-xl lg:rounded-t-none lg:rounded-l-xl flex flex-col gap-1">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-[10px] font-black text-[#1A1A1A] uppercase">#<?php echo str_pad($order['order_id'], 6, '0', STR_PAD_LEFT); ?></span>
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
                                    <span class="text-[9px] font-black px-2 py-0.5 rounded-full border <?php echo $status_classes; ?> uppercase tracking-tighter">
                                        <?php echo $order['order_status']; ?>
                                    </span>
                                </div>
                                <div class="text-[14px] font-black text-[#1A1A1A]"><?php echo date('M j, Y', strtotime($order['order_date'])); ?></div>
                                <div class="text-[10px] font-bold text-[#888888] uppercase"><?php echo date('g:i A', strtotime($order['order_date'])); ?></div>
                            </div>

                            <!-- Center: Items & Previews -->
                            <div class="flex-1 p-5 flex flex-col sm:flex-row items-center justify-between gap-6">
                                <div class="flex items-center gap-5">
                                    <div class="flex -space-x-3">
                                        <?php
                                        $stmt_items = $pdo->prepare("SELECT p.image FROM order_items oi JOIN products p ON oi.product_id = p.product_id WHERE oi.order_id = ? LIMIT 3");
                                        $stmt_items->execute([$order['order_id']]);
                                        $previews = $stmt_items->fetchAll();
                                        foreach($previews as $prev): ?>
                                            <div class="w-10 h-10 rounded-lg border-2 border-white bg-white shadow-sm overflow-hidden flex items-center justify-center p-1">
                                                <img class="max-w-full max-h-full object-contain" src="../assets/images/<?php echo $prev['image']; ?>" alt="">
                                            </div>
                                        <?php endforeach; ?>
                                        <?php if($order['item_count'] > 3): ?>
                                            <div class="w-10 h-10 rounded-lg border-2 border-white bg-primary text-white flex items-center justify-center text-[10px] font-black shadow-sm">
                                                +<?php echo $order['item_count'] - 3; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <div class="text-[13px] font-black text-[#1A1A1A]"><?php echo $order['item_count']; ?> Premium Items</div>
                                        <div class="text-[11px] font-medium text-[#888888]"><?php echo str_replace('_', ' ', $order['payment_method']); ?></div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-8">
                                    <div class="text-right">
                                        <div class="text-[9px] font-black text-[#888888] uppercase tracking-widest mb-0.5">Grand Total</div>
                                        <div class="text-[18px] font-black text-[#1A1A1A] tracking-tighter">GH₵<?php echo number_format($order['total_amount'], 2); ?></div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <a href="order_details.php?id=<?php echo $order['order_id']; ?>" class="h-10 px-6 rounded-lg bg-primary text-white font-black text-[11px] uppercase tracking-widest flex items-center gap-2 hover:scale-105 transition-all shadow-sm">
                                            View
                                            <span class="material-symbols-outlined text-[16px]">visibility</span>
                                        </a>
                                        <a href="invoice.php?order_id=<?php echo $order['order_id']; ?>" target="_blank" class="w-10 h-10 rounded-lg border border-[#EEEEEE] flex items-center justify-center text-[#888888] hover:bg-[#F9F9F9] transition-all">
                                            <span class="material-symbols-outlined text-[18px]">receipt_long</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
