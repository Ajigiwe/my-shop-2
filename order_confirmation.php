<?php
/**
 * Order Confirmation Page
 */
require_once 'includes/db.php';
require_once 'includes/email_config.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if (isset($_GET['order_id'])) {
    $order_id = (int)$_GET['order_id'];
    $_SESSION['last_order_id'] = $order_id;
} elseif (isset($_SESSION['last_order_id'])) {
    $order_id = $_SESSION['last_order_id'];
} else {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("
        SELECT o.*, u.name as customer_name, COALESCE(o.email, u.email) as customer_email
        FROM orders o
        JOIN users u ON o.user_id = u.user_id
        WHERE o.order_id = ? AND o.user_id = ?
    ");
    $stmt->execute([$order_id, $user_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (empty($order)) {
        header('Location: index.php');
        exit();
    }

    $stmt = $pdo->prepare("
        SELECT oi.*, p.name as product_name, oi.price as product_price, p.image
        FROM order_items oi
        LEFT JOIN products p ON oi.product_id = p.product_id
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$order_id]);
    $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Send confirmation email if not already sent for this session's order
    if (!isset($_SESSION['email_sent_for_order']) || $_SESSION['email_sent_for_order'] != $order_id) {
        $email_details = $order;
        $email_details['items'] = [];
        foreach ($order_items as $item) {
            $email_details['items'][] = [
                'name' => $item['product_name'],
                'price' => $item['product_price'],
                'quantity' => $item['quantity']
            ];
        }
        
        if (sendOrderConfirmationEmail($order['customer_email'], $order['customer_name'], $order_id, $email_details)) {
            $_SESSION['email_sent_for_order'] = $order_id;
        }
    }

} catch(PDOException $e) {
    error_log("Error processing order confirmation: " . $e->getMessage());
    header('Location: index.php');
    exit();
}

unset($_SESSION['last_order_id']);

$page_title = 'Order Confirmation';
include 'includes/header.php';
?>

<main class="bg-[#F9F9F9] min-h-screen py-md md:py-xl">
    <div class="max-w-[1200px] mx-auto px-6">
        <div class="flex flex-col items-center text-center mb-16">
            <div class="w-24 h-24 bg-white rounded-full border border-[#EEEEEE] shadow-sm flex items-center justify-center mb-6">
                <span class="material-symbols-outlined text-[#1A1A1A] text-[48px]">check_circle</span>
            </div>
            <h1 class="text-[36px] md:text-[48px] font-black text-[#1A1A1A] mb-2 tracking-tighter">Order Confirmed.</h1>
            <p class="text-[#666666] font-medium max-w-lg">Thank you for your purchase. Your order <span class="text-[#1A1A1A] font-black underline decoration-2">#<?php echo htmlspecialchars($order['order_number']); ?></span> has been placed successfully.</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Order Details -->
            <div class="lg:col-span-2 space-y-8">
                <div class="bg-white rounded-[2rem] p-8 md:p-10 border border-[#EEEEEE] shadow-sm">
                    <h2 class="text-[20px] font-black text-[#1A1A1A] mb-8 flex items-center gap-3">
                        <span class="material-symbols-outlined text-[24px]">shopping_bag</span> Items Ordered
                    </h2>
                    <div class="space-y-6">
                        <?php foreach ($order_items as $item): ?>
                            <div class="flex items-center gap-6 pb-6 border-b border-[#F5F5F5] last:border-0 last:pb-0">
                                <div class="w-20 h-20 rounded-2xl overflow-hidden bg-[#F9F9F9] border border-[#EEEEEE] flex-shrink-0 p-2">
                                    <img class="w-full h-full object-contain" src="assets/images/<?php echo htmlspecialchars($item['image'] ?? 'placeholder.jpg'); ?>" alt="" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h3 class="text-[15px] font-black text-[#1A1A1A] truncate"><?php echo htmlspecialchars($item['product_name']); ?></h3>
                                    <p class="text-[13px] font-bold text-[#888888]">Quantity: <?php echo $item['quantity']; ?> × <?php echo formatCurrency($item['product_price']); ?></p>
                                </div>
                                <p class="text-[16px] font-black text-[#1A1A1A]"><?php echo formatCurrency($item['product_price'] * $item['quantity']); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="bg-white rounded-[2rem] p-8 md:p-10 border border-[#EEEEEE] shadow-sm">
                        <h2 class="text-[18px] font-black text-[#1A1A1A] mb-6 flex items-center gap-3">
                            <span class="material-symbols-outlined text-[20px]">location_on</span> Delivery
                        </h2>
                        <div class="space-y-2">
                            <p class="text-[15px] font-black text-[#1A1A1A]"><?php echo htmlspecialchars($order['customer_name']); ?></p>
                            <p class="text-[14px] font-medium text-[#666666] leading-relaxed"><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
                        </div>
                    </div>
                    <div class="bg-white rounded-[2rem] p-8 md:p-10 border border-[#EEEEEE] shadow-sm">
                        <h2 class="text-[18px] font-black text-[#1A1A1A] mb-6 flex items-center gap-3">
                            <span class="material-symbols-outlined text-[20px]">payments</span> Payment
                        </h2>
                        <div class="space-y-4">
                            <p class="text-[15px] font-black text-[#1A1A1A]"><?php echo ucfirst(str_replace('_', ' ', $order['payment_method'])); ?></p>
                            <div class="inline-flex items-center gap-1.5 px-3 py-1 bg-[#FFFBEB] text-[#B45309] rounded-full text-[11px] font-black uppercase tracking-widest border border-[#FEF3C7]">
                                <span class="material-symbols-outlined text-[14px]">pending</span> Pending
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary -->
            <div class="space-y-8">
                <div class="bg-white rounded-[2rem] p-8 md:p-10 border border-[#EEEEEE] shadow-sm lg:sticky lg:top-24">
                    <h2 class="text-[20px] font-black text-[#1A1A1A] mb-8">Summary</h2>
                    <div class="space-y-4 mb-8 pb-8 border-b border-[#F5F5F5]">
                        <div class="flex justify-between text-[14px] font-bold">
                            <span class="text-[#888888]">Subtotal</span>
                            <span class="text-[#1A1A1A]"><?php echo formatCurrency($order['total_amount']); ?></span>
                        </div>
                        <div class="flex justify-between text-[14px] font-bold">
                            <span class="text-[#888888]">Delivery</span>
                            <span class="text-[#22C55E] uppercase tracking-widest text-[11px]">Free</span>
                        </div>
                    </div>
                    <div class="flex justify-between text-[#1A1A1A] mb-10">
                        <span class="text-[18px] font-black">Total</span>
                        <span class="text-[24px] font-black tracking-tighter"><?php echo formatCurrency($order['total_amount']); ?></span>
                    </div>
                    <div class="space-y-4">
                        <a href="index.php" class="w-full bg-primary text-white font-bold text-[16px] py-5 rounded-full flex items-center justify-center gap-3 hover:bg-primary shadow-xl hover:shadow-primary/10 transition-all active:scale-[0.98]">
                            Continue Shopping <span class="material-symbols-outlined text-[20px]">arrow_forward</span>
                        </a>
                        <a href="user/orders.php" class="w-full text-center text-[14px] font-black text-[#1A1A1A] py-2 hover:underline block">View My Orders</a>
                    </div>
                </div>

                <div class="bg-[#F5F5F5] rounded-[2rem] p-8 border border-[#EEEEEE]">
                    <h3 class="text-[12px] font-black text-[#1A1A1A] uppercase tracking-widest mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px]">info</span> Next Steps
                    </h3>
                    <ul class="text-[13px] text-[#666666] font-medium space-y-3">
                        <li class="flex gap-2"><span class="text-[#1A1A1A] font-black">•</span> You will receive an email confirmation shortly.</li>
                        <li class="flex gap-2"><span class="text-[#1A1A1A] font-black">•</span> Our team will call you to confirm delivery.</li>
                        <li class="flex gap-2"><span class="text-[#1A1A1A] font-black">•</span> Please have the exact amount ready if paying COD.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
