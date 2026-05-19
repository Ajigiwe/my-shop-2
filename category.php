<?php
/**
 * Storefront: Category Listing
 * - Lists products for a single category with search, price range filtering, sorting, and pagination
 */
// Include database connection
require_once 'includes/db.php';

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$category_id = (int)($_GET['id'] ?? 0);
if ($category_id <= 0) {
    header('Location: shop.php');
    exit();
}

// Fetch category
try {
    $stmt = $pdo->prepare('SELECT * FROM categories WHERE category_id = ?');
    $stmt->execute([$category_id]);
    $category = $stmt->fetch();
    if (!$category) {
        header('Location: shop.php');
        exit();
    }
} catch (PDOException $e) {
    error_log('Category fetch error: ' . $e->getMessage());
    header('Location: shop.php');
    exit();
}

// Set page title
$page_title = $category['category_name'];

// Filtering and pagination
$search = sanitizeInput($_GET['search'] ?? '');
$min_price = (float)($_GET['min_price'] ?? 0);
$max_price = (float)($_GET['max_price'] ?? 0);
$sort_by = sanitizeInput($_GET['sort'] ?? 'newest');
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Build WHERE clause safely using parameter binding
$where = ['p.category_id = ?'];
$params = [$category_id];

if ($search) {
    $where[] = '(p.name LIKE ? OR p.description LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($min_price > 0) {
    $where[] = 'p.price >= ?';
    $params[] = $min_price;
}
if ($max_price > 0 && $max_price >= $min_price) {
    $where[] = 'p.price <= ?';
    $params[] = $max_price;
}
$where_sql = 'WHERE ' . implode(' AND ', $where);

// Supported sort options
$sort_map = [
    'newest' => 'p.created_at DESC',
    'price_low' => 'p.price ASC',
    'price_high' => 'p.price DESC',
    'name' => 'p.name ASC',
];
$order_sql = $sort_map[$sort_by] ?? $sort_map['newest'];

// Total count (for pagination controls)
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM products p $where_sql");
    $stmt->execute($params);
    $total_products = (int)$stmt->fetch()['total'];
    $total_pages = (int)ceil($total_products / $per_page);
} catch (PDOException $e) {
    error_log('Count products error: ' . $e->getMessage());
    $total_products = 0;
    $total_pages = 0;
}

// Fetch products
$products = [];
if ($total_products > 0) {
    try {
        $stmt = $pdo->prepare("SELECT p.* FROM products p $where_sql ORDER BY $order_sql LIMIT $per_page OFFSET $offset");
        $stmt->execute($params);
        $products = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('Fetch products error: ' . $e->getMessage());
    }
}

include 'includes/header.php';
?>

<main class="bg-surface-container-low min-h-screen">
    <!-- Category Header -->
    <section class="bg-surface-container-lowest border-b border-outline-variant py-lg">
        <div class="max-w-container-max mx-auto px-md flex flex-col md:flex-row md:items-center justify-between gap-md">
            <div>
                <h1 class="font-headline-lg text-headline-lg text-on-background mb-xs"><?php echo htmlspecialchars($category['category_name']); ?></h1>
                <?php if (!empty($category['description'])): ?>
                    <p class="font-body-md text-on-surface-variant"><?php echo htmlspecialchars($category['description']); ?></p>
                <?php endif; ?>
            </div>
            <div class="bg-primary/10 text-[#1A1A1A] font-label-lg px-md py-sm rounded-xl flex items-center gap-xs">
                <span class="material-symbols-outlined text-[20px]">inventory_2</span>
                <?php echo $total_products; ?> Products
            </div>
        </div>
    </section>

    <!-- Content -->
    <div class="max-w-container-max mx-auto px-md py-xl flex flex-col lg:flex-row gap-lg">
        <!-- Sidebar Filters -->
        <aside class="w-full lg:w-72 flex-shrink-0">
            <!-- Mobile Toggle -->
            <button id="toggleFilters" class="lg:hidden w-full flex items-center justify-between bg-surface-container-lowest border border-outline-variant px-md py-sm rounded-xl mb-md font-label-lg text-on-surface">
                <div class="flex items-center gap-xs">
                    <span class="material-symbols-outlined text-[#1A1A1A] text-[20px]">filter_list</span>
                    Filter Products
                </div>
                <span id="filterChevron" class="material-symbols-outlined transition-transform">expand_more</span>
            </button>

            <div id="filterSidebar" class="hidden lg:block bg-surface-container-lowest rounded-xl border border-outline-variant p-md lg:sticky lg:top-28">
                <h3 class="hidden lg:flex font-label-lg text-label-lg text-on-surface mb-md items-center gap-xs">
                    <span class="material-symbols-outlined text-[#1A1A1A] text-[20px]">filter_list</span>
                    Filter Products
                </h3>
                
                <form method="GET" action="category.php" class="flex flex-col gap-md">
                    <input type="hidden" name="id" value="<?php echo $category_id; ?>">
                    
                    <!-- Search Field -->
                    <div class="flex flex-col gap-xs">
                        <label class="font-label-sm text-on-surface-variant">Search</label>
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-sm top-1/2 -translate-y-1/2 text-on-surface-variant text-[18px]">search</span>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search in category..." 
                                   class="w-full pl-xl pr-md py-sm bg-surface-container-low border border-outline-variant rounded-lg focus:ring-2 focus:ring-primary/20 outline-none font-body-sm transition-all" />
                        </div>
                    </div>

                    <!-- Price Range -->
                    <div class="flex flex-col gap-xs">
                        <label class="font-label-sm text-on-surface-variant">Price Range</label>
                        <div class="grid grid-cols-2 gap-sm">
                            <input type="number" name="min_price" value="<?php echo $min_price ?: ''; ?>" placeholder="Min" min="0" step="0.01"
                                   class="w-full px-sm py-sm bg-surface-container-low border border-outline-variant rounded-lg focus:ring-2 focus:ring-primary/20 outline-none font-body-sm transition-all" />
                            <input type="number" name="max_price" value="<?php echo $max_price ?: ''; ?>" placeholder="Max" min="0" step="0.01"
                                   class="w-full px-sm py-sm bg-surface-container-low border border-outline-variant rounded-lg focus:ring-2 focus:ring-primary/20 outline-none font-body-sm transition-all" />
                        </div>
                    </div>

                    <!-- Sorting -->
                    <div class="flex flex-col gap-xs">
                        <label class="font-label-sm text-on-surface-variant">Sort By</label>
                        <select name="sort" class="w-full px-sm py-sm bg-surface-container-low border border-outline-variant rounded-lg focus:ring-2 focus:ring-primary/20 outline-none font-body-sm transition-all cursor-pointer">
                            <option value="newest" <?php echo $sort_by==='newest'?'selected':''; ?>>Newest First</option>
                            <option value="name" <?php echo $sort_by==='name'?'selected':''; ?>>Name (A-Z)</option>
                            <option value="price_low" <?php echo $sort_by==='price_low'?'selected':''; ?>>Price: Low to High</option>
                            <option value="price_high" <?php echo $sort_by==='price_high'?'selected':''; ?>>Price: High to Low</option>
                        </select>
                    </div>

                    <div class="flex flex-col gap-sm mt-sm">
                        <button type="submit" class="w-full bg-primary text-on-primary font-label-md py-sm rounded-lg hover:shadow-md transition-all active:scale-95">
                            Apply Filters
                        </button>
                        <a href="category.php?id=<?php echo $category_id; ?>" class="w-full text-center py-sm border border-outline-variant rounded-lg text-on-surface-variant font-label-md hover:bg-surface-container-high transition-colors">
                            Clear All
                        </a>
                    </div>
                </form>
            </div>
        </aside>

        <!-- Product Listing -->
        <div class="flex-1">
            <?php if (empty($products)): ?>
                <div class="bg-surface-container-lowest rounded-xl p-xl text-center border border-outline-variant">
                    <span class="material-symbols-outlined text-[48px] text-on-surface-variant mb-md">search_off</span>
                    <h3 class="font-headline-md mb-xs text-on-background">No products found</h3>
                    <p class="text-on-surface-variant mb-md">Try adjusting your filters or search terms.</p>
                    <a href="category.php?id=<?php echo $category_id; ?>" class="text-[#1A1A1A] font-label-lg hover:underline flex items-center justify-center gap-xs">
                        <span class="material-symbols-outlined text-[18px]">refresh</span> Reset Category View
                    </a>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 md:gap-6">
                    <?php foreach ($products as $product): 
                        // Fetch all images for this product
                        $img_stmt = $pdo->prepare("SELECT image_path FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, image_id ASC");
                        $img_stmt->execute([$product['product_id']]);
                        $images = $img_stmt->fetchAll(PDO::FETCH_COLUMN);
                        
                        $main_img = !empty($images[0]) ? $images[0] : ($product['image'] ?? 'placeholder.jpg');
                        $hover_img = !empty($images[1]) ? $images[1] : null;
                        
                        // Wishlist status
                        $user_wishlist = [];
                        if (isset($_SESSION['user_id'])) {
                            $w_stmt = $pdo->prepare("SELECT product_id FROM wishlist WHERE user_id = ?");
                            $w_stmt->execute([$_SESSION['user_id']]);
                            $user_wishlist = $w_stmt->fetchAll(PDO::FETCH_COLUMN);
                        }
                        $in_wishlist = in_array($product['product_id'], $user_wishlist);
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
                                                <span class="text-[11px] text-[#888888] line-through font-bold truncate"><?php echo formatCurrency($original_price); ?></span>
                                                <?php if ($discount_percentage > 0): ?>
                                                    <span class="text-[9px] font-black text-primary bg-primary/10 px-1.5 py-0.5 rounded">-<?php echo $discount_percentage; ?>%</span>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                        <span class="text-[16px] md:text-[18px] font-black text-[#1A1A1A] tracking-tighter truncate"><?php echo formatCurrency($product['price']); ?></span>
                                    </div>
                                    
                                    <button class="flex-shrink-0 flex items-center justify-center bg-[#004225] text-white rounded-xl shadow-md hover:scale-105 active:scale-95 transition-all add-to-cart-btn font-black text-[11px] uppercase tracking-wider gap-1.5 p-2.5 sm:w-10 sm:h-10 sm:p-0"
                                            data-product-id="<?php echo $product['product_id']; ?>"
                                            data-product-name="<?php echo htmlspecialchars($product['name']); ?>">
                                        <span class="material-symbols-outlined text-[16px] md:text-[18px]">shopping_cart</span>
                                        <span class="inline sm:hidden">Add to Cart</span>
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
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" class="w-10 h-10 flex items-center justify-center rounded-lg border border-outline-variant bg-surface-container-lowest hover:bg-surface-container-high transition-colors text-on-surface-variant">
                                <span class="material-symbols-outlined">chevron_left</span>
                            </a>
                        <?php endif; ?>

                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                               class="w-10 h-10 flex items-center justify-center rounded-lg border <?php echo $i === $page ? 'bg-primary text-on-primary border-primary' : 'border-outline-variant bg-surface-container-lowest hover:bg-surface-container-high'; ?> transition-all font-label-lg">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" class="w-10 h-10 flex items-center justify-center rounded-lg border border-outline-variant bg-surface-container-lowest hover:bg-surface-container-high transition-colors text-on-surface-variant">
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

    // Add to cart functionality (mirrors index/shop logic)
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

            fetch('ajax/add_to_cart.php', {
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
                    setTimeout(() => {
                        this.innerHTML = originalContent;
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
