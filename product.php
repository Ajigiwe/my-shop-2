<?php
/**
 * Storefront: Product Details
 */
require_once 'includes/db.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$product_id = (int)($_GET['id'] ?? 0);
if ($product_id <= 0) {
    header('Location: shop.php');
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT p.*, c.category_name FROM products p JOIN categories c ON p.category_id = c.category_id WHERE p.product_id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    if (!$product) {
        header('Location: shop.php');
        exit();
    }
} catch(PDOException $e) {
    header('Location: shop.php');
    exit();
}

// Fetch reviews
$reviews = [];
$avg_rating = 0;
$total_reviews = 0;
try {
    $stmt = $pdo->prepare("SELECT r.*, u.name FROM reviews r JOIN users u ON r.user_id = u.user_id WHERE r.product_id = ? ORDER BY r.created_at DESC");
    $stmt->execute([$product_id]);
    $reviews = $stmt->fetchAll();
    
    if (count($reviews) > 0) {
        $total_reviews = count($reviews);
        $sum_rating = array_sum(array_column($reviews, 'rating'));
        $avg_rating = round($sum_rating / $total_reviews, 1);
    }
} catch(PDOException $e) {
    error_log("Error fetching reviews: " . $e->getMessage());
}

$related_products = [];
try {
    $stmt = $pdo->prepare("SELECT p.*, c.category_name FROM products p JOIN categories c ON p.category_id = c.category_id WHERE p.category_id = ? AND p.product_id != ? ORDER BY RAND() LIMIT 4");
    $stmt->execute([$product['category_id'], $product_id]);
    $related_products = $stmt->fetchAll();
} catch(PDOException $e) {
    error_log("Error fetching related products: " . $e->getMessage());
}

$page_title = $product['name'];
include 'includes/header.php';
?>

<main class="bg-[#F9F9F9] min-h-screen py-md md:py-xl">
    <!-- Breadcrumbs -->
    <div class="max-w-[1200px] mx-auto px-6 mb-8">
        <nav class="flex items-center gap-2 text-[13px] font-bold text-[#888888]">
            <a href="index.php" class="hover:text-[#1A1A1A] transition-colors">Home</a>
            <span class="material-symbols-outlined text-[16px]">chevron_right</span>
            <a href="shop.php" class="hover:text-[#1A1A1A] transition-colors">Shop</a>
            <span class="material-symbols-outlined text-[16px]">chevron_right</span>
            <a href="shop.php?category=<?php echo urlencode($product['category_name']); ?>" class="hover:text-[#1A1A1A] transition-colors"><?php echo htmlspecialchars($product['category_name']); ?></a>
            <span class="material-symbols-outlined text-[16px]">chevron_right</span>
            <span class="text-[#1A1A1A] truncate max-w-[150px] md:max-w-none"><?php echo htmlspecialchars($product['name']); ?></span>
        </nav>
    </div>

<?php
// Fetch all product images
$product_images = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, image_id ASC");
    $stmt->execute([$product_id]);
    $product_images = $stmt->fetchAll();
} catch(PDOException $e) {
    error_log("Error fetching product images: " . $e->getMessage());
}
$main_image = !empty($product_images) ? $product_images[0]['image_path'] : ($product['image'] ?? 'placeholder.jpg');
?>

    <!-- Main Product Section -->
    <section class="max-w-[1200px] mx-auto px-6">
        <!-- Top Row: Gallery & Purchase Info -->
        <div class="flex flex-col lg:flex-row gap-8 items-start">
            <!-- Gallery (Left) -->
            <div class="w-full lg:w-[55%] space-y-4">
                <div class="bg-white rounded-[2rem] p-6 md:p-8 border border-[#EEEEEE] shadow-sm aspect-square overflow-hidden flex items-center justify-center group cursor-zoom-in" onclick="openLightbox()">
                    <img id="mainProductImage" class="max-w-full max-h-full object-contain group-hover:scale-105 transition-transform duration-700" 
                         src="assets/images/<?php echo htmlspecialchars($main_image); ?>" 
                         alt="<?php echo htmlspecialchars($product['name']); ?>" />
                </div>
                
                <?php if (count($product_images) > 1): ?>
                    <div class="flex flex-wrap gap-3 mt-4">
                        <?php foreach ($product_images as $index => $img): ?>
                            <button onclick="changeImage('assets/images/<?php echo htmlspecialchars($img['image_path']); ?>', this, <?php echo $index; ?>)" 
                                    class="w-20 h-20 rounded-2xl border-2 overflow-hidden transition-all p-1.5 <?php echo $index === 0 ? 'border-primary' : 'border-transparent bg-white shadow-sm'; ?>"
                                    data-index="<?php echo $index; ?>">
                                <img src="assets/images/<?php echo htmlspecialchars($img['image_path']); ?>" class="w-full h-full object-cover rounded-xl" alt="">
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        <!-- Lightbox Modal -->
        <div id="imageLightbox" class="fixed inset-0 z-[100] hidden bg-black/95 backdrop-blur-sm flex flex-col items-center justify-center p-4 md:p-12 opacity-0 transition-opacity duration-300">
            <button onclick="closeLightbox()" class="absolute top-6 right-6 text-white/70 hover:text-white transition-colors z-[110]">
                <span class="material-symbols-outlined text-[32px]">close</span>
            </button>
            
            <div class="relative w-full h-full flex items-center justify-center">
                <!-- Navigation Arrows -->
                <?php if (count($product_images) > 1): ?>
                    <button onclick="prevImage(event)" class="absolute left-0 md:-left-8 text-white/50 hover:text-white transition-colors p-4 z-[110]">
                        <span class="material-symbols-outlined text-[48px] md:text-[64px]">chevron_left</span>
                    </button>
                <?php endif; ?>
                
                <img id="lightboxImage" src="" class="max-w-full max-h-full object-contain transition-all duration-500 scale-95" alt="">
                
                <?php if (count($product_images) > 1): ?>
                    <button onclick="nextImage(event)" class="absolute right-0 md:-right-8 text-white/50 hover:text-white transition-colors p-4 z-[110]">
                        <span class="material-symbols-outlined text-[48px] md:text-[64px]">chevron_right</span>
                    </button>
                <?php endif; ?>
            </div>
            
            <div id="lightboxCounter" class="mt-6 text-white/70 font-bold tracking-widest text-[13px] uppercase"></div>
        </div>

        <script>
            let currentImageIndex = 0;
            const productImages = <?php echo json_encode(array_map(function($img) { return 'assets/images/' . $img['image_path']; }, $product_images)); ?>;

            function changeImage(src, btn, index) {
                currentImageIndex = index;
                document.getElementById('mainProductImage').src = src;
                // Update active state of thumbnails
                const buttons = btn.parentElement.querySelectorAll('button');
                buttons.forEach(b => {
                    b.classList.remove('border-primary');
                    b.classList.add('border-transparent', 'bg-white', 'shadow-sm');
                });
                btn.classList.add('border-primary');
                btn.classList.remove('border-transparent', 'bg-white', 'shadow-sm');
            }

            function openLightbox() {
                const lightbox = document.getElementById('imageLightbox');
                const lightboxImg = document.getElementById('lightboxImage');
                
                lightboxImg.src = productImages[currentImageIndex];
                updateCounter();
                
                lightbox.classList.remove('hidden');
                setTimeout(() => {
                    lightbox.classList.add('opacity-100');
                    lightboxImg.classList.remove('scale-95');
                }, 10);
                document.body.style.overflow = 'hidden';
            }

            function closeLightbox() {
                const lightbox = document.getElementById('imageLightbox');
                const lightboxImg = document.getElementById('lightboxImage');
                
                lightbox.classList.remove('opacity-100');
                lightboxImg.classList.add('scale-95');
                
                setTimeout(() => {
                    lightbox.classList.add('hidden');
                    document.body.style.overflow = '';
                }, 300);
            }

            function nextImage(e) {
                if(e) e.stopPropagation();
                currentImageIndex = (currentImageIndex + 1) % productImages.length;
                updateLightboxImage();
            }

            function prevImage(e) {
                if(e) e.stopPropagation();
                currentImageIndex = (currentImageIndex - 1 + productImages.length) % productImages.length;
                updateLightboxImage();
            }

            function updateLightboxImage() {
                const lightboxImg = document.getElementById('lightboxImage');
                lightboxImg.style.opacity = '0';
                lightboxImg.style.transform = 'scale(0.95)';
                
                setTimeout(() => {
                    lightboxImg.src = productImages[currentImageIndex];
                    lightboxImg.style.opacity = '1';
                    lightboxImg.style.transform = 'scale(1)';
                    updateCounter();
                }, 200);
            }

            function updateCounter() {
                document.getElementById('lightboxCounter').textContent = `Image ${currentImageIndex + 1} of ${productImages.length}`;
            }

            // Keyboard navigation
            document.addEventListener('keydown', (e) => {
                const lightbox = document.getElementById('imageLightbox');
                if (lightbox.classList.contains('hidden')) return;
                
                if (e.key === 'Escape') closeLightbox();
                if (e.key === 'ArrowRight') nextImage();
                if (e.key === 'ArrowLeft') prevImage();
            });
        </script>

            <!-- Purchase Info (Right) -->
            <div class="w-full lg:w-[45%] lg:sticky lg:top-28">
                <div class="bg-white rounded-[2rem] p-6 md:p-8 border border-[#EEEEEE] shadow-sm">
                    <h1 class="text-[24px] md:text-[30px] font-black text-[#1A1A1A] leading-tight mb-4"><?php echo htmlspecialchars($product['name']); ?></h1>
                    
                    <div class="flex items-center flex-wrap gap-4 mb-6">
                        <div class="flex flex-col">
                            <?php 
                            $original_price = $product['original_price'] ?? 0;
                            $discount_percentage = 0;
                            if ($original_price > $product['price']) {
                                $discount_percentage = round((($original_price - $product['price']) / $original_price) * 100);
                            }
                            ?>
                            <?php if ($discount_percentage > 0): ?>
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-[14px] text-[#888888] line-through font-bold"><?php echo formatCurrency($original_price); ?></span>
                                    <span class="text-[12px] font-black text-primary bg-primary/10 px-2 py-0.5 rounded">-<?php echo $discount_percentage; ?>% OFF</span>
                                </div>
                            <?php endif; ?>
                            <p class="text-[28px] md:text-[32px] font-black text-[#1A1A1A] tracking-tighter leading-none"><?php echo formatCurrency($product['price']); ?></p>
                        </div>
                        <?php if ($product['stock_quantity'] > 0): ?>
                            <div class="flex items-center gap-1.5 text-[12px] font-bold text-[#22C55E] bg-[#F0FDF4] px-3 py-1 rounded-full border border-[#DCFCE7]">
                                <span class="material-symbols-outlined text-[16px]">check_circle</span> 
                                In Stock
                            </div>
                        <?php else: ?>
                            <div class="flex items-center gap-1.5 text-[12px] font-bold text-[#EF4444] bg-[#FEF2F2] px-3 py-1 rounded-full border border-[#FEE2E2]">
                                <span class="material-symbols-outlined text-[16px]">error</span> 
                                Out of Stock
                            </div>
                        <?php endif; ?>
                    </div>

                    <p class="text-[14px] text-[#666666] leading-relaxed mb-6">
                        <?php echo nl2br(htmlspecialchars($product['description'] ?: 'No description available for this product.')); ?>
                    </p>

                    <?php if ($product['stock_quantity'] > 0): ?>
                        <div class="space-y-4">
                            <div class="flex items-center gap-4">
                                <div class="flex-1 space-y-2">
                                    <label class="text-[12px] font-bold text-[#888888] uppercase tracking-widest ml-4">Quantity</label>
                                    <div class="flex items-center bg-[#F9F9F9] rounded-full border border-[#EEEEEE] p-1.5 px-2 gap-4 w-fit">
                                        <button onclick="decQty()" class="w-10 h-10 rounded-full flex items-center justify-center hover:bg-white hover:shadow-sm text-[#1A1A1A] transition-all">
                                            <span class="material-symbols-outlined text-[20px]">remove</span>
                                        </button>
                                        <input id="prodQty" type="number" value="1" min="1" max="<?php echo $product['stock_quantity']; ?>" class="w-10 bg-transparent text-center font-black text-[18px] text-[#1A1A1A] border-none focus:ring-0" />
                                        <button onclick="incQty()" class="w-10 h-10 rounded-full flex items-center justify-center hover:bg-white hover:shadow-sm text-[#1A1A1A] transition-all">
                                            <span class="material-symbols-outlined text-[20px]">add</span>
                                        </button>
                                    </div>
                                </div>
                                <div class="space-y-2">
                                    <label class="text-[12px] font-bold text-[#888888] uppercase tracking-widest ml-4">Save</label>
                                    <button class="w-14 h-14 rounded-full border-2 border-[#EEEEEE] flex items-center justify-center text-[#1A1A1A] hover:bg-primary hover:text-white hover:border-primary transition-all">
                                        <span class="material-symbols-outlined text-[24px]">favorite</span>
                                    </button>
                                </div>
                            </div>

                            <button id="mainAddToCart" class="w-full bg-primary text-white font-black text-[14px] py-4 rounded-2xl flex items-center justify-center gap-3 hover:bg-primary shadow-lg hover:shadow-primary/20 transition-all active:scale-[0.98]">
                                <span class="material-symbols-outlined text-[20px]">add_shopping_cart</span> Add to Cart
                            </button>
                        </div>
                    <?php endif; ?>

                    <div class="mt-10 pt-10 border-t border-[#F5F5F5] grid grid-cols-2 gap-6">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-[#F5F5F5] flex items-center justify-center text-[#1A1A1A]">
                                <span class="material-symbols-outlined text-[20px]">local_shipping</span>
                            </div>
                            <span class="text-[12px] font-bold text-[#1A1A1A]">Fast Delivery</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-[#F5F5F5] flex items-center justify-center text-[#1A1A1A]">
                                <span class="material-symbols-outlined text-[20px]">verified_user</span>
                            </div>
                            <span class="text-[12px] font-bold text-[#1A1A1A]">Secure Payment</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom Row: Features & Reviews -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-20 mt-20 pt-20 border-t border-[#EEEEEE]">
            <!-- Left Column: Key Features -->
            <div>
                <?php if (!empty($product['features'])): 
                    $features_list = array_filter(array_map('trim', explode("\n", $product['features'])));
                    $has_many_features = count($features_list) > 5;
                ?>
                    <div class="space-y-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-[20px] font-black text-[#1A1A1A]">Key Features</h3>
                                <p class="text-[13px] text-[#888888] font-medium mt-1">Exceptional product highlights.</p>
                            </div>
                        </div>
                        
                        <div class="relative">
                            <div id="featuresList" class="divide-y divide-[#F5F5F5] border-t border-[#F5F5F5] transition-all duration-500 ease-in-out overflow-hidden" 
                                 style="<?php echo $has_many_features ? 'max-height: 320px;' : ''; ?>">
                                <?php foreach ($features_list as $feature): ?>
                                    <div class="py-4 flex items-center gap-4 group">
                                        <div class="w-8 h-8 rounded-full bg-[#F0FDF4] flex items-center justify-center text-primary flex-shrink-0 group-hover:scale-110 transition-transform">
                                            <span class="material-symbols-outlined text-[18px]">check_circle</span>
                                        </div>
                                        <span class="text-[14px] text-[#444444] font-bold leading-relaxed"><?php echo htmlspecialchars($feature); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <?php if ($has_many_features): ?>
                                <div id="featuresFade" class="absolute bottom-0 left-0 right-0 h-24 bg-gradient-to-t from-[#F9F9F9] to-transparent pointer-events-none transition-opacity duration-300"></div>
                                <button onclick="toggleFeatures(this)" class="mt-4 flex items-center gap-2 text-[12px] font-black text-primary uppercase tracking-widest hover:underline relative z-10">
                                    <span class="btn-text">Show All Features</span>
                                    <span class="material-symbols-outlined text-[18px] transition-transform duration-300">expand_more</span>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Right Column: Reviews & Ratings -->
            <div>
                <div class="space-y-8">
                    <!-- Review Success/Error Messages -->
                    <?php if (isset($_GET['success'])): ?>
                        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-2xl text-[13px] font-bold flex items-center gap-2 mb-6 animate-down">
                            <span class="material-symbols-outlined text-[18px]">check_circle</span>
                            Review submitted successfully!
                        </div>
                    <?php endif; ?>
                    <?php if (isset($_GET['error'])): ?>
                        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-2xl text-[13px] font-bold flex items-center gap-2 mb-6 animate-down">
                            <span class="material-symbols-outlined text-[18px]">error</span>
                            <?php 
                                echo $_GET['error'] === 'invalid_input' ? 'Please select a rating and enter a comment.' : 'An error occurred. Please try again.';
                            ?>
                        </div>
                    <?php endif; ?>

                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-[20px] font-black text-[#1A1A1A]">Reviews</h3>
                            <div class="flex items-center gap-2 mt-1">
                                <div class="flex text-[#FFB800] scale-90 origin-left">
                                    <?php for($i=1; $i<=5; $i++): ?>
                                        <span class="material-symbols-outlined <?php echo $i <= $avg_rating ? 'filled' : ''; ?> text-[18px]">star</span>
                                    <?php endfor; ?>
                                </div>
                                <span class="text-[13px] text-[#1A1A1A] font-black"><?php echo $avg_rating; ?></span>
                                <span class="text-[13px] text-[#888888] font-medium">(<?php echo $total_reviews; ?>)</span>
                            </div>
                        </div>
                        <button onclick="toggleReviewForm()" class="text-[12px] font-black text-primary uppercase tracking-widest hover:underline">Write a Review</button>
                    </div>

                    <!-- Review Form -->
                    <div id="reviewForm" class="hidden animate-down bg-white rounded-3xl p-6 border border-[#EEEEEE] shadow-sm mb-8">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <form action="api/submit_review.php" method="POST" class="space-y-4">
                                <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                                <div>
                                    <label class="text-[11px] font-bold text-[#888888] uppercase tracking-widest block mb-2 ml-1">Your Rating</label>
                                    <div class="flex gap-1 text-[#FFB800]">
                                        <?php for($i=1; $i<=5; $i++): ?>
                                            <input type="radio" name="rating" value="<?php echo $i; ?>" id="r<?php echo $i; ?>" class="hidden" required>
                                            <label for="r<?php echo $i; ?>" class="material-symbols-outlined cursor-pointer hover:scale-110 transition-transform rating-star" data-rating="<?php echo $i; ?>">star</label>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <div>
                                    <label class="text-[11px] font-bold text-[#888888] uppercase tracking-widest block mb-2 ml-1">Comment</label>
                                    <textarea name="comment" rows="3" class="w-full bg-[#F9F9F9] border border-[#EEEEEE] rounded-2xl p-4 text-[14px] focus:ring-2 focus:ring-primary/20 transition-all outline-none" placeholder="Share your experience..." required></textarea>
                                </div>
                                <button type="submit" class="w-full bg-primary text-white font-black text-[14px] py-4 rounded-xl shadow-lg hover:shadow-primary/20 transition-all active:scale-[0.98]">Submit Review</button>
                            </form>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <p class="text-[13px] font-bold text-[#1A1A1A] mb-3">Please login to write a review</p>
                                <a href="login.php" class="inline-block bg-[#F5F5F5] text-[#1A1A1A] font-black text-[11px] px-6 py-2 rounded-full hover:bg-primary hover:text-white transition-all">Login Now</a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Compact Review List -->
                    <div class="divide-y divide-[#F5F5F5] border-t border-[#F5F5F5]">
                        <?php if (empty($reviews)): ?>
                            <div class="py-10 text-center">
                                <span class="material-symbols-outlined text-[32px] text-[#888888] mb-2 opacity-50">rate_review</span>
                                <p class="text-[13px] font-medium text-[#888888]">No reviews yet. Be the first to review!</p>
                            </div>
                        <?php else: ?>
                            <?php foreach (array_slice($reviews, 0, 3) as $rev): ?>
                                <div class="py-5 flex gap-4">
                                    <div class="w-10 h-10 rounded-full bg-[#F9F9F9] flex items-center justify-center text-primary font-black text-[12px] shrink-0 border border-[#EEEEEE]">
                                        <?php 
                                            $parts = explode(' ', $rev['name']);
                                            echo strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
                                        ?>
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex items-center justify-between mb-1">
                                            <span class="text-[14px] font-black text-[#1A1A1A]"><?php echo htmlspecialchars($rev['name']); ?></span>
                                            <div class="flex text-[#FFB800] scale-75 origin-right">
                                                <?php for($k=1; $k<=5; $k++): ?>
                                                    <span class="material-symbols-outlined <?php echo $k <= $rev['rating'] ? 'filled' : ''; ?>">star</span>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                        <p class="text-[13px] text-[#666666] leading-relaxed"><?php echo htmlspecialchars($rev['comment']); ?></p>
                                        <div class="mt-2 text-[11px] text-[#888888] font-bold uppercase tracking-wider flex items-center gap-1.5">
                                            <span class="material-symbols-outlined text-[14px] text-green-500">verified</span> 
                                            <span class="opacity-70"><?php echo date('M d, Y', strtotime($rev['created_at'])); ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <?php if ($total_reviews > 0): ?>
                        <button class="w-full py-4 rounded-2xl bg-[#F9F9F9] text-[12px] font-black text-[#1A1A1A] uppercase tracking-widest hover:bg-[#F0F0F0] transition-all border border-[#EEEEEE]">View All <?php echo $total_reviews; ?> Reviews</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <script>
        function toggleReviewForm() {
            const form = document.getElementById('reviewForm');
            form.classList.toggle('hidden');
        }

        function toggleFeatures(btn) {
            const list = document.getElementById('featuresList');
            const fade = document.getElementById('featuresFade');
            const text = btn.querySelector('.btn-text');
            const icon = btn.querySelector('.material-symbols-outlined');
            
            if (list.style.maxHeight !== 'none') {
                list.style.maxHeight = 'none';
                fade.style.opacity = '0';
                text.textContent = 'Show Less';
                icon.style.transform = 'rotate(180deg)';
            } else {
                list.style.maxHeight = '320px';
                fade.style.opacity = '1';
                text.textContent = 'Show All Features';
                icon.style.transform = 'rotate(0deg)';
                list.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        }

        // Handle star rating selection and hover
        const stars = document.querySelectorAll('.rating-star');
        stars.forEach(star => {
            star.addEventListener('mouseenter', function() {
                const rating = parseInt(this.dataset.rating);
                highlightStars(rating);
            });

            star.addEventListener('mouseleave', function() {
                const checkedRadio = document.querySelector('input[name="rating"]:checked');
                if (checkedRadio) {
                    highlightStars(parseInt(checkedRadio.value));
                } else {
                    highlightStars(0);
                }
            });

            star.addEventListener('click', function() {
                const rating = parseInt(this.dataset.rating);
                highlightStars(rating);
            });
        });

        function highlightStars(rating) {
            stars.forEach((star, index) => {
                if (index < rating) {
                    star.classList.add('filled');
                } else {
                    star.classList.remove('filled');
                }
            });
        }
    </script>

    <style>
        .material-symbols-outlined.filled {
            font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24 !important;
        }
        
        .animate-down {
            animation: slideDown 0.3s ease-out forwards;
        }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
    </section>

    <!-- Related Products -->
    <?php if (!empty($related_products)): ?>
    <section class="max-w-[1200px] mx-auto px-6 mt-20">
        <div class="flex items-center justify-between mb-8">
            <h2 class="text-[20px] md:text-[24px] font-black text-[#1A1A1A]">You Might Also Like</h2>
            <a href="shop.php?category=<?php echo urlencode($product['category_name']); ?>" class="text-[13px] font-bold text-[#888888] hover:text-[#1A1A1A] transition-colors flex items-center gap-1">View All <span class="material-symbols-outlined text-[16px]">chevron_right</span></a>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
            <?php foreach ($related_products as $rel): ?>
                <div class="bg-white rounded-2xl p-2 border border-[#EEEEEE] shadow-sm hover:shadow-md transition-all group">
                    <div class="relative aspect-square rounded-xl overflow-hidden bg-[#F9F9F9] mb-3">
                        <a href="product.php?id=<?php echo $rel['product_id']; ?>">
                            <img class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" 
                                 src="assets/images/<?php echo htmlspecialchars($rel['image'] ?? 'placeholder.jpg'); ?>" 
                                 alt="<?php echo htmlspecialchars($rel['name']); ?>" />
                        </a>
                    </div>
                    <div class="px-1 pb-2">
                        <h3 class="text-[13px] font-bold text-[#1A1A1A] mb-1 truncate"><?php echo htmlspecialchars($rel['name']); ?></h3>
                        <p class="text-[15px] font-black text-[#1A1A1A]"><?php echo formatCurrency($rel['price']); ?></p>
                        <a href="product.php?id=<?php echo $rel['product_id']; ?>" class="w-full mt-3 bg-[#F5F5F5] text-[#1A1A1A] font-bold text-[10px] py-2 rounded-full hover:bg-primary hover:text-white transition-colors flex items-center justify-center gap-1.5 uppercase tracking-tighter">
                            View Details
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
</main>
</main>

<script>
function incQty() {
    const i = document.getElementById('prodQty');
    const max = parseInt(i.getAttribute('max'));
    if (parseInt(i.value) < max) i.value = parseInt(i.value) + 1;
}
function decQty() {
    const i = document.getElementById('prodQty');
    if (parseInt(i.value) > 1) i.value = parseInt(i.value) - 1;
}

document.addEventListener('DOMContentLoaded', function() {
    const addBtn = document.getElementById('mainAddToCart');
    if (addBtn) {
        addBtn.addEventListener('click', function() {
            const qty = document.getElementById('prodQty').value;
            const originalContent = this.innerHTML;
            this.innerHTML = '<span class="material-symbols-outlined animate-spin text-[20px]">sync</span> Adding...';
            this.disabled = true;

            fetch('ajax/add_to_cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `product_id=<?php echo $product_id; ?>&quantity=${qty}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (window.refreshCartCounter) {
                        window.refreshCartCounter(data.cart_count);
                    }
                    this.innerHTML = '<span class="material-symbols-outlined text-[20px]">check</span> Added!';
                    setTimeout(() => {
                        this.innerHTML = originalContent;
                        this.disabled = false;
                    }, 2000);
                } else {
                    alert(data.message);
                    this.innerHTML = originalContent;
                    this.disabled = false;
                }
            });
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>
