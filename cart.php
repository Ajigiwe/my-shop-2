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
                                                        <button type="submit" class="btn btn-outline-danger btn-sm" style="border-radius: 8px; padding: 6px 12px; font-weight: 600; font-size: 0.9rem;"
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
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                                    <div class="d-flex gap-3 flex-wrap flex-grow-1 ps-2 ps-md-3 py-2">
                                        <a href="shop.php" class="btn btn-outline-primary btn-sm" style="border-radius: 8px; padding: 4px 10px; font-weight: 600; font-size: 0.9rem;">
                                            <i class="fas fa-arrow-left me-2"></i>Continue Shopping
                                        </a>

                                        <?php if (!empty($cart_items)): ?>
                                            <form method="POST" action="" class="d-inline">
                                                <input type="hidden" name="clear_all" value="1">
                                                <button type="submit" class="btn btn-outline-danger btn-sm" style="border-radius: 8px; padding: 6px 12px; font-weight: 600; font-size: 0.9rem;"
                                                        onclick="return confirm('Clear all items from cart?')">
                                                    <i class="fas fa-trash me-2"></i>Clear All
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>

                                    <?php if (!empty($cart_items)): ?>
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="text-end">
                                                <h5 class="mb-0">
                                                    <span class="text-muted">Total:</span>
                                                    <span class="ms-2 fw-bold text-success"><?php echo formatCurrency($total); ?></span>
                                                </h5>
                                                <small class="text-muted">Including all applicable taxes</small>
                                            </div>
                                            <div class="d-flex flex-column gap-2">
                                                <a href="checkout.php" class="btn btn-success" style="border-radius: 8px; padding: 10px 24px; font-weight: 600; white-space: nowrap;">
                                                    <i class="fas fa-credit-card me-2"></i>Proceed to Checkout
                                                </a>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Order Summary removed as requested -->
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

// Enhance +/- buttons: detect clicks on minus/plus icon buttons, update the input, then submit the form
document.addEventListener('click', function (e) {
    const btn = e.target.closest('button');
    if (!btn) return;

    // Identify minus/plus by icon classes to avoid markup changes
    const icon = btn.querySelector('i');
    if (!icon) return;

    let change = 0;
    if (icon.classList.contains('fa-minus')) change = -1;
    if (icon.classList.contains('fa-plus')) change = 1;
    if (change === 0) return;

    // If this button is inside its own form with hidden quantity fields, submit it directly
    const directForm = btn.closest('form');
    if (directForm && directForm.querySelector('input[name="update_quantity"]')) {
        e.preventDefault();
        // Ensure server recognizes this as a cart update (matches PHP handler that checks update_cart)
        if (!directForm.querySelector('input[name="update_cart"]')) {
            const flag = document.createElement('input');
            flag.type = 'hidden';
            flag.name = 'update_cart';
            flag.value = '1';
            directForm.appendChild(flag);
        }
        try { directForm.submit(); } catch (_) {}
        return;
    }

    // Fallback: find a visible quantity input and adjust it, then submit a related form
    const container = btn.closest('.card, .row') || document;
    const qtyInput = container.querySelector('input[id^="cart-quantity-"]');
    if (qtyInput) {
        e.preventDefault();
        const idMatch = qtyInput.id.match(/cart-quantity-(\d+)/);
        const productId = idMatch ? parseInt(idMatch[1], 10) : null;
        if (productId) {
            updateCartQuantity(productId, change);
            const form = qtyInput.closest('form') || btn.closest('form');
            if (form) {
                try { form.submit(); } catch (_) {}
            }
        }
    }
});

// Make +/- buttons smaller without changing PHP/HTML (override inline via JS)
document.addEventListener('DOMContentLoaded', function () {
    const tweakQtyButtons = () => {
        document.querySelectorAll('button').forEach(btn => {
            const icon = btn.querySelector('i');
            if (!icon) return;
            if (icon.classList.contains('fa-minus') || icon.classList.contains('fa-plus')) {
                // Add reliable classes for styling and targeting
                if (icon.classList.contains('fa-minus')) btn.classList.add('qty-minus');
                if (icon.classList.contains('fa-plus')) btn.classList.add('qty-plus');
                btn.classList.add('qty-compact');
                // Fallback inline adjustments in case CSS is cached
                if (!btn.style.width) btn.style.width = '24px';
                if (!btn.style.height) btn.style.height = '24px';
                btn.style.padding = '0';
                btn.style.display = 'flex';
                btn.style.alignItems = 'center';
                btn.style.justifyContent = 'center';
                icon.style.fontSize = '0.75rem';
            }
        });
    };
    tweakQtyButtons();
    // In case cart content updates dynamically later
    document.addEventListener('ajaxComplete', tweakQtyButtons, true);

    // Lean order summary: hide shipping row and items count (non-breaking)
    document.querySelectorAll('.cart-summary').forEach(summary => {
        // Hide shipping row (looks like a d-flex row containing a label 'Shipping:')
        const shippingRow = Array.from(summary.querySelectorAll('.d-flex'))
            .find(row => row.textContent && row.textContent.trim().startsWith('Shipping:'));
        if (shippingRow) shippingRow.style.display = 'none';

        // Hide items count small text
        const itemsCount = Array.from(summary.querySelectorAll('small'))
            .find(s => /items in your cart/i.test(s.textContent || ''));
        if (itemsCount) itemsCount.style.display = 'none';
    });
});
</script>

<?php include 'includes/footer.php'; ?>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
