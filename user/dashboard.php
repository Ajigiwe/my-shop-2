<?php
/**
 * User Dashboard
 * - Rebuilt to be significantly less bulky and more information-dense.
 * - Minimalist, clean, and professional.
 */
require_once '../includes/db.php';
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$page_title = 'Dashboard';

// Get user info
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    // Get stats
    $stmt = $pdo->prepare("SELECT COUNT(*) as total, SUM(total_amount) as spent FROM orders WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $stats = $stmt->fetch();
    
    // Get recent orders
    $stmt = $pdo->prepare("
        SELECT o.*, 
               (SELECT COUNT(*) FROM order_items WHERE order_id = o.order_id) as item_count 
        FROM orders o 
        WHERE o.user_id = ? 
        ORDER BY o.order_date DESC 
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $recent_orders = $stmt->fetchAll();
    
} catch(PDOException $e) {
    error_log("Error: " . $e->getMessage());
}

include '../includes/header.php';
?>

<div class="flex-1 bg-[#F9F9F9] min-h-screen">
    <div class="max-w-[1200px] mx-auto px-6 py-8">
        
        <!-- Compact Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8 bg-white border border-[#EEEEEE] p-6 rounded-xl shadow-sm">
            <div class="flex items-center gap-5">
                <div class="w-12 h-12 rounded-lg bg-primary flex items-center justify-center text-white text-[20px] font-black">
                    <?php echo substr($user['name'], 0, 1); ?>
                </div>
                <div>
                    <h1 class="text-[20px] font-black text-[#1A1A1A] tracking-tight">Welcome, <?php echo explode(' ', htmlspecialchars($user['name']))[0]; ?></h1>
                    <p class="text-[12px] text-[#666666] font-medium">Overview of your account activity and orders.</p>
                </div>
            </div>
            <div class="flex items-center gap-6">
                <div class="text-right">
                    <div class="text-[9px] font-black text-[#888888] uppercase tracking-widest mb-0.5">Total Spent</div>
                    <div class="text-[16px] font-black text-[#1A1A1A]">GH₵<?php echo number_format($stats['spent'] ?? 0, 2); ?></div>
                </div>
                <div class="w-[1px] h-8 bg-[#EEEEEE]"></div>
                <div class="text-right">
                    <div class="text-[9px] font-black text-[#888888] uppercase tracking-widest mb-0.5">Orders</div>
                    <div class="text-[16px] font-black text-[#1A1A1A]"><?php echo $stats['total'] ?? 0; ?></div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Left: Main Activity -->
            <div class="lg:col-span-8 space-y-6">
                
                <!-- Recent Activity Table-style -->
                <div class="bg-white border border-[#EEEEEE] rounded-xl shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-[#EEEEEE] flex items-center justify-between bg-[#FBFBFB]">
                        <h3 class="text-[14px] font-black text-[#1A1A1A] uppercase tracking-widest">Recent Orders</h3>
                        <a href="orders.php" class="text-[11px] font-black text-[#1A1A1A] hover:underline flex items-center gap-1">
                            View All <span class="material-symbols-outlined text-[14px]">chevron_right</span>
                        </a>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <tbody class="divide-y divide-[#EEEEEE]">
                                <?php if (empty($recent_orders)): ?>
                                    <tr>
                                        <td class="p-8 text-center text-[#888888] text-[12px] font-medium">No recent orders found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recent_orders as $order): ?>
                                        <tr class="hover:bg-[#F9F9F9] transition-colors group">
                                            <td class="px-5 py-4">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-8 h-8 rounded-lg bg-[#F5F5F5] border border-[#EEEEEE] flex items-center justify-center text-[#1A1A1A]">
                                                        <span class="material-symbols-outlined text-[16px]">shopping_bag</span>
                                                    </div>
                                                    <div>
                                                        <div class="text-[13px] font-black text-[#1A1A1A]">#<?php echo str_pad($order['order_id'], 6, '0', STR_PAD_LEFT); ?></div>
                                                        <div class="text-[11px] text-[#888888]"><?php echo date('M j, Y', strtotime($order['order_date'])); ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-5 py-4">
                                                <div class="text-[11px] font-bold text-[#888888]"><?php echo $order['item_count']; ?> Items</div>
                                                <div class="text-[13px] font-black text-[#1A1A1A]">GH₵<?php echo number_format($order['total_amount'], 2); ?></div>
                                            </td>
                                            <td class="px-5 py-4 text-right">
                                                <span class="inline-block px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest border border-[#EEEEEE] bg-white text-[#1A1A1A] mb-1">
                                                    <?php echo $order['order_status']; ?>
                                                </span>
                                            </td>
                                            <td class="px-5 py-4 text-right">
                                                <a href="order_details.php?id=<?php echo $order['order_id']; ?>" class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-[#EEEEEE] text-[#1A1A1A] hover:bg-primary hover:text-white transition-all">
                                                    <span class="material-symbols-outlined text-[16px]">visibility</span>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>



            </div>

            <!-- Right: Account Sidebar -->
            <div class="lg:col-span-4 space-y-6">
                
                <!-- Account Profile -->
                <div class="bg-white border border-[#EEEEEE] rounded-xl p-6 shadow-sm">
                    <h3 class="text-[11px] font-black text-[#888888] uppercase tracking-widest mb-5">Account Details</h3>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-[12px] font-medium text-[#666666]">Name</span>
                            <span class="text-[13px] font-black text-[#1A1A1A]"><?php echo htmlspecialchars($user['name']); ?></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-[12px] font-medium text-[#666666]">Email</span>
                            <span class="text-[13px] font-black text-[#1A1A1A] truncate max-w-[150px]"><?php echo htmlspecialchars($user['email']); ?></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-[12px] font-medium text-[#666666]">Phone</span>
                            <span class="text-[13px] font-black text-[#1A1A1A]"><?php echo htmlspecialchars($user['phone'] ?? 'Not set'); ?></span>
                        </div>
                        <div class="pt-2">
                            <a href="profile.php" class="flex items-center justify-center w-full h-10 rounded-lg bg-[#F5F5F5] border border-[#EEEEEE] text-[#1A1A1A] font-black text-[11px] hover:bg-primary hover:text-white transition-all">
                                Edit Settings
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Support Card -->
                <div class="bg-white border border-[#EEEEEE] rounded-xl p-6 shadow-sm">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-8 h-8 rounded-lg bg-[#F9F9F9] border border-[#EEEEEE] flex items-center justify-center text-[#1A1A1A]">
                            <span class="material-symbols-outlined text-[16px]">support_agent</span>
                        </div>
                        <h3 class="text-[14px] font-black text-[#1A1A1A] tracking-tight">Need Support?</h3>
                    </div>
                    <p class="text-[12px] text-[#666666] mb-5 leading-relaxed">Our team is available to assist you with any questions regarding your orders.</p>
                    <a href="mailto:support@aso-market.com" class="text-[11px] font-black text-[#1A1A1A] underline flex items-center gap-1 hover:text-[#888888] transition-colors">
                        Go to Support Center <span class="material-symbols-outlined text-[14px]">open_in_new</span>
                    </a>
                </div>

            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
