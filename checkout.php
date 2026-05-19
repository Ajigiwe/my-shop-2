<?php
/**
 * Checkout Page
 */
require_once 'includes/db.php';
require 'vendor/autoload.php';
require_once 'includes/functions.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = 'checkout.php';
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$errors = [];

try {
    $stmt = $pdo->prepare("SELECT c.*, p.name, p.price, p.image, p.stock_quantity FROM cart c JOIN products p ON c.product_id = p.product_id WHERE c.user_id = ?");
    $stmt->execute([$user_id]);
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    header('Location: cart.php');
    exit();
}

if (empty($cart_items)) {
    header('Location: cart.php');
    exit();
}

$total = 0;
foreach ($cart_items as $item) {
    $total += $item['price'] * $item['quantity'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'process_order') {
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $shipping_address = sanitizeInput($_POST['shipping_address'] ?? '');
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $payment_method = $_POST['payment_method'] ?? 'paystack';
    
    if (empty($phone) || empty($shipping_address) || empty($email)) {
        $errors[] = 'All required fields must be filled';
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            $order_number = 'ORD-' . time() . '-' . uniqid();
            $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_amount, payment_method, shipping_address, order_status, order_date, order_number, billing_address) VALUES (?, ?, ?, ?, 'pending', NOW(), ?, ?)");
            $stmt->execute([$user_id, $total, $payment_method, $shipping_address, $order_number, $shipping_address]);
            $order_id = $pdo->lastInsertId();

            foreach ($cart_items as $item) {
                $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                $stmt->execute([$order_id, $item['product_id'], $item['quantity'], $item['price']]);
            }

            $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $pdo->commit();

            header('Content-Type: application/json');
            $redirect = ($payment_method === 'paystack') ? 'checkout_paystack.php?order_id=' . $order_id : 'order_confirmation.php?order_id=' . $order_id;
            echo json_encode(['success' => true, 'redirect' => $redirect]);
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = 'Processing failed: ' . $e->getMessage();
        }
    }
    
    if (!empty($errors)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
        exit();
    }
}

$page_title = 'Checkout';
include 'includes/header.php';
?>

<main class="bg-[#F9F9F9] min-h-screen py-md md:py-lg">
    <div class="max-w-[1200px] mx-auto px-4 md:px-md">
        <h1 class="font-headline-lg text-[24px] md:text-[30px] font-black text-[#1A1A1A] mb-8 text-center md:text-left tracking-tighter">Checkout</h1>

        <div class="flex flex-col lg:flex-row gap-6 lg:gap-10 items-start">
            <!-- Checkout Form -->
            <div class="flex-1 w-full space-y-6">
                <!-- Delivery Info -->
                <div class="bg-white rounded-2xl p-6 border border-[#EEEEEE] shadow-sm">
                    <h2 class="text-[16px] md:text-[18px] font-black text-[#1A1A1A] mb-6 flex items-center gap-2">
                        <span class="material-symbols-outlined text-[20px]">local_shipping</span> Delivery Information
                    </h2>
                    
                    <form id="checkoutForm" class="space-y-6">
                        <input type="hidden" name="action" value="process_order">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-1.5">
                                <label class="text-[11px] font-bold text-[#888888] uppercase tracking-wider ml-3">Email Address</label>
                                <input type="email" name="email" required 
                                    class="w-full bg-[#F9F9F9] border border-[#EEEEEE] rounded-xl py-2.5 md:py-3 px-5 outline-none focus:border-primary transition-colors text-[13px] md:text-[14px]" 
                                    value="<?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?>" />
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-[11px] font-bold text-[#888888] uppercase tracking-wider ml-3">Phone Number</label>
                                <input type="tel" name="phone" required 
                                    class="w-full bg-[#F9F9F9] border border-[#EEEEEE] rounded-xl py-2.5 md:py-3 px-5 outline-none focus:border-primary transition-colors text-[13px] md:text-[14px]" />
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-[#888888] uppercase tracking-wider ml-3">Shipping Address</label>
                            <textarea name="shipping_address" required rows="2" 
                                class="w-full bg-[#F9F9F9] border border-[#EEEEEE] rounded-xl py-2.5 md:py-3 px-5 outline-none focus:border-primary transition-colors text-[13px] md:text-[14px] resize-none"></textarea>
                        </div>

                        <div class="pt-6 border-t border-[#F5F5F5]">
                            <h2 class="text-[16px] md:text-[18px] font-black text-[#1A1A1A] mb-5 flex items-center gap-2">
                                <span class="material-symbols-outlined text-[20px]">payments</span> Payment Method
                            </h2>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <label class="relative cursor-pointer group">
                                    <input type="radio" name="payment_method" value="paystack" checked class="peer sr-only" />
                                    <div class="p-4 rounded-xl border-2 border-[#EEEEEE] bg-white peer-checked:border-primary peer-checked:bg-[#FAFAFA] transition-all hover:bg-[#FAFAFA]">
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 rounded-lg bg-[#F5F5F5] flex items-center justify-center text-[#1A1A1A]">
                                                <span class="material-symbols-outlined text-[18px]">credit_card</span>
                                            </div>
                                            <div>
                                                <p class="font-black text-[14px] text-[#1A1A1A] mb-0">Pay Online</p>
                                                <p class="text-[10px] text-[#888888]">Secure via Paystack</p>
                                            </div>
                                        </div>
                                    </div>
                                </label>
                                
                                <label class="relative cursor-pointer group">
                                    <input type="radio" name="payment_method" value="cash_on_delivery" class="peer sr-only" />
                                    <div class="p-4 rounded-xl border-2 border-[#EEEEEE] bg-white peer-checked:border-primary peer-checked:bg-[#FAFAFA] transition-all hover:bg-[#FAFAFA]">
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 rounded-lg bg-[#F5F5F5] flex items-center justify-center text-[#1A1A1A]">
                                                <span class="material-symbols-outlined text-[18px]">payments</span>
                                            </div>
                                            <div>
                                                <p class="font-black text-[14px] text-[#1A1A1A] mb-0">Cash on Delivery</p>
                                                <p class="text-[10px] text-[#888888]">Pay when you receive</p>
                                            </div>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Summary -->
            <div class="w-full lg:w-[380px] lg:sticky lg:top-24">
                <div class="bg-white rounded-2xl p-6 border border-[#EEEEEE] shadow-sm">
                    <h2 class="text-[16px] md:text-[18px] font-black text-[#1A1A1A] mb-6">Order Summary</h2>
                    
                    <div class="space-y-4 mb-8 max-h-[300px] overflow-y-auto pr-2 custom-scrollbar">
                        <?php foreach ($cart_items as $item): ?>
                            <div class="flex justify-between items-center text-[13px] md:text-[14px] gap-4">
                                <span class="text-[#666666] truncate flex-1"><?php echo htmlspecialchars($item['name']); ?></span>
                                <div class="flex items-center gap-3 flex-shrink-0">
                                    <span class="font-bold text-[#1A1A1A]">x<?php echo $item['quantity']; ?></span>
                                    <span class="font-bold text-[#1A1A1A] min-w-[80px] text-right"><?php echo formatCurrency($item['price'] * $item['quantity']); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="space-y-4 mb-8 pt-6 border-t border-[#F5F5F5]">
                        <div class="flex justify-between text-[14px] md:text-[15px] text-[#666666]">
                            <span>Sub Total</span>
                            <span class="font-bold text-[#1A1A1A]"><?php echo formatCurrency($total); ?></span>
                        </div>
                        <div class="flex justify-between text-[14px] md:text-[15px] text-[#666666]">
                            <span>Delivery fee</span>
                            <span class="font-bold text-[#1A1A1A]"><?php echo formatCurrency(0); ?></span>
                        </div>
                    </div>

                    <div class="flex justify-between items-center pt-6 border-t border-[#F5F5F5] mb-8">
                        <span class="text-[16px] md:text-[18px] font-bold text-[#1A1A1A]">Total</span>
                        <span class="text-[22px] md:text-[24px] font-black text-[#1A1A1A] tracking-tighter"><?php echo formatCurrency($total); ?></span>
                    </div>

                    <button id="placeOrderBtn" class="w-full bg-primary text-white font-bold text-[16px] py-4 md:py-5 rounded-full flex items-center justify-center gap-2 hover:bg-primary shadow-xl hover:shadow-primary/10 transition-all active:scale-[0.98]">
                        Place Order <span class="material-symbols-outlined text-[20px]">verified</span>
                    </button>
                    
                    <p class="mt-6 text-[10px] text-[#888888] text-center px-4 leading-relaxed">
                        By placing your order, you agree to our <a href="#" class="text-[#1A1A1A] font-bold underline">Terms and Conditions</a> and privacy policy.
                    </p>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
document.getElementById('placeOrderBtn').addEventListener('click', function() {
    const form = document.getElementById('checkoutForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const formData = new FormData(form);
    this.innerHTML = '<span class="material-symbols-outlined animate-spin text-[20px]">sync</span> Processing...';
    this.disabled = true;

    fetch('checkout.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            window.location.href = data.redirect;
        } else {
            alert(data.message);
            this.innerHTML = 'Place Order <span class="material-symbols-outlined text-[20px]">verified</span>';
            this.disabled = false;
        }
    })
    .catch(err => {
        console.error(err);
        this.innerHTML = 'Place Order <span class="material-symbols-outlined text-[20px]">verified</span>';
        this.disabled = false;
    });
});
</script>

<?php include 'includes/footer.php'; ?>