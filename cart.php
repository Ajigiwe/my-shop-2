<?php
/**
 * Storefront: Shopping Cart
 * - Requires login; loads cart items from DB for the current user
 * - Supports quantity updates and item removal
 * - Computes subtotal/total and provides checkout CTA
 */
// Include database connection
require_once 'includes/db.php';

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = 'cart.php';
    header('Location: login.php');
    exit();
}

// Set page title
$page_title = 'Shopping Cart';

// Handle cart updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_cart'])) {
        $product_id = (int)$_POST['product_id'];
        $quantity = (int)$_POST['quantity'];

        if ($quantity > 0) {
            try {
                $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
                $stmt->execute([$quantity, $_SESSION['user_id'], $product_id]);
            } catch(PDOException $e) {
                error_log("Error updating cart: " . $e->getMessage());
            }
        }
    } elseif (isset($_POST['remove_item'])) {
        $product_id = (int)$_POST['product_id'];

        try {
            $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$_SESSION['user_id'], $product_id]);
        } catch(PDOException $e) {
            error_log("Error removing from cart: " . $e->getMessage());
        }
    } elseif (isset($_POST['clear_all'])) {
        try {
            $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
        } catch(PDOException $e) {
            error_log("Error clearing cart: " . $e->getMessage());
        }
    }

    // Redirect to refresh the page and show updated cart
    header('Location: cart.php');
    exit();
}

// Get cart items first so they're available for the badge
$cart_items = [];
$total = 0;

try {
    $stmt = $pdo->prepare("SELECT c.*, p.name, p.price, p.image, p.stock_quantity, cat.category_name
                          FROM cart c
                          JOIN products p ON c.product_id = p.product_id
                          JOIN categories cat ON p.category_id = cat.category_id
                          WHERE c.user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    // Compute total
    foreach ($cart_items as $item) {
        $total += $item['price'] * $item['quantity'];
    }
} catch(PDOException $e) {
    error_log("Error fetching cart: " . $e->getMessage());
    $cart_items = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - ASO Online Market</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="container py-4">
    <!-- Full Width Cart Section -->
    <div class="row justify-content-center">
        <div class="col-12">
            <div class="card shadow-lg border-0">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h2 class="mb-0">
                            <i class="fas fa-shopping-cart me-2"></i>Your Cart
                        </h2>
                        <span class="badge bg-primary fs-6 px-3 py-2">
                            <?php echo count($cart_items); ?> items
                        </span>
                    </div>
                </div>

                <div class="card-body p-0">
                    <?php if (empty($cart_items)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-shopping-cart fa-5x text-muted mb-4"></i>
                            <h3 class="text-muted">Your cart is empty</h3>
                            <p class="text-muted mb-4">Add some products to get started!</p>
                            <a href="shop.php" class="btn btn-primary px-4" style="border-radius: 8px; font-weight: 600; font-size: 0.9rem;">
                                <i class="fas fa-shopping-bag me-2"></i>Continue Shopping
                            </a>
                        </div>
                    <?php else: ?>
                        <!-- Cart Items as Cards -->
                        <div class="row g-3">
                            <?php foreach ($cart_items as $item): ?>
                                <div class="col-12">
                                    <div class="card border-0 shadow-sm">
                                        <div class="card-body">
                                            <div class="row align-items-center">
                                                <!-- Product Image & Info -->
                                                <div class="col-md-2">
                                                    <div style="position: relative;">
                                                        <img src="assets/images/<?php echo htmlspecialchars($item['image'] ?? 'placeholder.jpg'); ?>"
                                                             class="img-fluid rounded" alt="<?php echo htmlspecialchars($item['name']); ?>"
                                                             style="max-height: 120px; width: 100%; object-fit: cover; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                                        <?php if ($item['quantity'] > $item['stock_quantity']): ?>
                                                            <div style="position: absolute; top: 5px; right: 5px;">
                                                                <span class="badge bg-warning text-dark">Limited Stock</span>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>

                                                <div class="col-md-4">
                                                    <h6 class="mb-2" style="font-weight: 600; color: var(--gray-800); font-size: 1.1rem;">
                                                        <a href="product.php?id=<?php echo $item['product_id']; ?>" class="text-decoration-none" style="color: var(--primary-color); transition: color 0.3s ease;">
                                                            <?php echo htmlspecialchars($item['name']); ?>
                                                        </a>
                                                    </h6>
                                                    <p class="text-muted small mb-2" style="color: var(--gray-600);">
                                                        <i class="fas fa-tag me-1"></i><?php echo htmlspecialchars($item['category_name']); ?>
                                                    </p>
                                                    <div class="d-flex align-items-center">
                                                        <span class="fw-bold me-3" style="color: var(--success-color); font-size: 1.2rem;"><?php echo formatCurrency($item['price']); ?></span>
                                                        <?php if ($item['quantity'] > $item['stock_quantity']): ?>
                                                            <span class="badge bg-warning text-dark">
                                                                <i class="fas fa-exclamation-triangle me-1"></i>Only <?php echo $item['stock_quantity']; ?> left
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>

                                                <!-- Quantity Controls -->
                                                <div class="col-md-3 text-center">
                                                    <form method="POST" action="" class="d-inline">
                                                        <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                                        <input type="hidden" name="update_cart" value="1">

                                                        <div class="d-flex align-items-center justify-content-center mb-2">
                                                            <form method="POST" action="" class="d-inline">
                                                                <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                                                <input type="hidden" name="quantity" value="<?php echo max(0, $item['quantity'] - 1); ?>">
                                                                <input type="hidden" name="update_quantity" value="1">
                                                                <button type="submit" class="btn btn-outline-secondary btn-sm" style="width: 28px; height: 28px; padding: 0; display: flex; align-items: center; justify-content: center;">
                                                                    <i class="fas fa-minus" style="font-size: 0.8rem;"></i>
                                                                </button>
                                                            </form>

                                                            <span class="mx-3 fw-bold" style="color: var(--primary-color); font-size: 1.2rem; min-width: 40px; text-align: center;"><?php echo $item['quantity']; ?></span>

                                                            <form method="POST" action="" class="d-inline">
                                                                <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                                                <input type="hidden" name="quantity" value="<?php echo $item['quantity'] + 1; ?>">
                                                                <input type="hidden" name="update_quantity" value="1">
                                                                <button type="submit" class="btn btn-outline-secondary btn-sm" style="width: 28px; height: 28px; padding: 0; display: flex; align-items: center; justify-content: center;">
                                                                    <i class="fas fa-plus" style="font-size: 0.8rem;"></i>
                                                                </button>
                                                            </form>
                                                        </div>

                                                        <button type="submit" class="btn btn-primary btn-sm" style="padding: 4px 8px; font-size: 0.8rem;">Update</button>
                                                    </form>
                                                </div>

                                                <!-- Subtotal -->
                                                <div class="col-md-2 text-center">
                                                    <span class="fw-bold text-success fs-5">
                                                        <?php echo formatCurrency($item['price'] * min($item['quantity'], $item['stock_quantity'])); ?>
                                                    </span>
                                                </div>

                                                <!-- Actions -->
                                                <div class="col-md-1 text-center">
                                                    <form method="POST" action="">
                                                        <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                                        <input type="hidden" name="remove_item" value="1">
                                                        <button type="submit" class="btn btn-outline-danger btn-sm" style="padding: 4px 8px; font-size: 0.8rem;"
                                                                onclick="return confirm('Remove this item from cart?')">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Cart Summary & Actions (moved outside cards) -->
                        <div class="row mt-4">
                            <div class="col-md-6">
                                <div class="d-flex gap-3 flex-wrap">
                                    <a href="shop.php" class="btn btn-outline-primary" style="padding: 8px 16px; font-size: 0.9rem;">
                                        <i class="fas fa-arrow-left me-2"></i>Continue Shopping
                                    </a>

                                    <?php if (!empty($cart_items)): ?>
                                        <form method="POST" action="" class="d-inline">
                                            <input type="hidden" name="clear_all" value="1">
                                            <button type="submit" class="btn btn-outline-danger" style="padding: 8px 16px; font-size: 0.9rem;"
                                                    onclick="return confirm('Clear all items from cart?')">
                                                <i class="fas fa-trash me-2"></i>Clear All
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div style="background: linear-gradient(145deg, #ffffff, #f8f9fa); border-radius: 15px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.08);">
                                    <div class="mb-3">
                                        <h3 class="mb-2" style="color: var(--gray-800); font-weight: 700;">
                                            <i class="fas fa-receipt me-2" style="color: var(--primary-color);"></i>Order Summary
                                        </h3>
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span style="color: var(--gray-600);">Subtotal (<?php echo count($cart_items); ?> items):</span>
                                            <span class="fw-bold" style="color: var(--primary-color); font-size: 1.1rem;"><?php echo formatCurrency($total); ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span style="color: var(--gray-600);">Shipping:</span>
                                            <span class="text-success fw-bold">
                                                <i class="fas fa-truck me-1"></i>Free
                                            </span>
                                        </div>
                                        <hr style="border-color: var(--gray-300);">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span style="font-weight: 700; color: var(--gray-800); font-size: 1.2rem;">Total:</span>
                                            <span class="fw-bold fs-3" style="color: var(--success-color);"><?php echo formatCurrency($total); ?></span>
                                        </div>
                                        <small class="text-muted d-block text-center mt-2"><?php echo count($cart_items); ?> items in your cart</small>
                                    </div>

                                    <?php if (!empty($cart_items)): ?>
                                        <a href="checkout.php" class="btn w-100" style="background: linear-gradient(135deg, var(--success-color), #28a745); color: white; border: none; border-radius: 8px; padding: 10px; font-weight: 700; font-size: 0.9rem; transition: all 0.3s ease;">
                                            <i class="fas fa-credit-card me-2"></i>Proceed to Checkout
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function updateCartQuantity(productId, change) {
    const input = document.getElementById(`cart-quantity-${productId}`);
    let currentQuantity = parseInt(input.value);
    currentQuantity += change;

    if (currentQuantity < 1) currentQuantity = 1;
    if (currentQuantity > parseInt(input.max)) currentQuantity = parseInt(input.max);

    input.value = currentQuantity;
}
</script>

<?php include 'includes/footer.php'; ?>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
