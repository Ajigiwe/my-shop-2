<?php
/**
 * Storefront: Shop Listing
 */
require_once 'includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get category filter
$category_name = sanitizeInput($_GET['category'] ?? '');

// Set page title
$page_title = $category_name ? htmlspecialchars($category_name) : 'All Products';

// Get pagination parameters
$page = (int)($_GET['page'] ?? 1);
$per_page = 12; // Standardizing to 12
$offset = ($page - 1) * $per_page;

// Build WHERE clause
$where_conditions = ['p.stock_quantity > 0'];
$params = [];
if ($category_name) {
    $where_conditions[] = 'c.category_name = ?';
    $params[] = $category_name;
}
$where_sql = 'WHERE ' . implode(' AND ', $where_conditions);

// Get total count
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM products p JOIN categories c ON p.category_id = c.category_id $where_sql");
    $stmt->execute($params);
    $total_products = $stmt->fetch()['total'];
    $total_pages = ceil($total_products / $per_page);
} catch(PDOException $e) {
    $total_products = 0;
    $total_pages = 1;
}

// Get settings
$settings = [];
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {}

// Fetch user's wishlist ids
$user_wishlist = [];
if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT product_id FROM wishlist WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user_wishlist = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch(PDOException $e) {}
}

$grid_cols = isset($settings['products_per_row']) ? (int)$settings['products_per_row'] : 4;

// Get products
$products = [];
try {
    $stmt = $pdo->prepare("SELECT p.*, c.category_name, 
                            (SELECT GROUP_CONCAT(image_path ORDER BY is_primary DESC, image_id ASC) 
                             FROM product_images 
                             WHERE product_id = p.product_id) as all_images 
                            FROM products p 
                            JOIN categories c ON p.category_id = c.category_id 
                            $where_sql 
                            ORDER BY p.created_at DESC LIMIT $per_page OFFSET $offset");
    $stmt->execute($params);
    $products = $stmt->fetchAll();
} catch(PDOException $e) {
    error_log("Error fetching products: " . $e->getMessage());
}

// Get categories for sidebar
$categories_sidebar = [];
try {
    $stmt = $pdo->query("SELECT c.*, COUNT(p.product_id) as product_count FROM categories c LEFT JOIN products p ON c.category_id = p.category_id GROUP BY c.category_id ORDER BY c.category_name");
    $categories_sidebar = $stmt->fetchAll();
} catch(PDOException $e) {
    error_log("Error fetching categories: " . $e->getMessage());
}

include 'includes/header.php';
?>

<main class="bg-surface-container-low min-h-screen">
    <!-- Shop Header -->
    <section class="bg-surface-container-lowest border-b border-outline-variant py-lg">
        <div class="max-w-container-max mx-auto px-md">
            <h1 class="font-headline-lg text-headline-lg text-on-background mb-xs"><?php echo $page_title; ?></h1>
            <p class="font-body-md text-on-surface-variant">Discover our curated collection of <?php echo $category_name ? strtolower($page_title) : 'premium products'; ?>.</p>
        </div>
    </section>

    <!-- Content -->
    <div class="max-w-container-max mx-auto px-md py-xl flex flex-col lg:flex-row gap-lg">
        <!-- Sidebar Filters -->
        <aside class="w-full lg:w-72 flex-shrink-0">
            <!-- Mobile Toggle -->
            <button id="toggleFilters" class="lg:hidden w-full flex items-center justify-between bg-surface-container-lowest border border-outline-variant px-md py-sm rounded-xl mb-md font-label-lg text-on-surface">
                <div class="flex items-center gap-xs">
                    <span class="material-symbols-outlined text-[#1A1A1A] text-[20px]">category</span>
                    Browse Categories
                </div>
                <span id="filterChevron" class="material-symbols-outlined transition-transform">expand_more</span>
            </button>

            <div id="filterSidebar" class="hidden lg:block bg-surface-container-lowest rounded-xl border border-outline-variant p-md lg:sticky lg:top-28">
                <h3 class="hidden lg:flex font-label-lg text-label-lg text-on-surface mb-md items-center gap-xs">
                    <span class="material-symbols-outlined text-[#1A1A1A] text-[20px]">category</span>
                    Browse Categories
                </h3>
                <div class="flex flex-col gap-xs">
                    <a href="shop.php" class="flex items-center justify-between px-sm py-sm rounded-lg transition-all <?php echo !$category_name ? 'bg-primary text-white font-bold shadow-sm' : 'text-[#888888] hover:bg-[#F5F5F5] font-medium'; ?>">
                        <span>All Products</span>
                    </a>
                    <?php foreach ($categories_sidebar as $cat): ?>
                        <a href="shop.php?category=<?php echo urlencode($cat['category_name']); ?>" class="flex items-center justify-between px-sm py-sm rounded-lg transition-all <?php echo $category_name === $cat['category_name'] ? 'bg-primary text-white font-bold shadow-sm' : 'text-[#888888] hover:bg-[#F5F5F5] font-medium'; ?>">
                            <span><?php echo htmlspecialchars($cat['category_name']); ?></span>
                            <span class="text-[10px] <?php echo $category_name === $cat['category_name'] ? 'bg-white/20 text-white' : 'bg-[#F5F5F5] text-[#888888]'; ?> px-1.5 py-0.5 rounded-full"><?php echo $cat['product_count']; ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>

                <div class="mt-lg pt-lg border-t border-outline-variant">
                    <h3 class="font-label-lg text-label-lg text-on-surface mb-md flex items-center gap-xs">
                        <span class="material-symbols-outlined text-[#1A1A1A] text-[20px]">payments</span>
                        Price Range
                    </h3>
                    <div class="flex flex-col gap-sm">
                        <input type="range" class="w-full accent-primary cursor-pointer" min="0" max="10000" step="100" />
                        <div class="flex justify-between font-label-sm text-on-surface-variant">
                            <span>GH₵0</span>
                            <span>GH₵10,000+</span>
                        </div>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Product Listing -->
        <div class="flex-1">
            <!-- Toolbar -->
            <div class="flex items-center justify-between mb-lg bg-surface-container-lowest p-sm rounded-xl border border-outline-variant">
                <p class="font-body-sm text-on-surface-variant ml-sm">Showing <span class="font-bold text-on-surface"><?php echo count($products); ?></span> of <?php echo $total_products; ?> results</p>
                <div class="flex items-center gap-sm">
                    <span class="material-symbols-outlined text-on-surface-variant text-[20px]">sort</span>
                    <select class="bg-transparent border-none focus:ring-0 font-label-sm text-on-surface-variant cursor-pointer py-1">
                        <option>Newest Arrivals</option>
                        <option>Price: Low to High</option>
                        <option>Price: High to Low</option>
                    </select>
                </div>
            </div>

            <!-- Grid -->
            <?php if (empty($products)): ?>
                <div class="bg-surface-container-lowest rounded-xl p-xl text-center border border-outline-variant">
                    <span class="material-symbols-outlined text-[48px] text-on-surface-variant mb-md">search_off</span>
                    <h3 class="font-headline-md mb-xs text-on-background">No products found</h3>
                    <p class="text-on-surface-variant mb-md">Try selecting a different category or clearing filters.</p>
                    <a href="shop.php" class="text-[#1A1A1A] font-label-lg hover:underline flex items-center justify-center gap-xs">
                        <span class="material-symbols-outlined text-[18px]">refresh</span> Reset Shop View
                    </a>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-<?php echo $grid_cols; ?> gap-3 sm:gap-4 lg:gap-gutter">
                    <?php foreach ($products as $product): 
                        $images = !empty($product['all_images']) ? explode(',', $product['all_images']) : [];
                        $main_img = !empty($images[0]) ? $images[0] : ($product['image'] ?? 'placeholder.jpg');
                        $hover_img = !empty($images[1]) ? $images[1] : null;
                    ?>
                        <div class="bg-white rounded-[1.5rem] p-3 border border-[#EEEEEE] shadow-sm hover:shadow-xl transition-all group relative flex flex-col justify-between h-full">
                            <!-- Image Section -->
                            <div class="relative aspect-square rounded-[1rem] overflow-hidden bg-[#F9F9F9] mb-4">
                                <a href="product.php?id=<?php echo $product['product_id']; ?>" class="block w-full h-full relative">
                                    <img class="w-full h-full object-contain p-4 transition-all duration-700 <?php echo $hover_img ? 'group-hover:opacity-0' : 'group-hover:scale-110'; ?>" 
                                         src="assets/images/<?php echo htmlspecialchars($main_img); ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>" />
                                    <?php if ($hover_img): ?>
                                        <img class="absolute inset-0 w-full h-full object-contain p-4 opacity-0 group-hover:opacity-100 scale-110 group-hover:scale-100 transition-all duration-700" 
                                             src="assets/images/<?php echo htmlspecialchars($hover_img); ?>" 
                                             alt="<?php echo htmlspecialchars($product['name']); ?>" />
                                    <?php endif; ?>
                                </a>
                                
                                <!-- Floating Actions -->
                                <div class="absolute left-2 top-2 flex flex-col gap-2 translate-x-0 lg:translate-x-[-3rem] lg:group-hover:translate-x-0 transition-transform duration-500 z-20">
                                    <?php 
                                    $in_wishlist = in_array($product['product_id'], $user_wishlist);
                                    ?>
                                    <button class="w-8 h-8 rounded-full bg-white shadow-md flex items-center justify-center <?php echo $in_wishlist ? 'text-red-500' : 'text-[#1A1A1A]'; ?> hover:bg-red-500 hover:text-white transition-colors wishlist-btn"
                                             data-product-id="<?php echo $product['product_id']; ?>">
                                        <span class="material-symbols-outlined text-[18px] <?php echo $in_wishlist ? 'fill-1' : ''; ?>">favorite</span>
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
                                    <a href="product.php?id=<?php echo $product['product_id']; ?>" class="hover:text-primary transition-colors">
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
                                                <span class="text-[10px] sm:text-[11px] text-[#888888] line-through font-bold whitespace-nowrap"><?php echo formatCurrency($original_price); ?></span>
                                                <?php if ($discount_percentage > 0): ?>
                                                    <span class="text-[9px] font-black text-primary bg-primary/10 px-1.5 py-0.5 rounded">-<?php echo $discount_percentage; ?>%</span>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                        <span class="text-[13px] sm:text-[15px] md:text-[18px] font-black text-[#1A1A1A] tracking-tighter"><?php echo formatCurrency($product['price']); ?></span>
                                    </div>
                                    
                                    <button class="flex-shrink-0 w-9 h-9 rounded-xl bg-[#004225] text-white flex items-center justify-center hover:scale-105 active:scale-95 transition-all add-to-cart-btn shadow-md"
                                            data-product-id="<?php echo $product['product_id']; ?>"
                                            data-product-name="<?php echo htmlspecialchars($product['name']); ?>">
                                        <span class="material-symbols-outlined text-[18px]">shopping_cart</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="flex justify-center mt-xl gap-sm">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?><?php echo $category_name ? '&category=' . urlencode($category_name) : ''; ?>" class="w-10 h-10 flex items-center justify-center rounded-lg border border-outline-variant bg-surface-container-lowest hover:bg-surface-container-high transition-colors text-on-surface-variant">
                                <span class="material-symbols-outlined">chevron_left</span>
                            </a>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?page=<?php echo $i; ?><?php echo $category_name ? '&category=' . urlencode($category_name) : ''; ?>" 
                               class="w-10 h-10 flex items-center justify-center rounded-lg border <?php echo $i === $page ? 'bg-primary text-on-primary border-primary' : 'border-outline-variant bg-surface-container-lowest hover:bg-surface-container-high'; ?> transition-all font-label-lg">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?><?php echo $category_name ? '&category=' . urlencode($category_name) : ''; ?>" class="w-10 h-10 flex items-center justify-center rounded-lg border border-outline-variant bg-surface-container-lowest hover:bg-surface-container-high transition-colors text-on-surface-variant">
                                <span class="material-symbols-outlined">chevron_right</span>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mobile Filter Toggle
    const toggleFilters = document.getElementById('toggleFilters');
    const filterSidebar = document.getElementById('filterSidebar');
    const filterChevron = document.getElementById('filterChevron');

    if (toggleFilters && filterSidebar) {
        toggleFilters.addEventListener('click', () => {
            filterSidebar.classList.toggle('hidden');
            filterChevron.classList.toggle('rotate-180');
        });
    }

    // Shared Add to Cart Logic
    document.querySelectorAll('.add-to-cart-btn').forEach(button => {
        button.addEventListener('click', function() {
            // Show loading state
            const originalContent = this.innerHTML;
            const isMobile = window.innerWidth < 640;
            if (isMobile) {
                this.innerHTML = '<span class="material-symbols-outlined animate-spin text-[18px]">sync</span> Adding...';
            } else {
                this.innerHTML = '<span class="material-symbols-outlined animate-spin text-[18px]">sync</span>';
            }
            this.disabled = true;

            fetch(window.SHOP_URL + 'ajax/add_to_cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `product_id=${productId}&quantity=1`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (window.refreshCartCounter) {
                        window.refreshCartCounter(data.cart_count);
                    }
                    if (isMobile) {
                        this.innerHTML = '<span class="material-symbols-outlined text-[18px]">check</span> Added';
                    } else {
                        this.innerHTML = '<span class="material-symbols-outlined text-[18px]">check</span>';
                    }
                    this.classList.add('bg-green-600');
                    setTimeout(() => {
                        this.innerHTML = originalContent;
                        this.classList.remove('bg-green-600');
                        this.disabled = false;
                    }, 2000);
                } else {
                    alert(data.message || 'Error adding to cart');
                    this.innerHTML = originalContent;
                    this.disabled = false;
                }
            })
            .catch(err => {
                console.error(err);
                this.innerHTML = originalContent;
                this.disabled = false;
            });
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
