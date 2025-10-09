<?php
// Include database connection
require_once __DIR__ . '/db.php';

// Detect if we're in a subdirectory (admin, user, or legal)
$is_subdirectory = false;
$current_path = $_SERVER['PHP_SELF'] ?? '';
if (preg_match('/\/(admin|user|legal)\//', $current_path, $matches)) {
    $is_subdirectory = true;
}

// Get cart count for logged-in users
$cart_count = 0;
if (isset($_SESSION['user_id']) && $pdo !== null) {
    try {
        $stmt = $pdo->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $result = $stmt->fetch();
        $cart_count = $result['total'] ?? 0;
    } catch(PDOException $e) {
        // Handle error silently
        $cart_count = 0;
    }
}

// Get all categories for dropdown
$nav_categories = [];
if ($pdo !== null) {
    try {
        $stmt = $pdo->query("SELECT * FROM categories ORDER BY category_name");
        $nav_categories = $stmt->fetchAll();
    } catch(PDOException $e) {
        // Handle error silently
        $nav_categories = [];
    }
}
?>

<nav class="navbar navbar-expand-lg navbar-light bg-white">
    <div class="container">
        <!-- Brand -->
        <a class="navbar-brand" href="<?php echo $is_subdirectory ? '../index.php' : 'index.php'; ?>">
            <i class="fas fa-store me-2"></i><?php echo isset($site_name) ? htmlspecialchars($site_name) : 'ASO Online Market'; ?>
        </a>

        <!-- Mobile menu button -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navigation menu -->
        <div class="collapse navbar-collapse" id="navbarNav">

            <!-- Left side menu -->
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $is_subdirectory ? '../index.php' : 'index.php'; ?>">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $is_subdirectory ? '../shop.php' : 'shop.php'; ?>">Shop</a>
                </li>

                <!-- Categories dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="categoriesDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Categories
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="categoriesDropdown">
                            <?php foreach ($nav_categories as $category): ?>
                            <li>
                                <a class="dropdown-item" href="<?php echo $is_subdirectory ? '../category.php?id=' . $category['category_id'] : 'category.php?id=' . $category['category_id']; ?>">
                                    <?php echo htmlspecialchars($category['category_name']); ?>
                                </a>
                            </li>
                            <?php endforeach; ?>
                    </ul>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $is_subdirectory ? '../about.php' : 'about.php'; ?>">About</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $is_subdirectory ? '../contact.php' : 'contact.php'; ?>">Contact</a>
                </li>
            </ul>

            <!-- Search Form -->
            <form class="d-flex me-3" action="<?php echo $is_subdirectory ? '../search.php' : 'search.php'; ?>" method="GET" style="flex-grow: 1; max-width: 400px;">
                <div class="input-group">
                    <input type="text" class="form-control form-control-sm" name="q" placeholder="Search products..." 
                           value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>" 
                           aria-label="Search products" required>
                    <button class="btn btn-outline-primary" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>

            <!-- Right side menu -->
            <ul class="navbar-nav">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <!-- User is logged in -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" data-bs-auto-close="true" aria-expanded="false" style="cursor: pointer;">
                            <i class="fas fa-user me-1"></i>
                            <span class="d-none d-sm-inline"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li>
                                <a class="dropdown-item" href="<?php echo $is_subdirectory ? '../user/profile.php' : 'user/profile.php'; ?>">
                                    <i class="fas fa-user me-2"></i>Profile
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?php echo $is_subdirectory ? '../user/orders.php' : 'user/orders.php'; ?>">
                                    <i class="fas fa-shopping-bag me-2"></i>My Orders
                                </a>
                            </li>
                                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="<?php echo $is_subdirectory ? '../admin/dashboard.php' : 'admin/dashboard.php'; ?>">
                                        <i class="fas fa-cog me-2"></i>Admin Panel
                                    </a>
                                </li>
                                <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="<?php echo $is_subdirectory ? '../logout.php' : 'logout.php'; ?>">
                                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                                </a>
                    </li>
                        </ul>
                    </li>
                <?php else: ?>
                    <!-- User is not logged in -->
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $is_subdirectory ? '../login.php' : 'login.php'; ?>">
                            <i class="fas fa-sign-in-alt me-1"></i>
                            <span class="d-none d-sm-inline">Login</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $is_subdirectory ? '../register.php' : 'register.php'; ?>">
                            <i class="fas fa-user-plus me-1"></i>
                            <span class="d-none d-sm-inline">Register</span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
        </div>
    </div>
</nav>

<!-- Navbar Size Adjustments -->
<style>
/* Make navbar slightly smaller */
.navbar {
    padding: 0.4rem 0 !important;
}

.navbar-brand {
    font-size: 1.3rem !important;
    font-weight: 600 !important;
}

.navbar-nav .nav-link {
    padding: 0.4rem 0.8rem !important;
    font-size: 0.9rem !important;
    font-weight: 500 !important;
}

.navbar-toggler {
    padding: 0.25rem 0.5rem !important;
    font-size: 0.9rem !important;
}

/* Adjust search form */
.navbar .form-control {
    padding: 0.4rem 0.8rem !important;
    font-size: 0.9rem !important;
}

.navbar .btn {
    padding: 0.4rem 0.8rem !important;
    font-size: 0.9rem !important;
}

/* Dropdown fixes */
.dropdown-toggle {
    cursor: pointer !important;
    user-select: none !important;
}

.dropdown-toggle:hover {
    background-color: rgba(0,0,0,0.05) !important;
}

.dropdown-menu {
    border: 1px solid rgba(0,0,0,.15) !important;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    border-radius: 0.375rem !important;
    z-index: 1050 !important;
    position: absolute !important;
    display: none !important;
}

.dropdown-menu.show {
    display: block !important;
}

.dropdown-menu-end {
    right: 0 !important;
    left: auto !important;
}

/* Ensure navbar has proper z-index */
.navbar {
    z-index: 1030 !important;
}

/* Ensure dropdown works on mobile */
@media (max-width: 991.98px) {
    .dropdown-menu {
        position: static !important;
        float: none !important;
        width: 100% !important;
        margin-top: 0.125rem !important;
        background-color: transparent !important;
        border: none !important;
        box-shadow: none !important;
    }
    
    .dropdown-item {
        padding: 0.5rem 1rem !important;
        color: rgba(255, 255, 255, 0.8) !important;
    }
    
    .dropdown-item:hover {
        background-color: rgba(255, 255, 255, 0.1) !important;
        color: white !important;
    }
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .navbar {
        padding: 0.3rem 0 !important;
    }
    
    .navbar-brand {
        font-size: 1.1rem !important;
    }
    
    .navbar-nav .nav-link {
        padding: 0.3rem 0.6rem !important;
        font-size: 0.85rem !important;
    }
}

@media (max-width: 576px) {
    .navbar-brand {
        font-size: 1rem !important;
    }
    
    .navbar-nav .nav-link {
        padding: 0.25rem 0.5rem !important;
        font-size: 0.8rem !important;
    }
}
</style>

<!-- Direct Dropdown Fix -->
<script>
// Immediate dropdown fix - no waiting
(function() {
    console.log('Direct dropdown fix starting');
    
    function fixDropdowns() {
        const dropdownToggles = document.querySelectorAll('[data-bs-toggle="dropdown"]');
        console.log('Found dropdown toggles:', dropdownToggles.length);
        
        dropdownToggles.forEach(function(toggle, index) {
            console.log('Fixing dropdown', index, toggle);
            
            // Remove any existing event listeners
            const newToggle = toggle.cloneNode(true);
            toggle.parentNode.replaceChild(newToggle, toggle);
            
            // Add click event
            newToggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Dropdown clicked:', newToggle);
                
                const dropdownMenu = newToggle.nextElementSibling;
                if (dropdownMenu && dropdownMenu.classList.contains('dropdown-menu')) {
                    // Close all other dropdowns
                    document.querySelectorAll('.dropdown-menu').forEach(function(menu) {
                        if (menu !== dropdownMenu) {
                            menu.classList.remove('show');
                        }
                    });
                    
                    // Toggle current dropdown
                    dropdownMenu.classList.toggle('show');
                    console.log('Dropdown toggled:', dropdownMenu.classList.contains('show'));
                }
            });
        });
        
        // Close dropdowns when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.dropdown')) {
                document.querySelectorAll('.dropdown-menu').forEach(function(menu) {
                    menu.classList.remove('show');
                });
            }
        });
    }
    
    // Run immediately
    fixDropdowns();
    
    // Also run when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', fixDropdowns);
    }
    
    // Also run after a short delay
    setTimeout(fixDropdowns, 100);
    setTimeout(fixDropdowns, 500);
    setTimeout(fixDropdowns, 1000);
})();
</script>





