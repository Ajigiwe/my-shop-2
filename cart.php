<?php
/**
 * Storefront: Shopping Cart
 */
require_once 'includes/db.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = 'cart.php';
    header('Location: login.php');
    exit();
}

// Handle cart updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_cart'])) {
        $product_id = (int)$_POST['product_id'];
        $quantity = (int)$_POST['quantity'];
        if ($quantity > 0) {
            $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$quantity, $_SESSION['user_id'], $product_id]);
        }
    } elseif (isset($_POST['remove_item'])) {
        $product_id = (int)$_POST['product_id'];
        $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$_SESSION['user_id'], $product_id]);
    } elseif (isset($_POST['clear_all'])) {
        $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
    }
    
    // If it's an AJAX request, return success instead of redirecting
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        echo json_encode(['success' => true]);
        exit();
    }
    
    header('Location: cart.php');
    exit();
}

$cart_items = [];
$total = 0;
try {
    $stmt = $pdo->prepare("SELECT c.*, p.name, p.price, p.image, p.stock_quantity, cat.category_name
                          FROM cart c
                          JOIN products p ON c.product_id = p.product_id
                          JOIN categories cat ON p.category_id = cat.category_id
                          WHERE c.user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    foreach ($cart_items as $item) {
        $total += $item['price'] * $item['quantity'];
    }
} catch(PDOException $e) {
    error_log("Error fetching cart: " . $e->getMessage());
}

$page_title = 'Shopping Cart';
include 'includes/header.php';
?>

<main class="bg-[#F9F9F9] min-h-screen py-md">
    <div class="max-w-[1200px] mx-auto px-md">
        <h1 class="font-headline-lg text-[36px] font-black text-[#1A1A1A] mb-lg">Shopping Cart</h1>

        <?php if (empty($cart_items)): ?>
            <div class="bg-white rounded-[2rem] p-xl text-center border border-[#EEEEEE] shadow-sm">
                <p class="text-[#666666] font-body-lg mb-lg">Your shopping cart is empty</p>
                <a href="shop.php" class="inline-block bg-primary text-white font-bold px-xl py-4 rounded-full hover:scale-105 transition-transform">Browse Shop</a>
            </div>
        <?php else: ?>
            <div class="flex flex-col lg:flex-row gap-xl items-start">
                <!-- Products List -->
                <div class="flex-1 w-full">
                    <div class="bg-white rounded-[2rem] border border-[#EEEEEE] overflow-hidden shadow-sm">
                        <!-- Table Header (Desktop Only) -->
                        <div class="hidden md:grid grid-cols-[1fr,150px,120px,80px] px-8 py-6 border-b border-[#F5F5F5]">
                            <span class="text-[14px] font-bold text-[#1A1A1A]">Product Code</span>
                            <span class="text-[14px] font-bold text-[#1A1A1A] text-center">Quantity</span>
                            <span class="text-[14px] font-bold text-[#1A1A1A] text-center">Total</span>
                            <span class="text-[14px] font-bold text-[#1A1A1A] text-right">Action</span>
                        </div>

                        <!-- Items -->
                        <div class="divide-y divide-[#F5F5F5]">
                            <?php foreach ($cart_items as $item): ?>
                                <div class="flex flex-col md:grid md:grid-cols-[1fr,150px,120px,80px] items-center px-6 py-6 md:px-8 md:py-8 hover:bg-[#FAFAFA] transition-colors gap-6 md:gap-0">
                                    <!-- Product Info -->
                                    <div class="flex items-center gap-4 md:gap-6 w-full md:w-auto">
                                        <div class="w-20 h-20 md:w-24 md:h-24 rounded-2xl overflow-hidden bg-[#F5F5F5] border border-[#EEEEEE] p-2 flex-shrink-0">
                                            <img class="w-full h-full object-contain" src="assets/images/<?php echo htmlspecialchars($item['image'] ?? 'placeholder.jpg'); ?>" onerror="this.src='assets/images/placeholder.jpg'" alt="" />
                                        </div>
                                        <div class="min-w-0">
                                            <h3 class="font-bold text-[16px] md:text-[18px] text-[#1A1A1A] truncate"><?php echo htmlspecialchars($item['name']); ?></h3>
                                            <p class="text-[12px] md:text-[13px] text-[#888888] mt-1">Category: <span class="text-[#1A1A1A] font-medium"><?php echo htmlspecialchars($item['category_name']); ?></span></p>
                                        </div>
                                    </div>

                                    <!-- Quantity (Mobile: Label + Controls) -->
                                    <div class="flex flex-col md:flex-row items-center gap-2 md:gap-0 md:justify-center w-full md:w-auto">
                                        <span class="md:hidden text-[11px] font-bold text-[#888888] uppercase tracking-widest">Quantity</span>
                                        <div class="flex items-center bg-white rounded-full border border-[#DDDDDD] p-1 px-2 gap-3 shadow-sm">
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                                <input type="hidden" name="quantity" value="<?php echo $item['quantity'] + 1; ?>">
                                                <input type="hidden" name="update_cart" value="1">
                                                <button type="submit" class="w-8 h-8 rounded-full flex items-center justify-center hover:bg-[#F5F5F5] text-[#1A1A1A] transition-colors">
                                                    <span class="material-symbols-outlined text-[18px]">add</span>
                                                </button>
                                            </form>
                                            <span class="w-6 text-center font-bold text-[16px] text-[#1A1A1A]"><?php echo $item['quantity']; ?></span>
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                                <input type="hidden" name="quantity" value="<?php echo max(1, $item['quantity'] - 1); ?>">
                                                <input type="hidden" name="update_cart" value="1">
                                                <button type="submit" class="w-8 h-8 rounded-full flex items-center justify-center hover:bg-[#F5F5F5] text-[#1A1A1A] transition-colors" <?php echo $item['quantity'] <= 1 ? 'disabled' : ''; ?>>
                                                    <span class="material-symbols-outlined text-[18px]">remove</span>
                                                </button>
                                            </form>
                                        </div>
                                    </div>

                                    <!-- Price (Mobile: Label + Total) -->
                                    <div class="flex flex-col md:flex-row items-center gap-1 md:gap-0 md:justify-center w-full md:w-auto">
                                        <span class="md:hidden text-[11px] font-bold text-[#888888] uppercase tracking-widest">Total</span>
                                        <span class="font-bold text-[18px] text-[#1A1A1A]"><?php echo formatCurrency($item['price'] * $item['quantity']); ?></span>
                                    </div>

                                    <!-- Remove -->
                                    <div class="flex justify-center md:justify-end w-full md:w-auto pt-4 md:pt-0 border-t md:border-none border-[#F5F5F5]">
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                            <input type="hidden" name="remove_item" value="1">
                                            <button type="submit" class="flex items-center gap-2 md:block md:w-10 md:h-10 rounded-full md:flex items-center justify-center text-[#888888] hover:text-[#FF4444] hover:bg-[#FFF5F5] transition-all px-4 md:px-0 py-2 md:py-0">
                                                <span class="material-symbols-outlined text-[20px]">delete</span>
                                                <span class="md:hidden text-[14px] font-bold">Remove Item</span>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="mt-8">
                        <a href="shop.php" class="inline-block bg-primary text-white font-bold px-10 py-4 rounded-full shadow-lg hover:bg-primary transition-all hover:-translate-y-0.5 active:translate-y-0">
                            Update Cart
                        </a>
                    </div>
                </div>

                <!-- Summary -->
                <div class="w-full lg:w-[380px] lg:sticky lg:top-24">
                    <div class="bg-white rounded-[2.5rem] p-8 border border-[#EEEEEE] shadow-sm">
                        <h2 class="text-[20px] font-bold text-[#1A1A1A] mb-8">Order Summery</h2>
                        
                        <div class="relative mb-8">
                            <input type="text" placeholder="Discount voucher" class="w-full bg-white border border-[#DDDDDD] rounded-full py-4 px-6 outline-none focus:border-primary transition-colors text-[14px]">
                            <button class="absolute right-2 top-2 bottom-2 bg-white border border-[#DDDDDD] rounded-full px-6 font-bold text-[14px] hover:bg-[#F5F5F5] transition-colors">Apply</button>
                        </div>

                        <div class="space-y-4 mb-8">
                            <div class="flex justify-between text-[15px] text-[#666666]">
                                <span>Sub Total</span>
                                <span class="font-bold text-[#1A1A1A]"><?php echo formatCurrency($total); ?></span>
                            </div>
                            <div class="flex justify-between text-[15px] text-[#666666]">
                                <span>Discount (0%)</span>
                                <span class="font-bold text-[#FF4444]">-<?php echo formatCurrency(0); ?></span>
                            </div>
                            <div class="flex justify-between text-[15px] text-[#666666]">
                                <span>Delivery fee</span>
                                <span class="font-bold text-[#1A1A1A]"><?php echo formatCurrency(0); ?></span>
                            </div>
                        </div>

                        <div class="flex justify-between items-center pt-6 border-t border-[#F5F5F5] mb-8">
                            <span class="text-[18px] font-bold text-[#1A1A1A]">Total</span>
                            <span class="text-[24px] font-black text-[#1A1A1A] tracking-tighter"><?php echo formatCurrency($total); ?></span>
                        </div>

                        <div class="flex items-center gap-3 p-4 bg-[#F9F9F9] rounded-2xl mb-8">
                            <span class="material-symbols-outlined text-[20px] text-[#44BB44]">verified_user</span>
                            <p class="text-[11px] text-[#666666] leading-tight">90 Day Limited Warranty against manufacturer's defects <a href="#" class="text-[#1A1A1A] font-bold underline">Details</a></p>
                        </div>

                        <a href="checkout.php" class="w-full bg-primary text-white font-bold text-[16px] py-5 rounded-full flex items-center justify-center gap-2 hover:bg-primary shadow-xl hover:shadow-primary/10 transition-all active:scale-[0.98]">
                            Checkout Now
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
function attachCartListeners() {
    // Intercept all forms in the cart to submit via AJAX
    const forms = document.querySelectorAll('main form');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalContent = submitBtn.innerHTML;
            
            // Show loading state
            submitBtn.innerHTML = '<span class="material-symbols-outlined animate-spin text-[18px]">sync</span>';
            submitBtn.disabled = true;
            
            const formData = new FormData(form);
            
            fetch('cart.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Fetch the updated cart page silently and swap the content
                    fetch('cart.php')
                    .then(res => res.text())
                    .then(html => {
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        const newMain = doc.querySelector('main');
                        document.querySelector('main').innerHTML = newMain.innerHTML;
                        
                        // Re-attach listeners to the new DOM elements
                        attachCartListeners();
                    });
                } else {
                    alert('Error updating cart');
                    submitBtn.innerHTML = originalContent;
                    submitBtn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                window.location.reload();
            });
        });
    });
}

// Initial attachment
attachCartListeners();
});
</script>

<?php include 'includes/footer.php'; ?>