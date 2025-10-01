<?php
/**
 * Storefront: Product Details
 * - Displays a single product with stock status and add-to-cart controls
 * - Shows related products from the same category
 */
// Include database connection
require_once 'includes/db.php';

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Get product ID from URL
$product_id = (int)($_GET['id'] ?? 0);

if ($product_id <= 0) {
    header('Location: shop.php');
    exit();
}

// Get product details
try {
    $stmt = $pdo->prepare("SELECT p.*, c.category_name FROM products p
                          JOIN categories c ON p.category_id = c.category_id
                          WHERE p.product_id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();

    if (!$product) {
        header('Location: shop.php');
        exit();
    }
} catch(PDOException $e) {
    error_log("Error fetching product: " . $e->getMessage());
    header('Location: shop.php');
    exit();
}

// Get related products (same category, random 4)
$related_products = [];
try {
    $stmt = $pdo->prepare("SELECT p.*, c.category_name FROM products p
                          JOIN categories c ON p.category_id = c.category_id
                          WHERE p.category_id = ? AND p.product_id != ?
                          ORDER BY RAND() LIMIT 4");
    $stmt->execute([$product['category_id'], $product_id]);
    $related_products = $stmt->fetchAll();
} catch(PDOException $e) {
    error_log("Error fetching related products: " . $e->getMessage());
}

// Set page title
$page_title = $product['name'];

// Handle AJAX add to cart for both main product and related products
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $product_id_to_add = (int)($_POST['product_id'] ?? $product_id);
    $quantity = (int)($_POST['quantity'] ?? 1);

    // Debug logging
    error_log("Add to cart request: product_id=$product_id_to_add, quantity=$quantity");

    if ($product_id_to_add > 0 && $quantity > 0) {
        try {
            // Get product details for validation
            $stmt = $pdo->prepare("SELECT stock_quantity, name FROM products WHERE product_id = ?");
            $stmt->execute([$product_id_to_add]);
            $product_to_add = $stmt->fetch();

            error_log("Product found: " . ($product_to_add ? $product_to_add['name'] : 'Not found'));

            if ($product_to_add && $quantity <= $product_to_add['stock_quantity']) {
                if (isset($_SESSION['user_id'])) {
                    // User is logged in - add to database cart
                    $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity)
                                         VALUES (?, ?, ?)
                                         ON DUPLICATE KEY UPDATE quantity = quantity + ?");
                    $stmt->execute([$_SESSION['user_id'], $product_id_to_add, $quantity, $quantity]);
                } else {
                    // User is not logged in - add to session cart
                    if (!isset($_SESSION['cart'])) {
                        $_SESSION['cart'] = [];
                    }

                    if (isset($_SESSION['cart'][$product_id_to_add])) {
                        $_SESSION['cart'][$product_id_to_add] += $quantity;
                    } else {
                        $_SESSION['cart'][$product_id_to_add] = $quantity;
                    }
                }

                echo json_encode([
                    'success' => true,
                    'message' => $product_to_add['name'] . ' (Qty: ' . $quantity . ') added to cart!'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid product or quantity'
                ]);
            }
        } catch(PDOException $e) {
            error_log("Error adding to cart: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Error adding product to cart'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid request'
        ]);
    }
    exit();
}
?>

<?php include 'includes/header.php'; ?>

<!-- Breadcrumb -->
<div class="container py-3">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Home</a></li>
            <li class="breadcrumb-item"><a href="shop.php" class="text-decoration-none">Shop</a></li>
            <li class="breadcrumb-item"><a href="shop.php?category=<?php echo urlencode($product['category_name']); ?>" class="text-decoration-none"><?php echo htmlspecialchars($product['category_name']); ?></a></li>
            <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($product['name']); ?></li>
        </ol>
    </nav>
</div>

<!-- Product Details Section -->
<section class="product-details-section py-5">
    <div class="container">
        <div class="row g-5">
            <!-- Product Images -->
            <div class="col-lg-6">
                <div class="product-gallery">
                    <div class="main-image-container mb-3">
                        <img src="assets/images/<?php echo htmlspecialchars($product['image'] ?? 'placeholder.jpg'); ?>"
                             class="img-fluid rounded shadow-sm product-main-image"
                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                             data-bs-toggle="modal" data-bs-target="#imageModal">
                    </div>

                    <!-- Image Modal -->
                    <div class="modal fade" id="imageModal" tabindex="-1">
                        <div class="modal-dialog modal-lg modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body text-center">
                                    <img src="assets/images/<?php echo htmlspecialchars($product['image'] ?? 'placeholder.jpg'); ?>"
                                         class="img-fluid" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Product Information -->
            <div class="col-lg-6">
                <div class="product-info-card">
                    <!-- Product Title & Category -->
                    <div class="mb-3">
                        <span class="product-category-badge"><?php echo htmlspecialchars($product['category_name']); ?></span>
                        <h1 class="product-title mt-2"><?php echo htmlspecialchars($product['name']); ?></h1>
                    </div>

                    <!-- Price -->
                    <div class="product-price-section mb-4">
                        <div class="product-price">
                            <?php echo formatCurrency($product['price']); ?>
                        </div>
                        <?php if ($product['stock_quantity'] > 0): ?>
                            <div class="stock-info mt-2">
                                <?php if ($product['stock_quantity'] > 10): ?>
                                    <span class="badge bg-success stock-badge">
                                        <i class="fas fa-check-circle me-1"></i>In Stock (<?php echo $product['stock_quantity']; ?> available)
                                    </span>
                                <?php elseif ($product['stock_quantity'] > 0): ?>
                                    <span class="badge bg-warning stock-badge">
                                        <i class="fas fa-exclamation-triangle me-1"></i>Limited Stock (<?php echo $product['stock_quantity']; ?> left)
                                    </span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Add to Cart Section -->
                    <?php if ($product['stock_quantity'] > 0): ?>
                        <div class="add-to-cart-section mb-4">
                            <form method="POST" action="" id="add-to-cart-form" class="d-flex align-items-end gap-3">
                                <input type="hidden" name="add_to_cart" value="1">
                                <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">

                                <div class="quantity-selector">
                                    <label class="form-label mb-2">Quantity</label>
                                    <div class="input-group quantity-input-group">
                                        <button type="button" class="btn btn-outline-secondary quantity-btn"
                                                onclick="updateQuantity(<?php echo $product_id; ?>, -1)">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                        <input type="number" class="form-control text-center quantity-input"
                                               id="quantity-<?php echo $product_id; ?>"
                                               name="quantity" value="1" min="1" max="<?php echo $product['stock_quantity']; ?>">
                                        <button type="button" class="btn btn-outline-secondary quantity-btn"
                                                onclick="updateQuantity(<?php echo $product_id; ?>, 1)">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="add-to-cart-btn-container">
                                    <button type="submit" class="btn btn-primary btn-lg add-to-cart-btn" id="add-to-cart-btn">
                                        <i class="fas fa-cart-plus me-2"></i>Add to Cart
                                    </button>
                                </div>
                            </form>
                        </div>
                    <?php else: ?>
                        <div class="out-of-stock-alert mb-4">
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                This product is currently out of stock
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Action Buttons -->
                    <div class="product-actions mb-4">
                        <div class="d-flex gap-2 flex-wrap">
                            <button class="btn btn-outline-primary" onclick="addToWishlist(<?php echo $product_id; ?>)">
                                <i class="fas fa-heart me-2"></i>Add to Wishlist
                            </button>
                            <button class="btn btn-outline-secondary" onclick="shareProduct()">
                                <i class="fas fa-share-alt me-2"></i>Share
                            </button>
                        </div>
                    </div>

                    <!-- Product Description -->
                    <div class="product-description-card">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">
                                    <i class="fas fa-info-circle me-2"></i>Product Details
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($product['description'])): ?>
                                    <p class="product-description"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                                <?php else: ?>
                                    <p class="text-muted">No detailed description available for this product.</p>
                                <?php endif; ?>

                                <!-- Product Features -->
                                <div class="product-features mt-3">
                                    <h6>Features:</h6>
                                    <ul class="list-unstyled">
                                        <li><i class="fas fa-check text-success me-2"></i>High-quality materials</li>
                                        <li><i class="fas fa-check text-success me-2"></i>Tested for durability</li>
                                        <li><i class="fas fa-check text-success me-2"></i>Satisfaction guaranteed</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Related Products Section -->
<?php if (!empty($related_products)): ?>
    <section class="related-products-section py-5 bg-light">
        <div class="container">
            <div class="section-header text-center mb-5">
                <h2 class="section-title">You Might Also Like</h2>
                <p class="section-subtitle text-muted">Discover similar products from <?php echo htmlspecialchars($product['category_name']); ?></p>
            </div>

            <div class="row g-3">
                <?php foreach ($related_products as $related_product): ?>
                    <div class="col-lg-3 col-md-4 col-sm-6">
                        <div class="card product-card h-100 shadow-sm">
                            <div class="product-image-container">
                                <img src="assets/images/<?php echo htmlspecialchars($related_product['image'] ?? 'placeholder.jpg'); ?>"
                                     class="card-img-top product-image"
                                     alt="<?php echo htmlspecialchars($related_product['name']); ?>"
                                     style="height: 140px; object-fit: cover;">
                                <?php if ($related_product['stock_quantity'] <= 0): ?>
                                    <div class="out-of-stock-overlay">
                                        <span class="out-of-stock-text" style="font-size: 0.75rem;">Out of Stock</span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="card-body d-flex flex-column" style="padding: 0.75rem;">
                                <div class="product-info">
                                    <h6 class="card-title product-title" style="font-size: 0.85rem; margin-bottom: 0.25rem; line-height: 1.2;">
                                        <a href="product.php?id=<?php echo $related_product['product_id']; ?>"
                                           class="text-decoration-none text-dark">
                                            <?php echo htmlspecialchars($related_product['name']); ?>
                                        </a>
                                    </h6>
                                    <p class="text-muted small mb-1" style="font-size: 0.75rem;"><?php echo htmlspecialchars($related_product['category_name']); ?></p>
                                    <div class="product-price" style="font-size: 1rem; font-weight: 700;"><?php echo formatCurrency($related_product['price']); ?></div>
                                </div>

                                <div class="mt-auto">
                                    <?php if ($related_product['stock_quantity'] > 0): ?>
                                        <button class="btn btn-primary w-100 related-add-to-cart-btn"
                                                data-product-id="<?php echo $related_product['product_id']; ?>"
                                                data-product-name="<?php echo htmlspecialchars($related_product['name']); ?>"
                                                style="padding: 0.4rem 0.6rem; font-size: 0.8rem;">
                                            <i class="fas fa-cart-plus me-1"></i>Add
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-secondary w-100" disabled style="padding: 0.4rem 0.6rem; font-size: 0.8rem;">
                                            <i class="fas fa-times me-1"></i>Out
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>

<script>
// Product-specific Add to Cart functionality
document.addEventListener('DOMContentLoaded', function() {
    let isProcessing = false;

    // Handle related product Add to Cart buttons
    document.querySelectorAll('.related-add-to-cart-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            if (isProcessing) return;
            isProcessing = true;

            const productId = this.getAttribute('data-product-id');
            const productName = this.getAttribute('data-product-name');

            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Adding...';

            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `add_to_cart=1&product_id=${productId}&quantity=1`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success', 2000);
                } else {
                    showToast(data.message, 'danger', 2000);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error adding product to cart', 'danger', 2000);
            })
            .finally(() => {
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-cart-plus me-2"></i>Add to Cart';
                isProcessing = false;
            });
        });
    });

    // Handle main product form submission
    const addToCartForm = document.getElementById('add-to-cart-form');
    const addToCartBtn = document.getElementById('add-to-cart-btn');

    if (addToCartForm) {
        addToCartForm.addEventListener('submit', function(e) {
            e.preventDefault();

            if (isProcessing) return;
            isProcessing = true;

            addToCartBtn.disabled = true;
            addToCartBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Adding...';

            const formData = new FormData(this);

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success', 2000);
                } else {
                    showToast(data.message, 'danger', 2000);
                }

                addToCartBtn.disabled = false;
                addToCartBtn.innerHTML = '<i class="fas fa-cart-plus me-2"></i>Add to Cart';
                isProcessing = false;
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error adding product to cart', 'danger', 2000);

                addToCartBtn.disabled = false;
                addToCartBtn.innerHTML = '<i class="fas fa-cart-plus me-2"></i>Add to Cart';
                isProcessing = false;
            });
        });
    }
});

// Wishlist functionality
function addToWishlist(productId) {
    fetch('ajax/add_to_wishlist.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${productId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success', 2000);
        } else {
            showToast(data.message, 'warning', 2000);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error adding to wishlist', 'danger', 2000);
    });
}

// Share functionality
function shareProduct() {
    if (navigator.share) {
        navigator.share({
            title: document.title,
            url: window.location.href
        }).catch(console.error);
    } else {
        // Fallback: copy to clipboard
        navigator.clipboard.writeText(window.location.href).then(() => {
            showToast('Product link copied to clipboard!', 'success', 2000);
        }).catch(() => {
            showToast('Could not copy link', 'warning', 2000);
        });
    }
}

// Update product quantity
function updateQuantity(productId, change) {
    const quantityInput = document.querySelector(`#quantity-${productId}`);
    if (quantityInput) {
        let currentQuantity = parseInt(quantityInput.value);
        currentQuantity += change;

        if (currentQuantity < 1) currentQuantity = 1;
        if (currentQuantity > 99) currentQuantity = 99;

        quantityInput.value = currentQuantity;
    }
}

// Ensure only one footer exists
document.addEventListener('DOMContentLoaded', function() {
    // Remove any duplicate footers after page loads
    const footers = document.querySelectorAll('footer');
    if (footers.length > 1) {
        // Keep only the last footer (main-footer)
        for (let i = 0; i < footers.length - 1; i++) {
            footers[i].remove();
        }
    }
});
</script>

<style>
/* Product Page Styles - Beige Theme Integration */
.product-details-section {
    background: linear-gradient(135deg, #f5f5dc 0%, rgba(245, 245, 220, 0.9) 100%);
    min-height: 100vh;
}

.product-gallery {
    position: sticky;
    top: 2rem;
}

.product-main-image {
    width: 100%;
    height: 320px;
    object-fit: cover;
    cursor: pointer;
    transition: transform 0.3s ease;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(139, 69, 19, 0.15);
}

.product-main-image:hover {
    transform: scale(1.02);
}

.product-info-card {
    background: rgba(255, 255, 255, 0.95);
    padding: 1.5rem;
    border-radius: 16px;
    box-shadow: 0 8px 32px rgba(139, 69, 19, 0.1);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(139, 69, 19, 0.1);
}

.product-category-badge {
    display: inline-block;
    background: linear-gradient(135deg, #8b4513, #d2b48c);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.product-title {
    font-size: 1.75rem;
    font-weight: 700;
    color: #2c1810;
    line-height: 1.2;
    margin-bottom: 0;
}

.product-price {
    font-size: 2rem;
    font-weight: 800;
    color: #28a745;
    margin-bottom: 0.5rem;
}

.stock-badge {
    font-size: 0.875rem;
    padding: 0.5rem 1rem;
}

.quantity-selector {
    min-width: 150px;
}

.quantity-input-group {
    border-radius: 8px;
    overflow: hidden;
}

.quantity-input {
    border: 2px solid rgba(139, 69, 19, 0.2);
    font-weight: 600;
    font-size: 1.1rem;
}

.quantity-btn {
    border: 2px solid rgba(139, 69, 19, 0.2);
    font-weight: 600;
    background: rgba(139, 69, 19, 0.05);
}

.add-to-cart-btn-container {
    flex-grow: 1;
}

.add-to-cart-btn {
    padding: 1rem 2rem;
    font-size: 1.1rem;
    font-weight: 600;
    border-radius: 8px;
    transition: all 0.3s ease;
    background: linear-gradient(135deg, #28a745, #20c997);
    border: none;
    color: white;
}

.add-to-cart-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
    background: linear-gradient(135deg, #218838, #17a2b8);
}

.out-of-stock-alert {
    text-align: center;
}

.product-actions .btn {
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.3s ease;
    border: 2px solid #8b4513;
    color: #8b4513;
    background: transparent;
}

.product-actions .btn:hover {
    transform: translateY(-1px);
    background: #8b4513;
    color: white;
}

.product-description-card .card-header {
    border-radius: 12px 12px 0 0 !important;
    background: rgba(139, 69, 19, 0.1);
    border-bottom: 1px solid rgba(139, 69, 19, 0.2);
}

.product-description {
    line-height: 1.6;
    color: #495057;
}

.product-features h6 {
    color: #2c1810;
    font-weight: 600;
    margin-bottom: 1rem;
}

.product-features li {
    padding: 0.25rem 0;
    color: #495057;
}

.product-features li i {
    color: #28a745 !important;
}

/* Related Products Section */
.related-products-section {
    background: rgba(245, 245, 220, 0.3);
    border-top: 1px solid rgba(139, 69, 19, 0.1);
}

.section-title {
    font-size: 2rem;
    font-weight: 700;
    color: #2c1810;
    margin-bottom: 1rem;
}

.section-subtitle {
    font-size: 1.1rem;
    max-width: 600px;
    margin: 0 auto;
    color: #6c757d;
}

.product-image-container {
    position: relative;
    overflow: hidden;
    border-radius: 12px 12px 0 0;
}

.out-of-stock-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(220, 53, 69, 0.9);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 2;
}

.out-of-stock-text {
    color: white;
    font-weight: 600;
    font-size: 1rem;
    text-transform: uppercase;
    letter-spacing: 1px;
}

/* Breadcrumb */
.breadcrumb {
    background: rgba(245, 245, 220, 0.8);
    border-radius: 8px;
    padding: 1rem 1.5rem;
    border: 1px solid rgba(139, 69, 19, 0.1);
}

.breadcrumb-item a {
    color: #8b4513;
    text-decoration: none;
}

.breadcrumb-item a:hover {
    text-decoration: underline;
    color: #654321;
}

.breadcrumb-item.active {
    color: #495057;
    font-weight: 500;
}

/* Enhanced Modal Styling */
.modal-content {
    border-radius: 12px;
    border: 1px solid rgba(139, 69, 19, 0.2);
}

.modal-header {
    background: rgba(245, 245, 220, 0.9);
    border-bottom: 1px solid rgba(139, 69, 19, 0.1);
    border-radius: 12px 12px 0 0;
}

.modal-title {
    color: #2c1810;
    font-weight: 600;
}

/* Responsive Design */
@media (max-width: 768px) {
    .product-details-section {
        padding: 2rem 0;
    }

    .product-title {
        font-size: 1.5rem;
    }

    .product-price {
        font-size: 2rem;
    }

    .add-to-cart-section form {
        flex-direction: column;
        gap: 1rem;
    }

    .add-to-cart-section form > div {
        width: 100%;
    }

    .product-actions {
        flex-direction: column;
    }

    .product-actions .btn {
        width: 100%;
    }

    .product-main-image {
        height: 300px;
    }
}

/* Animation for page load */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.product-details-section > .container > .row {
    animation: fadeInUp 0.6s ease-out;
}

/* Enhanced shadows with beige theme */
.product-info-card,
.product-description-card .card,
.related-products-section .card {
    box-shadow: 0 4px 20px rgba(139, 69, 19, 0.08);
}

/* Hover effects for related products */
.related-products-section .card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.related-products-section .card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(139, 69, 19, 0.15);
}

<?php include 'includes/footer.php'; ?>
