<?php
/**
 * Storefront: Home
 * - Welcomes users and highlights featured products
 * - Lists categories with product counts
 */

// Include database connection
require_once 'includes/db.php';

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set page title
$page_title = 'Home';

// Get featured products (limit 8)
$featured_products = [];
try {
    $stmt = $pdo->query("SELECT p.*, c.category_name FROM products p
                        JOIN categories c ON p.category_id = c.category_id
                        ORDER BY p.created_at DESC LIMIT 8");
    $featured_products = $stmt->fetchAll();
} catch(PDOException $e) {
    error_log("Error fetching featured products: " . $e->getMessage());
}

// Handle AJAX add to cart for featured products
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_add_to_cart'])) {
    $ajax_product_id = (int)($_POST['product_id'] ?? 0);
    $ajax_quantity = (int)($_POST['quantity'] ?? 1);

    if ($ajax_product_id > 0 && $ajax_quantity > 0) {
        try {
            // Get product details for validation
            $stmt = $pdo->prepare("SELECT stock_quantity, name FROM products WHERE product_id = ?");
            $stmt->execute([$ajax_product_id]);
            $ajax_product = $stmt->fetch();

            if ($ajax_product && $ajax_quantity <= $ajax_product['stock_quantity']) {
                if (isset($_SESSION['user_id'])) {
                    // User is logged in - add to database cart
                    $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity)
                                         VALUES (?, ?, ?)
                                         ON DUPLICATE KEY UPDATE quantity = quantity + ?");
                    $stmt->execute([$_SESSION['user_id'], $ajax_product_id, $ajax_quantity, $ajax_quantity]);
                } else {
                    // User is not logged in - add to session cart
                    if (!isset($_SESSION['cart'])) {
                        $_SESSION['cart'] = [];
                    }

                    if (isset($_SESSION['cart'][$ajax_product_id])) {
                        $_SESSION['cart'][$ajax_product_id] += $ajax_quantity;
                    } else {
                        $_SESSION['cart'][$ajax_product_id] = $ajax_quantity;
                    }
                }

                echo json_encode(['success' => true, 'message' => $ajax_product['name'] . ' (Qty: ' . $ajax_quantity . ') added to cart!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid product or quantity']);
            }
        } catch(PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Error adding product to cart']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - ASO Online Market</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Other CSS and JS includes -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="hero-slider">
        <div class="hero-slide active"></div>
        <div class="hero-slide"></div>
        <div class="hero-slide"></div>
    </div>
    <div class="hero-overlay"></div>
    <div class="hero-content">
        <h1>Quality Products, Great Prices</h1>
        <p>Your trusted online shopping destination offering quality products at competitive prices with excellent customer service.</p>
        <a href="shop.php" class="btn btn-light btn-lg">
            <i class="fas fa-shopping-bag me-2"></i>Shop Now
        </a>
    </div>
</section>

<!-- Search Section -->
<section class="search-section py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-md-10">
                <div class="search-card">
                    <div class="search-header text-center mb-4">
                        <h2>Browse Our Products</h2>
                        <p>Discover quality products from our extensive collection</p>
                    </div>

                    <div class="search-suggestions">
                        <div class="text-center">
                            <div class="mb-3">
                                <strong class="text-muted">Browse by Category:</strong>
                            </div>
                            <div class="d-flex flex-wrap justify-content-center gap-2">
                                <?php
                                // Get actual categories from database
                                try {
                                    $stmt = $pdo->query('SELECT category_name FROM categories ORDER BY category_name');
                                    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

                                    if (empty($categories)) {
                                        // Fallback to default categories if none found
                                        $categories = ['Electronics', 'Clothing', 'Home & Garden', 'Sports & Outdoors', 'Books'];
                                    }

                                    $categoryIcons = [
                                        'Electronics' => 'fas fa-laptop',
                                        'Clothing' => 'fas fa-tshirt',
                                        'Home & Garden' => 'fas fa-home',
                                        'Sports & Outdoors' => 'fas fa-running',
                                        'Books' => 'fas fa-book',
                                        'Food & Beverages' => 'fas fa-utensils',
                                        'Health & Beauty' => 'fas fa-spa',
                                        'Automotive' => 'fas fa-car',
                                        'Toys & Games' => 'fas fa-gamepad'
                                    ];

                                    foreach($categories as $category) {
                                        $icon = $categoryIcons[$category] ?? 'fas fa-box';
                                        echo '<a href="shop.php?category=' . urlencode($category) . '"
                                                class="btn btn-outline-primary btn-sm" style="font-size: 0.75rem; padding: 2px 6px;"
                                                <i class="' . $icon . ' me-1"></i>' . htmlspecialchars($category) . '
                                            </a>';
                                    }
                                } catch(PDOException $e) {
                                    // Fallback if database connection fails
                                    $categories = ['Electronics', 'Clothing', 'Home & Garden'];
                                    foreach($categories as $category) {
                                        echo '<a href="shop.php?category=' . urlencode($category) . '"
                                                class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-box me-1"></i>' . htmlspecialchars($category) . '
                                            </a>';
                                    }
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Featured Products Section -->
<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2>Featured Products</h2>
            <p>Discover our handpicked selection of quality products</p>
        </div>

        <div class="row">
            <?php if (empty($featured_products)): ?>
                <div class="col-12">
                    <div class="alert alert-info text-center">
                        <i class="fas fa-info-circle me-2"></i>No products available at the moment.
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($featured_products as $product): ?>
                    <div class="col-lg-5th col-md-4 col-sm-6 mb-4">
                        <div class="card product-card h-100">
                            <img src="assets/images/<?php echo htmlspecialchars($product['image'] ?? 'placeholder.jpg'); ?>"
                                 class="card-img-top product-image" alt="<?php echo htmlspecialchars($product['name']); ?>">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title product-title">
                                    <a href="product.php?id=<?php echo $product['product_id']; ?>" class="text-decoration-none text-dark">
                                        <?php echo htmlspecialchars($product['name']); ?>
                                    </a>
                                </h5>
                                <p class="text-muted small mb-2"><?php echo htmlspecialchars($product['category_name']); ?></p>
                                <p class="product-price mb-3"><?php echo formatCurrency($product['price']); ?></p>

                                <div class="mt-auto">
                                    <?php if ($product['stock_quantity'] > 0): ?>
                                        <button class="btn btn-primary w-100 home-add-to-cart-btn"
                                                data-product-id="<?php echo $product['product_id']; ?>"
                                                data-product-name="<?php echo htmlspecialchars($product['name']); ?>">
                                            <i class="fas fa-cart-plus me-2"></i>Add to Cart
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-secondary w-100" disabled>
                                            <i class="fas fa-times me-2"></i>Out of Stock
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="text-center mt-4">
            <a href="shop.php" class="btn btn-outline-primary">View All Products</a>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="py-5">
    <div class="container">
        <div class="row">
            <div class="col-md-4 text-center mb-4">
                <div class="mb-3">
                    <i class="fas fa-shipping-fast fa-3x text-primary"></i>
                </div>
                <h4>Free Shipping</h4>
                <p>Free shipping on orders over GH₵50</p>
            </div>
            <div class="col-md-4 text-center mb-4">
                <div class="mb-3">
                    <i class="fas fa-undo fa-3x text-primary"></i>
                </div>
                <h4>Easy Returns</h4>
                <p>30-day return policy</p>
            </div>
            <div class="col-md-4 text-center mb-4">
                <div class="mb-3">
                    <i class="fas fa-shield-alt fa-3x text-primary"></i>
                </div>
                <h4>Secure Payment</h4>
                <p>100% secure payment processing</p>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>

<!-- Bootstrap JS (loaded at end for better performance) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/script.js"></script>

<script>
// Home page Add to Cart functionality
document.addEventListener('DOMContentLoaded', function() {
    // Handle home page product Add to Cart buttons
    document.querySelectorAll('.home-add-to-cart-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();

            const productId = this.getAttribute('data-product-id');
            const productName = this.getAttribute('data-product-name');

            // Disable button and show loading state
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Adding...';

            // Make AJAX request to add item to cart
            fetch('index.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `ajax_add_to_cart=1&product_id=${productId}&quantity=1`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success toast
                    showToast(data.message, 'success', 2000);

                    // Update cart count in navbar if it exists
                    const cartCountElement = document.querySelector('.cart-count');
                    if (cartCountElement) {
                        const currentCount = parseInt(cartCountElement.textContent) || 0;
                        cartCountElement.textContent = currentCount + 1;
                        cartCountElement.style.display = 'inline';
                    }
                } else {
                    showToast(data.message || 'Error adding to cart', 'danger', 2000);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error adding product to cart', 'danger', 2000);
            })
            .finally(() => {
                // Re-enable button
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-cart-plus me-2"></i>Add to Cart';
            });
        });
    });

    // Simple toast notification function
    function showToast(message, type = 'info', duration = 3000) {
        // Remove existing toasts
        const existingToasts = document.querySelectorAll('.toast-notification');
        existingToasts.forEach(toast => toast.remove());

        // Create toast element
        const toast = document.createElement('div');
        toast.className = `toast-notification alert alert-${type} position-fixed`;
        toast.style.cssText = `
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        `;
        toast.innerHTML = `
            <div class="d-flex align-items-center">
                <span>${message}</span>
                <button type="button" class="btn-close ms-auto" onclick="this.parentElement.parentElement.remove()"></button>
            </div>
        `;

        document.body.appendChild(toast);

        // Auto remove after duration
        setTimeout(() => {
            if (toast.parentNode) {
                toast.remove();
            }
        }, duration);
    }
});
</script>
<script>
// Hero Slider - Simple working version
document.addEventListener('DOMContentLoaded', function() {
    const slides = document.querySelectorAll('.hero-slide');
    let currentSlide = 0;
    
    function showSlide(index) {
        slides.forEach((slide, i) => {
            slide.classList.remove('active');
            if (i === index) {
                slide.classList.add('active');
            }
        });
    }
    
    function nextSlide() {
        currentSlide = (currentSlide + 1) % slides.length;
        showSlide(currentSlide);
    }
    
    // Start auto-advance
    setInterval(nextSlide, 5000);
    
    console.log('✅ Hero slider initialized with ' + slides.length + ' slides');
});
</script>
</body>
</html>
