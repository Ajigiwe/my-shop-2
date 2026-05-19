<?php
/**
 * User: Wishlist
 * - Displays all products added to the user's wishlist
 * - Reuses the premium compact grid design
 */
require_once '../includes/db.php';
require_once '../includes/functions.php';
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$page_title = 'My Wishlist';

// Get user info for sidebar
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
} catch(PDOException $e) {
    error_log("Error fetching user: " . $e->getMessage());
}

// Fetch wishlist products
$products = [];
try {
    $stmt = $pdo->prepare("
        SELECT p.*, c.category_name, w.created_at as added_at,
        (SELECT GROUP_CONCAT(image_path ORDER BY is_primary DESC, image_id ASC) 
         FROM product_images 
         WHERE product_id = p.product_id) as all_images 
        FROM products p
        JOIN wishlist w ON p.product_id = w.product_id
        LEFT JOIN categories c ON p.category_id = c.category_id
        WHERE w.user_id = ?
        ORDER BY w.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $products = $stmt->fetchAll();
} catch(PDOException $e) {
    error_log("Error fetching wishlist: " . $e->getMessage());
}

// All displayed items are intrinsically in the user's wishlist
$user_wishlist = array_column($products, 'product_id');

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
                    <span class="text-[#1A1A1A]">My Wishlist</span>
                </nav>
                <h1 class="text-[24px] font-black text-[#1A1A1A] tracking-tighter mb-1">My Wishlist</h1>
                <p class="text-[12px] text-[#666666] font-medium">Items you've saved for later.</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="../shop.php" class="h-10 px-6 rounded-lg bg-[#1A1A1A] text-white font-black text-[11px] uppercase tracking-widest flex items-center gap-2 hover:scale-105 transition-all shadow-lg">
                    <span class="material-symbols-outlined text-[16px]">shopping_bag</span> Continue Shopping
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Sidebar Nav -->
            <div class="lg:col-span-4 space-y-6">
                <div class="bg-white border border-[#EEEEEE] rounded-xl p-5 shadow-sm">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-10 h-10 rounded-lg bg-primary flex items-center justify-center text-white text-[18px] font-black">
                            <?php echo substr($user['name'], 0, 1); ?>
                        </div>
                        <div>
                            <h2 class="text-[14px] font-black text-[#1A1A1A] tracking-tight"><?php echo htmlspecialchars($user['name']); ?></h2>
                            <p class="text-[10px] font-bold text-[#888888]"><?php echo htmlspecialchars($user['email']); ?></p>
                        </div>
                    </div>
                    <div class="space-y-1">
                        <a href="dashboard.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-[#F9F9F9] transition-all text-[#888888] hover:text-[#1A1A1A] font-black text-[11px] uppercase tracking-widest">
                            <span class="material-symbols-outlined text-[18px]">dashboard</span> Dashboard
                        </a>
                        <a href="orders.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-[#F9F9F9] transition-all text-[#888888] hover:text-[#1A1A1A] font-black text-[11px] uppercase tracking-widest">
                            <span class="material-symbols-outlined text-[18px]">shopping_bag</span> My Orders
                        </a>
                        <div class="flex items-center gap-3 p-3 rounded-lg bg-primary text-white font-black text-[11px] uppercase tracking-widest shadow-md">
                            <span class="material-symbols-outlined text-[18px]">favorite</span> My Wishlist
                        </div>
                        <a href="profile.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-[#F9F9F9] transition-all text-[#888888] hover:text-[#1A1A1A] font-black text-[11px] uppercase tracking-widest">
                            <span class="material-symbols-outlined text-[18px]">settings</span> Settings
                        </a>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="lg:col-span-8">
                <?php if (empty($products)): ?>
                    <div class="bg-white border border-[#EEEEEE] rounded-xl p-12 shadow-sm text-center flex flex-col items-center justify-center min-h-[400px]">
                        <div class="w-20 h-20 bg-[#F9F9F9] rounded-full flex items-center justify-center mb-4">
                            <span class="material-symbols-outlined text-[40px] text-[#DDDDDD]">heart_broken</span>
                        </div>
                        <h3 class="text-[18px] font-black text-[#1A1A1A] tracking-tight mb-2">Your wishlist is empty</h3>
                        <p class="text-[13px] text-[#888888] font-medium max-w-[250px] mx-auto mb-6">Looks like you haven't saved any items yet. Start exploring our shop!</p>
                        <a href="../shop.php" class="h-11 px-8 rounded-full bg-primary text-white font-black text-[12px] uppercase tracking-widest shadow-lg hover:scale-105 transition-transform flex items-center justify-center">
                            Explore Products
                        </a>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 md:gap-6">
                        <?php foreach ($products as $product): 
                            $images = !empty($product['all_images']) ? explode(',', $product['all_images']) : [];
                            $main_img = !empty($images[0]) ? $images[0] : ($product['image'] ?? 'placeholder.jpg');
                            $hover_img = !empty($images[1]) ? $images[1] : null;
                        ?>
                            <div class="bg-white rounded-[1.5rem] p-3 border border-[#EEEEEE] shadow-sm hover:shadow-xl transition-all group relative flex flex-col justify-between h-full">
                                <!-- Image Section -->
                                <div class="relative aspect-square rounded-[1rem] overflow-hidden bg-[#F9F9F9] mb-4">
                                    <a href="../product.php?id=<?php echo $product['product_id']; ?>" class="block w-full h-full relative">
                                        <img class="w-full h-full object-contain p-4 transition-all duration-700 <?php echo $hover_img ? 'group-hover:opacity-0' : 'group-hover:scale-110'; ?>" 
                                             src="../assets/images/<?php echo htmlspecialchars($main_img); ?>" 
                                             alt="<?php echo htmlspecialchars($product['name']); ?>" />
                                        <?php if ($hover_img): ?>
                                            <img class="absolute inset-0 w-full h-full object-contain p-4 opacity-0 group-hover:opacity-100 scale-110 group-hover:scale-100 transition-all duration-700" 
                                                 src="../assets/images/<?php echo htmlspecialchars($hover_img); ?>" 
                                                 alt="<?php echo htmlspecialchars($product['name']); ?>" />
                                        <?php endif; ?>
                                    </a>
                                    
                                    <!-- Floating Actions -->
                                    <div class="absolute left-2 top-2 flex flex-col gap-2 translate-x-0 lg:translate-x-[-3rem] lg:group-hover:translate-x-0 transition-transform duration-500 z-20">
                                        <button class="w-8 h-8 rounded-full bg-white shadow-md flex items-center justify-center text-red-500 hover:bg-red-500 hover:text-white transition-colors wishlist-btn"
                                                data-product-id="<?php echo $product['product_id']; ?>">
                                            <span class="material-symbols-outlined text-[18px] fill-1">favorite</span>
                                        </button>
                                    </div>

                                    <?php if (($product['original_price'] ?? 0) > $product['price']): ?>
                                        <div class="absolute right-0 top-0 z-30 pointer-events-none">
                                            <div class="bg-[#FF3B30] text-white text-[9px] font-black px-2 py-1 rounded-bl-xl shadow-md uppercase tracking-tighter">Sale</div>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Info Section -->
                                <div class="px-1 flex-1 flex flex-col">
                                    <h3 class="text-[13px] font-black text-[#1A1A1A] mb-1 truncate leading-tight">
                                        <a href="../product.php?id=<?php echo $product['product_id']; ?>" class="hover:text-primary transition-colors">
                                            <?php echo htmlspecialchars($product['name']); ?>
                                        </a>
                                    </h3>
                                    
                                     <!-- Rating -->
                                     <?php if (isset($product['review_count']) && $product['review_count'] > 0): ?>
                                     <div class="flex items-center gap-1 mb-2">
                                         <div class="flex text-[#FFB800] scale-[0.7] origin-left">
                                             <?php for($i=1; $i<=5; $i++): ?>
                                                 <span class="material-symbols-outlined text-[16px] <?php echo $i <= round($product['average_rating'] ?? 0) ? 'fill-1' : ''; ?>">star</span>
                                             <?php endfor; ?>
                                         </div>
                                         <span class="text-[9px] md:text-[10px] font-bold text-[#888888] -ml-2">(<?php echo $product['review_count'] ?? 0; ?>)</span>
                                     </div>
                                     <?php endif; ?>

                                    <!-- Price & Cart Row -->
                                    <div class="flex items-center justify-between mt-auto pt-2">
                                        <div class="flex flex-col min-w-0">
                                            <?php 
                                            $original_price = $product['original_price'] ?? 0;
                                            $discount_percentage = 0;
                                            if ($original_price > $product['price']) {
                                                $discount_percentage = round((($original_price - $product['price']) / $original_price) * 100);
                                            }
                                            ?>
                                            <div class="flex items-center gap-1.5 mb-0.5 flex-wrap">
                                                <?php if ($original_price > $product['price']): ?>
                                                    <span class="text-[11px] text-[#888888] line-through font-bold truncate"><?php echo formatCurrency($original_price); ?></span>
                                                    <?php if ($discount_percentage > 0): ?>
                                                        <span class="text-[9px] font-black text-primary bg-primary/10 px-1.5 py-0.5 rounded">-<?php echo $discount_percentage; ?>%</span>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                            <span class="text-[16px] md:text-[18px] font-black text-[#1A1A1A] tracking-tighter truncate"><?php echo formatCurrency($product['price']); ?></span>
                                        </div>
                                        
                                        <button class="flex-shrink-0 w-8 h-12 md:w-10 md:h-14 rounded-full bg-[#004225] text-white flex flex-col items-center justify-center shadow-lg hover:scale-105 active:scale-95 transition-all add-to-cart-btn gap-1"
                                                data-product-id="<?php echo $product['product_id']; ?>"
                                                data-product-name="<?php echo htmlspecialchars($product['name']); ?>">
                                            <span class="material-symbols-outlined text-[18px] md:text-[22px]">shopping_cart</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Re-initialize add-to-cart for wishlist page
    document.querySelectorAll('.add-to-cart-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const productId = this.getAttribute('data-product-id');
            const originalContent = this.innerHTML;
            
            this.innerHTML = '<span class="material-symbols-outlined animate-spin text-[18px]">sync</span>';
            this.disabled = true;

            fetch('../ajax/add_to_cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `product_id=${productId}&quantity=1`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (window.refreshCartCounter) {
                        window.refreshCartCounter(data.cart_count);
                    }
                    this.innerHTML = '<span class="material-symbols-outlined text-[20px]">check</span>';
                    this.classList.add('bg-green-600');
                    if (typeof showToast === 'function') {
                        showToast('Added to cart', 'success');
                    }
                    setTimeout(() => {
                        this.innerHTML = originalContent;
                        this.classList.remove('bg-green-600');
                        this.disabled = false;
                    }, 2000);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                this.innerHTML = originalContent;
                this.disabled = false;
            });
        });
    });

    // Make wishlist removal instantly remove the card from DOM
    document.querySelectorAll('.wishlist-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            // Give the toggle_wishlist.php ajax call time to fire (which is bound globally in script.js)
            // Then instantly remove the card from the UI
            setTimeout(() => {
                const card = this.closest('.group');
                if (card) {
                    card.style.opacity = '0';
                    card.style.transform = 'scale(0.95)';
                    setTimeout(() => {
                        card.remove();
                        // Check if grid is empty
                        const grid = document.querySelector('.grid-cols-2');
                        if (grid && grid.children.length === 0) {
                            location.reload(); // Reload to show empty state
                        }
                    }, 300);
                }
            }, 100);
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>
