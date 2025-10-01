<?php
/**
 * Wishlist Page
 * - Display user's wishlist items
 * - Allow removing items from wishlist
 * - Add to cart from wishlist
 */

require_once 'includes/db.php';

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$page_title = 'My Wishlist';

// Handle remove from wishlist
if (isset($_POST['remove_from_wishlist'])) {
    $product_id = (int)$_POST['product_id'];

    try {
        $stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$_SESSION['user_id'], $product_id]);

        $_SESSION['success_message'] = 'Product removed from wishlist!';
        header('Location: wishlist.php');
        exit();
    } catch(PDOException $e) {
        $_SESSION['error_message'] = 'Error removing product from wishlist.';
    }
}

// Handle AJAX add to cart for wishlist items
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
            error_log("Error adding to cart via AJAX: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error adding product to cart']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
    }
    exit();
}

// Get wishlist items
$wishlist_items = [];
try {
    $stmt = $pdo->prepare("
        SELECT w.*, p.name, p.price, p.image, p.stock_quantity, c.category_name
        FROM wishlist w
        JOIN products p ON w.product_id = p.product_id
        JOIN categories c ON p.category_id = c.category_id
        WHERE w.user_id = ?
        ORDER BY w.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $wishlist_items = $stmt->fetchAll();
} catch(PDOException $e) {
    error_log("Error fetching wishlist: " . $e->getMessage());
}

include 'includes/header.php';
?>

<div class="container my-5">
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="fas fa-heart text-danger me-2"></i>My Wishlist</h1>
                <span class="badge bg-primary fs-6"><?php echo count($wishlist_items); ?> items</span>
            </div>

            <?php if (empty($wishlist_items)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-heart-broken text-muted" style="font-size: 4rem;"></i>
                    <h3 class="mt-3 text-muted">Your wishlist is empty</h3>
                    <p class="text-muted">Start adding products you love to your wishlist!</p>
                    <a href="shop.php" class="btn btn-primary">
                        <i class="fas fa-shopping-bag me-2"></i>Continue Shopping
                    </a>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($wishlist_items as $item): ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card product-card h-100">
                                <div class="position-relative">
                                    <img src="<?php echo $item['image'] ?: 'assets/images/placeholder.jpg'; ?>"
                                         class="card-img-top product-image"
                                         alt="<?php echo htmlspecialchars($item['name']); ?>">

                                    <!-- Remove from wishlist button -->
                                    <form method="POST" class="position-absolute top-0 end-0 m-2">
                                        <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                        <button type="submit" name="remove_from_wishlist"
                                                class="btn btn-sm btn-danger rounded-circle"
                                                title="Remove from wishlist">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </form>
                                </div>

                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title product-title">
                                        <?php echo htmlspecialchars($item['name']); ?>
                                    </h5>

                                    <p class="text-muted mb-2">
                                        <small><i class="fas fa-tag me-1"></i><?php echo htmlspecialchars($item['category_name']); ?></small>
                                    </p>

                                    <div class="product-price mb-3">
                                        <?php echo formatCurrency($item['price']); ?>
                                    </div>

                                    <div class="mt-auto">
                                        <?php if ($item['stock_quantity'] > 0): ?>
                                            <button class="btn btn-primary w-100 wishlist-add-to-cart-btn"
                                                    data-product-id="<?php echo $item['product_id']; ?>"
                                                    data-product-name="<?php echo htmlspecialchars($item['name']); ?>">
                                                <i class="fas fa-shopping-cart me-2"></i>Add to Cart
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-secondary w-100" disabled>
                                                <i class="fas fa-times me-2"></i>Out of Stock
                                            </button>
                                        <?php endif; ?>

                                        <a href="product.php?id=<?php echo $item['product_id']; ?>"
                                           class="btn btn-outline-primary w-100 mt-2">
                                            <i class="fas fa-eye me-2"></i>View Details
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Wishlist Actions -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body text-center">
                                <h5>Share Your Wishlist</h5>
                                <p class="text-muted">Let others know what you're wishing for!</p>
                                <div class="d-flex justify-content-center gap-2 flex-wrap">
                                    <button class="btn btn-outline-primary" onclick="shareWishlist('facebook')">
                                        <i class="fab fa-facebook me-2"></i>Facebook
                                    </button>
                                    <button class="btn btn-outline-info" onclick="shareWishlist('twitter')">
                                        <i class="fab fa-twitter me-2"></i>Twitter
                                    </button>
                                    <button class="btn btn-outline-success" onclick="shareWishlist('whatsapp')">
                                        <i class="fab fa-whatsapp me-2"></i>WhatsApp
                                    </button>
                                    <button class="btn btn-outline-secondary" onclick="copyWishlistLink()">
                                        <i class="fas fa-link me-2"></i>Copy Link
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Wishlist page Add to Cart functionality
document.addEventListener('DOMContentLoaded', function() {
    let isProcessing = false; // Prevent multiple simultaneous requests

    // Handle wishlist Add to Cart buttons
    document.querySelectorAll('.wishlist-add-to-cart-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            // Prevent multiple clicks
            if (isProcessing) return;
            isProcessing = true;

            const productId = this.getAttribute('data-product-id');
            const productName = this.getAttribute('data-product-name');

            // Disable button and show loading
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Adding...';

            // Add to cart via AJAX (quantity = 1)
            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `ajax_add_to_cart=1&product_id=${productId}&quantity=1`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success toast with 2 second duration
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
                // Re-enable button and reset processing flag
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-shopping-cart me-2"></i>Add to Cart';
                isProcessing = false;
            });
        });
    });

    // Share wishlist functionality
    window.shareWishlist = function(platform) {
        const url = encodeURIComponent(window.location.href);
        const text = encodeURIComponent('Check out my wishlist at ASO Online Market!');

        let shareUrl = '';
        switch(platform) {
            case 'facebook':
                shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${url}`;
                break;
            case 'twitter':
                shareUrl = `https://twitter.com/intent/tweet?url=${url}&text=${text}`;
                break;
            case 'whatsapp':
                shareUrl = `https://wa.me/?text=${text}%20${url}`;
                break;
        }

        if (shareUrl) {
            window.open(shareUrl, '_blank', 'width=600,height=400');
        }
    };

    window.copyWishlistLink = function() {
        navigator.clipboard.writeText(window.location.href).then(() => {
            showToast('Wishlist link copied to clipboard!', 'success', 2000);
        }).catch(() => {
            showToast('Failed to copy link', 'danger', 2000);
        });
    };
});
</script>

<?php include 'includes/footer.php'; ?>
