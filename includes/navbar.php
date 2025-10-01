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

<nav class="navbar navbar-expand-lg navbar-light sticky-top">
    <div class="container">
        <!-- Brand -->
        <a class="navbar-brand" href="<?php echo $is_subdirectory ? '../index.php' : 'index.php'; ?>">
            <i class="fas fa-store me-2"></i><?php echo isset($site_name) ? htmlspecialchars($site_name) : 'ASO Online Market'; ?>
        </a>

        <!-- Mobile toggle button -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
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
                <li class="nav-item">
                    <div class="dropdown-container">
                        <a class="nav-link dropdown-toggle-custom" href="#" onclick="toggleCustomDropdown(event, 'categories-menu')">
                            Categories <i class="fas fa-chevron-down"></i>
                        </a>
                        <div class="dropdown-menu-custom" id="categories-menu">
                            <?php foreach ($nav_categories as $category): ?>
                                <a href="<?php echo $is_subdirectory ? '../category.php?id=' . $category['category_id'] : 'category.php?id=' . $category['category_id']; ?>" class="dropdown-item-custom">
                                    <?php echo htmlspecialchars($category['category_name']); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $is_subdirectory ? '../about.php' : 'about.php'; ?>">About</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $is_subdirectory ? '../contact.php' : 'contact.php'; ?>">Contact</a>
                </li>
            </ul>

            <!-- Right side menu -->
            <ul class="navbar-nav">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <!-- User is logged in -->
                    <li class="nav-item">
                        <div class="dropdown-container">
                            <a class="nav-link dropdown-toggle-custom" href="#" onclick="toggleCustomDropdown(event, 'user-menu')">
                                <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($_SESSION['user_name']); ?> <i class="fas fa-chevron-down"></i>
                            </a>
                            <div class="dropdown-menu-custom" id="user-menu">
                                <a href="<?php echo $is_subdirectory ? '../user/profile.php' : 'user/profile.php'; ?>" class="dropdown-item-custom">Profile</a>
                                <a href="<?php echo $is_subdirectory ? '../user/orders.php' : 'user/orders.php'; ?>" class="dropdown-item-custom">My Orders</a>
                                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                                    <div class="dropdown-divider-custom"></div>
                                    <a href="<?php echo $is_subdirectory ? '../admin/dashboard.php' : 'admin/dashboard.php'; ?>" class="dropdown-item-custom">
                                        <i class="fas fa-cog me-1"></i>Admin Panel
                                    </a>
                                <?php endif; ?>
                                <div class="dropdown-divider-custom"></div>
                                <a href="<?php echo $is_subdirectory ? '../logout.php' : 'logout.php'; ?>" class="dropdown-item-custom">Logout</a>
                            </div>
                        </div>
                    </li>

                    <!-- Cart with badge -->
                    <li class="nav-item">
                        <a class="nav-link position-relative" href="<?php echo $is_subdirectory ? '../cart.php' : 'cart.php'; ?>">
                            <i class="fas fa-shopping-cart"></i>
                            <?php if ($cart_count > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    <?php echo $cart_count; ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    </li>
                <?php else: ?>
                    <!-- User is not logged in -->
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $is_subdirectory ? '../login.php' : 'login.php'; ?>">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $is_subdirectory ? '../register.php' : 'register.php'; ?>">Register</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Navbar Dropdown JavaScript -->
<script>
function toggleCustomDropdown(event, menuId) {
    event.preventDefault();

    // Close all other dropdowns first
    document.querySelectorAll('.dropdown-menu-custom').forEach(menu => {
        if (menu.id !== menuId) {
            menu.classList.remove('show');
        }
    });

    // Toggle the clicked dropdown
    const menu = document.getElementById(menuId);
    if (menu) {
        menu.classList.toggle('show');
    }

    // Close dropdown when clicking outside
    setTimeout(() => {
        document.addEventListener('click', function closeDropdown(e) {
            if (!e.target.closest('.dropdown-container')) {
                menu.classList.remove('show');
                document.removeEventListener('click', closeDropdown);
            }
        });
    }, 100);
}
</script>
