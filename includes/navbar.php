<?php
/**
 * Navigation Bar
 * - Handles category fetching
 * - Handles cart count
 * - Handles user login state
 */

// Detect if we're in a subdirectory (admin, user, or legal)
$is_subdirectory = false;
$current_path = $_SERVER['PHP_SELF'] ?? '';
if (preg_match('/\/(admin|user|legal)\//', $current_path, $matches)) {
    $is_subdirectory = true;
}
$link_base = $is_subdirectory ? '../' : '';

// Get cart count for logged-in users
$cart_count = 0;
if (isset($_SESSION['user_id']) && isset($pdo)) {
    try {
        $stmt = $pdo->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $result = $stmt->fetch();
        $cart_count = $result['total'] ?? 0;
    } catch(PDOException $e) {
        $cart_count = 0;
    }
}

// Get all categories for dropdown
$nav_categories = [];
if (isset($pdo)) {
    try {
        $stmt = $pdo->query("SELECT * FROM categories ORDER BY category_name");
        $nav_categories = $stmt->fetchAll();
    } catch(PDOException $e) {
        $nav_categories = [];
    }
}
?>

<header class="w-full top-0 sticky bg-surface-container-lowest shadow-sm border-b border-outline-variant z-50 transition-all duration-300">
    <div class="max-w-container-max mx-auto px-md h-12 lg:h-14 flex items-center justify-between">
        <!-- Left Section: Hamburger & Branding -->
        <div class="flex items-center gap-xs md:gap-md relative z-10">
            <!-- Mobile Hamburger Button -->
            <button id="mobileMenuBtn" class="md:hidden p-xs hover:bg-surface-container-low rounded-full text-on-surface-variant active:scale-95 transition-transform">
                <span class="material-symbols-outlined text-[24px]" id="hamburgerIcon">menu</span>
            </button>

            <!-- Logo & Brand Name -->
            <span class="flex items-center gap-2">
                <a href="<?php echo $link_base; ?>index.php" class="flex items-center transition-transform hover:scale-105 h-full py-1">
                    <img src="<?php echo $link_base; ?>assets/images/logo-v3.png" alt="<?php echo htmlspecialchars($site_name); ?>" class="h-10 lg:h-12 w-auto object-contain" />
                </a>
                <h2 class="shop-text text-[20px] lg:text-[22px] font-semibold leading-none tracking-tight select-none" style="color: <?php echo $primary_color; ?>;">Aso Online</h2>
            </span>
            
            
            <!-- Desktop Navigation -->
            <nav class="hidden md:flex items-center gap-8 ml-xl">
                <a class="text-on-surface-variant hover:text-[#1A1A1A] transition-all font-headline-sm text-[15px] tracking-tight relative group" href="<?php echo $link_base; ?>index.php">
                    Home
                    <span class="absolute -bottom-1 left-0 w-0 h-0.5 bg-primary transition-all group-hover:w-full rounded-full"></span>
                </a>
                <a class="text-on-surface-variant hover:text-[#1A1A1A] transition-all font-headline-sm text-[15px] tracking-tight relative group" href="<?php echo $link_base; ?>shop.php">
                    Shop
                    <span class="absolute -bottom-1 left-0 w-0 h-0.5 bg-primary transition-all group-hover:w-full rounded-full"></span>
                </a>
                
                <!-- Dynamic Categories -->
                <div class="relative group">
                    <button class="text-on-surface-variant hover:text-[#1A1A1A] transition-all font-headline-sm text-[15px] tracking-tight flex items-center gap-xs">
                        Categories <span class="material-symbols-outlined text-[18px] group-hover:rotate-180 transition-transform">expand_more</span>
                    </button>
                    <div class="absolute top-full left-0 mt-3 w-56 bg-surface-container-lowest/95 backdrop-blur-md border border-outline-variant rounded-2xl shadow-2xl opacity-0 invisible group-hover:opacity-100 group-hover:visible translate-y-2 group-hover:translate-y-0 transition-all z-50 py-3">
                        <?php foreach ($nav_categories as $category): ?>
                            <a href="<?php echo $link_base; ?>category.php?id=<?php echo $category['category_id']; ?>" class="flex items-center gap-md px-md py-sm hover:bg-primary/5 text-on-surface-variant hover:text-[#1A1A1A] transition-colors font-label-md">
                                <span class="w-1.5 h-1.5 rounded-full bg-outline-variant"></span>
                                <?php echo htmlspecialchars($category['category_name']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                
            </nav>
        </div>

        <!-- Right Section: Search & Actions -->
        <div class="flex items-center gap-md relative z-10">
            <!-- Search Bar (Desktop) -->
            <div class="hidden lg:block relative group">
                <form action="<?php echo $link_base; ?>search.php" method="GET" class="relative">
                    <span class="material-symbols-outlined absolute left-md top-1/2 -translate-y-1/2 text-on-surface-variant text-[20px] group-focus-within:text-[#1A1A1A] transition-colors">search</span>
                    <input type="text" id="desktopSearchInput" name="q" autocomplete="off" placeholder="Search products..." value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>" 
                           class="pl-[44px] pr-md py-2.5 rounded-2xl bg-surface-container-low border border-outline-variant/30 focus:border-primary/50 focus:bg-surface-container-lowest focus:ring-4 focus:ring-primary/10 text-body-sm w-64 xl:w-80 transition-all outline-none" required />
                </form>
                <!-- Desktop Suggestions Dropdown -->
                <div id="desktopSuggestions" class="absolute top-full left-0 right-0 mt-3 bg-surface-container-lowest/95 backdrop-blur-md border border-outline-variant rounded-2xl shadow-2xl hidden z-[60] overflow-hidden"></div>
            </div>

            <div class="flex items-center gap-1">
                <!-- Search Icon (Mobile/Tablet Only) -->
                <button id="mobileSearchBtn" class="lg:hidden p-2 hover:bg-[#F5F5F5] rounded-full text-[#1A1A1A] active:scale-95 transition-transform flex items-center justify-center">
                    <span class="material-symbols-outlined text-[24px]">search</span>
                </button>

                <!-- Cart -->
                <a href="<?php echo $link_base; ?>cart.php" class="relative cursor-pointer active:scale-95 transition-transform p-2 hover:bg-[#F5F5F5] rounded-full text-[#1A1A1A] group flex items-center justify-center">
                    <span class="material-symbols-outlined text-[24px] group-hover:scale-105 transition-transform">shopping_cart</span>
                    <span class="cart-counter-badge absolute top-1 right-1 bg-primary text-white text-[9px] font-black h-4 w-4 flex items-center justify-center rounded-full border-2 border-white <?php echo ($cart_count > 0) ? '' : 'hidden'; ?>">
                        <?php echo $cart_count; ?>
                    </span>
                </a>

                <!-- User Account -->
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="relative group">
                        <button class="flex items-center gap-2 p-2 hover:bg-[#F5F5F5] rounded-full transition-all active:scale-95 text-[#1A1A1A] group/btn">
                            <span class="material-symbols-outlined text-[24px] group-hover/btn:scale-105 transition-transform">account_circle</span>
                            <span class="hidden md:block font-black text-[13px] tracking-tight group-hover/btn:text-black transition-colors"><?php echo explode(' ', htmlspecialchars($_SESSION['user_name']))[0]; ?></span>
                        </button>
                        <div class="absolute top-full right-0 mt-2 w-52 bg-white border border-[#EEEEEE] rounded-[1rem] shadow-xl opacity-0 invisible group-hover:opacity-100 group-hover:visible translate-y-2 group-hover:translate-y-0 transition-all z-50 py-3">
                            <div class="px-4 py-1.5 mb-1">
                                <p class="text-[#1A1A1A] font-black text-[13px] truncate leading-tight"><?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
                                <p class="text-[#888888] font-bold text-[10px] uppercase tracking-widest truncate mt-0.5"><?php echo htmlspecialchars($_SESSION['user_email']); ?></p>
                            </div>
                            <div class="h-[1px] bg-[#F5F5F5] mx-3 my-1.5"></div>
                            <a href="<?php echo $link_base; ?>user/profile.php" class="flex items-center gap-3 px-4 py-2 hover:bg-[#F9F9F9] text-[#666666] hover:text-[#1A1A1A] transition-colors font-bold text-[12px]">
                                <span class="material-symbols-outlined text-[18px]">settings</span> Profile Settings
                            </a>
                            <a href="<?php echo $link_base; ?>user/orders.php" class="flex items-center gap-3 px-4 py-2 hover:bg-[#F9F9F9] text-[#666666] hover:text-[#1A1A1A] transition-colors font-bold text-[12px]">
                                <span class="material-symbols-outlined text-[18px]">package</span> My Orders
                            </a>
                            <a href="<?php echo $link_base; ?>user/wishlist.php" class="flex items-center gap-3 px-4 py-2 hover:bg-[#F9F9F9] text-[#666666] hover:text-[#1A1A1A] transition-colors font-bold text-[12px]">
                                <span class="material-symbols-outlined text-[18px]">favorite</span> My Wishlist
                            </a>
                            <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                                <div class="h-[1px] bg-[#F5F5F5] mx-3 my-1.5"></div>
                                <a href="<?php echo $link_base; ?>admin/dashboard.php" class="flex items-center gap-3 px-4 py-2 hover:bg-[#F9F9F9] text-[#1A1A1A] transition-colors font-black text-[12px]">
                                    <span class="material-symbols-outlined text-[18px]">admin_panel_settings</span> Admin Panel
                                </a>
                            <?php endif; ?>
                            <div class="h-[1px] bg-[#F5F5F5] mx-3 my-1.5"></div>
                            <a href="<?php echo $link_base; ?>logout.php" class="flex items-center gap-3 px-4 py-2 hover:bg-[#FEF2F2] text-[#EF4444] transition-colors font-black text-[12px]">
                                <span class="material-symbols-outlined text-[18px]">logout</span> Logout
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="<?php echo $link_base; ?>login.php" class="flex items-center gap-2 p-2 hover:bg-[#F5F5F5] rounded-full text-[#1A1A1A] active:scale-95 transition-transform group">
                        <span class="material-symbols-outlined text-[24px] group-hover:scale-105 transition-transform">login</span>
                        <span class="hidden md:block font-black text-[13px] tracking-tight group-hover:text-black transition-colors">Sign In</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Mobile Search Bar (Collapsable) -->
    <div id="mobileSearchContainer" class="hidden sm:hidden border-t border-outline-variant bg-surface-container-lowest px-md py-sm animate-in fade-in slide-in-from-top-2 duration-300 relative">
        <form action="<?php echo $link_base; ?>search.php" method="GET" class="relative">
            <span class="material-symbols-outlined absolute left-sm top-1/2 -translate-y-1/2 text-on-surface-variant text-[20px]">search</span>
            <input type="text" id="mobileSearchInput" name="q" autocomplete="off" placeholder="Search for products..." value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>" class="w-full pl-xl pr-md py-sm rounded-xl bg-surface-container border-none focus:ring-2 focus:ring-primary text-body-md" required />
        </form>
        <!-- Mobile Suggestions Dropdown -->
        <div id="mobileSuggestions" class="absolute top-full left-0 right-0 mx-md bg-surface-container-lowest border border-outline-variant rounded-b-xl shadow-2xl hidden z-[60] overflow-hidden"></div>
    </div>

    <!-- Mobile Navigation Drawer -->
    <div id="mobileDrawer" class="fixed inset-0 z-[60] bg-[#1A1A1A]/40 opacity-0 pointer-events-none transition-opacity duration-300 md:hidden backdrop-blur-sm">
        <div id="drawerContent" class="absolute top-0 left-0 h-full w-[300px] bg-white shadow-2xl translate-x-[-100%] transition-transform duration-300 ease-out flex flex-col">
            <!-- Header -->
            <div class="px-6 py-5 flex items-center justify-between border-b border-[#F1F5F9]">
                <img src="<?php echo $link_base; ?>assets/images/logo-v3.png" alt="Logo" class="h-12 w-auto object-contain" />
                <button id="closeDrawer" class="p-2 -mr-2 hover:bg-[#F1F5F9] rounded-full text-[#64748B] transition-colors">
                    <span class="material-symbols-outlined text-[24px]">close</span>
                </button>
            </div>
            
            <!-- Navigation Links -->
            <nav class="flex-1 overflow-y-auto py-6">
                <div class="px-6 mb-4 text-[12px] font-bold text-[#64748B] uppercase tracking-widest">Main Menu</div>
                <a href="<?php echo $link_base; ?>index.php" class="flex items-center gap-4 px-6 py-3 hover:bg-[#F8FAFC] text-[#1E293B] transition-colors text-[16px] font-medium">
                    <span class="material-symbols-outlined text-[#1E293B] text-[22px]" style="font-variation-settings: 'FILL' 0;">home</span> Home
                </a>
                <a href="<?php echo $link_base; ?>shop.php" class="flex items-center gap-4 px-6 py-3 hover:bg-[#F8FAFC] text-[#1E293B] transition-colors text-[16px] font-medium">
                    <span class="material-symbols-outlined text-[#1E293B] text-[22px]" style="font-variation-settings: 'FILL' 0;">shopping_bag</span> Shop
                </a>

                <div class="px-6 mt-8 mb-4 text-[12px] font-bold text-[#64748B] uppercase tracking-widest">Categories</div>
                <div class="grid grid-cols-1">
                    <?php foreach ($nav_categories as $category): ?>
                        <a href="<?php echo $link_base; ?>category.php?id=<?php echo $category['category_id']; ?>" class="flex items-center gap-4 px-6 py-3 hover:bg-[#F8FAFC] text-[#475569] transition-colors text-[15px]">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#CBD5E1] ml-2"></span>
                            <?php echo htmlspecialchars($category['category_name']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </nav>

            <!-- Bottom Action/User Section -->
            <div class="p-5 border-t border-[#F1F5F9] bg-[#F8FAFC]">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="flex items-center gap-3 mb-5">
                        <div class="w-10 h-10 rounded-full bg-[#E2E8F0] flex items-center justify-center text-[#1E293B] font-bold text-[15px]">
                            <?php echo substr($_SESSION['user_name'], 0, 1); ?>
                        </div>
                        <div>
                            <p class="text-[#1E293B] font-medium text-[14px] leading-tight"><?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
                            <p class="text-[#64748B] text-[11px] mt-0.5"><?php echo htmlspecialchars($_SESSION['user_email']); ?></p>
                        </div>
                    </div>
                    <a href="<?php echo $link_base; ?>user/wishlist.php" class="flex items-center gap-4 px-3 py-2 mb-4 hover:bg-[#F1F5F9] rounded-xl text-[#1E293B] transition-colors text-[14px] font-medium border border-[#F1F5F9]">
                        <span class="material-symbols-outlined text-[#1E293B] text-[20px]" style="font-variation-settings: 'FILL' 0;">favorite</span> My Wishlist
                    </a>
                    <a href="<?php echo $link_base; ?>logout.php" class="flex items-center justify-center gap-2 w-full py-2.5 rounded-xl bg-[#FCE8E8] text-[#EF4444] font-medium text-[14px] hover:bg-[#FEE2E2] transition-colors">
                        <span class="material-symbols-outlined text-[18px]" style="font-variation-settings: 'FILL' 0;">logout</span> Logout
                    </a>
                <?php else: ?>
                    <a href="<?php echo $link_base; ?>login.php" class="flex items-center justify-center gap-2 w-full py-2.5 rounded-xl bg-primary text-white font-medium text-[14px] hover:bg-primary/90 transition-colors shadow-sm">
                        <span class="material-symbols-outlined text-[18px]" style="font-variation-settings: 'FILL' 0;">login</span> Sign In
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const mobileDrawer = document.getElementById('mobileDrawer');
    const drawerContent = document.getElementById('drawerContent');
    const closeDrawer = document.getElementById('closeDrawer');
    
    const mobileSearchBtn = document.getElementById('mobileSearchBtn');
    const mobileSearchContainer = document.getElementById('mobileSearchContainer');
    const mobileSearchInput = document.getElementById('mobileSearchInput');
    const mobileSuggestions = document.getElementById('mobileSuggestions');

    const desktopSearchInput = document.getElementById('desktopSearchInput');
    const desktopSuggestions = document.getElementById('desktopSuggestions');
    
    const body = document.body;

    function toggleDrawer(open) {
        if (open) {
            mobileDrawer.classList.remove('pointer-events-none');
            mobileDrawer.classList.add('opacity-100');
            drawerContent.classList.remove('translate-x-[-100%]');
            body.style.overflow = 'hidden';
        } else {
            mobileDrawer.classList.remove('opacity-100');
            mobileDrawer.classList.add('pointer-events-none');
            drawerContent.classList.add('translate-x-[-100%]');
            body.style.overflow = '';
        }
    }

    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', () => toggleDrawer(true));
    }

    if (closeDrawer) {
        closeDrawer.addEventListener('click', () => toggleDrawer(false));
    }

    if (mobileDrawer) {
        mobileDrawer.addEventListener('click', (e) => {
            if (e.target === mobileDrawer) toggleDrawer(false);
        });
    }

    // Mobile Search Toggle
    if (mobileSearchBtn && mobileSearchContainer) {
        mobileSearchBtn.addEventListener('click', () => {
            mobileSearchContainer.classList.toggle('hidden');
            if (!mobileSearchContainer.classList.contains('hidden')) {
                mobileSearchInput.focus();
            }
        });
    }

    // Live Search Logic
    function handleLiveSearch(input, resultsContainer) {
        let debounceTimer;
        input.addEventListener('input', (e) => {
            clearTimeout(debounceTimer);
            const query = e.target.value.trim();
            
            if (query.length < 2) {
                resultsContainer.innerHTML = '';
                resultsContainer.classList.add('hidden');
                return;
            }

            debounceTimer = setTimeout(() => {
                fetch(`<?php echo $link_base; ?>api/search_suggestions.php?q=${encodeURIComponent(query)}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.length > 0) {
                            resultsContainer.innerHTML = data.map(item => `
                                <a href="<?php echo $link_base; ?>product.php?id=${item.id}" class="flex items-center gap-sm p-sm hover:bg-surface-container-high transition-colors border-b border-outline-variant last:border-none">
                                    <img src="<?php echo $link_base; ?>assets/images/${item.image}" class="w-10 h-10 object-contain mix-blend-multiply bg-white rounded" />
                                    <div class="flex-1 overflow-hidden">
                                        <p class="text-label-md text-on-surface truncate">${item.name}</p>
                                        <p class="text-label-sm text-[#1A1A1A] font-bold">${item.price}</p>
                                    </div>
                                    <span class="material-symbols-outlined text-on-surface-variant text-[18px]">chevron_right</span>
                                </a>
                            `).join('') + `
                                <a href="<?php echo $link_base; ?>search.php?q=${encodeURIComponent(query)}" class="block p-sm text-center text-[#1A1A1A] font-label-md hover:bg-surface-container-high transition-colors">
                                    View all results
                                </a>
                            `;
                            resultsContainer.classList.remove('hidden');
                        } else {
                            resultsContainer.innerHTML = '<div class="p-md text-center text-on-surface-variant font-body-sm">No products found</div>';
                            resultsContainer.classList.remove('hidden');
                        }
                    });
            }, 300);
        });

        // Close when clicking outside
        document.addEventListener('click', (e) => {
            if (!input.contains(e.target) && !resultsContainer.contains(e.target)) {
                resultsContainer.classList.add('hidden');
            }
        });
    }

    if (desktopSearchInput && desktopSuggestions) {
        handleLiveSearch(desktopSearchInput, desktopSuggestions);
    }
    if (mobileSearchInput && mobileSuggestions) {
        handleLiveSearch(mobileSearchInput, mobileSuggestions);
    }

    // --- Global Cart Synchronization ---
    window.refreshCartCounter = function(count) {
        const badges = document.querySelectorAll('.cart-counter-badge');
        badges.forEach(badge => {
            badge.textContent = count;
            if (parseInt(count) > 0) {
                badge.classList.remove('hidden');
                // Pulse animation for feedback
                badge.animate([
                    { transform: 'scale(1)', offset: 0 },
                    { transform: 'scale(1.4)', offset: 0.5 },
                    { transform: 'scale(1)', offset: 1 }
                ], { duration: 300, easing: 'ease-out' });
            } else {
                badge.classList.add('hidden');
            }
        });
        // Sync to local storage for other tabs
        localStorage.setItem('cart_count_updated', Date.now() + ':' + count);
    };

    // Listen for updates from other tabs
    window.addEventListener('storage', (e) => {
        if (e.key === 'cart_count_updated') {
            const newCount = e.newValue.split(':')[1];
            refreshCartCounter(newCount);
        }
    });

    // Sync current PHP count to local storage on load (to update other tabs)
    const initialCount = '<?php echo $cart_count; ?>';
    localStorage.setItem('cart_count_updated', Date.now() + ':' + initialCount);
});
</script>





