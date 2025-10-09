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
    
    <style>
        .cart-container {
            background: linear-gradient(135deg, var(--gray-50) 0%, var(--white) 100%);
            min-height: 100vh;
        }
        
        .cart-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
        }
        
        .cart-item-card {
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
            overflow: hidden;
        }
        
        .cart-item-card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }
        
        .product-image {
            width: 100%;
            height: 120px;
            object-fit: cover;
            border-radius: var(--radius);
        }
        
        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .quantity-btn {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            border: 1px solid var(--gray-300);
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }
        
        .quantity-btn:hover {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .quantity-display {
            min-width: 40px;
            text-align: center;
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .price-display {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--success-color);
        }
        
        .summary-card {
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--gray-200);
        }
        
        .empty-cart {
            text-align: center;
            padding: 4rem 2rem;
        }
        
        .empty-cart-icon {
            font-size: 4rem;
            color: var(--gray-400);
            margin-bottom: 1.5rem;
        }
        
        .btn-custom {
            border-radius: var(--radius);
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            transition: all 0.3s ease;
        }
        
        .btn-primary-custom {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
            color: white;
        }
        
        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .remove-btn {
            color: var(--danger-color);
            background: none;
            border: none;
            padding: 0.5rem;
            border-radius: var(--radius);
            transition: all 0.2s ease;
        }
        
        .remove-btn:hover {
            background: var(--danger-color);
            color: white;
        }
        
        .stock-warning {
            background: var(--warning-color);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: var(--radius-sm);
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .category-badge {
            background: var(--gray-100);
            color: var(--gray-600);
            padding: 0.25rem 0.75rem;
            border-radius: var(--radius);
            font-size: 0.8rem;
            font-weight: 500;
        }
    </style>
</head>
<body class="cart-container">

<?php include 'includes/navbar.php'; ?>

<div class="container py-5">
    <!-- Cart Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="cart-header p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="mb-2">
                            <i class="fas fa-shopping-cart me-3"></i>Your Shopping Cart
                        </h1>
                        <p class="mb-0 opacity-75">Review your items and proceed to checkout</p>
                    </div>
                    <div class="text-end">
                        <div class="badge bg-white text-dark fs-6 px-3 py-2">
                            <i class="fas fa-box me-2"></i><?php echo count($cart_items); ?> items
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (empty($cart_items)): ?>
        <!-- Empty Cart State -->
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="empty-cart">
                            <div class="empty-cart-icon">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                            <h3 class="text-muted mb-3">Your cart is empty</h3>
                            <p class="text-muted mb-4">Looks like you haven't added any items to your cart yet.</p>
                            <a href="shop.php" class="btn btn-primary-custom btn-custom">
                                <i class="fas fa-shopping-bag me-2"></i>Start Shopping
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="row">
            <!-- Cart Items -->
            <div class="col-lg-8">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="mb-0">Cart Items</h4>
                    <form method="POST" action="" class="d-inline">
                        <input type="hidden" name="clear_all" value="1">
                        <button type="submit" class="btn btn-outline-danger btn-sm" 
                                onclick="return confirm('Are you sure you want to clear all items from your cart?')">
                            <i class="fas fa-trash me-2"></i>Clear All
                        </button>
                    </form>
                </div>

                <div class="row g-4">
                    <?php foreach ($cart_items as $item): ?>
                        <div class="col-12">
                            <div class="cart-item-card">
                                <div class="card-body p-4">
                                    <div class="row align-items-center">
                                        <!-- Product Image -->
                                        <div class="col-md-2">
                                            <div class="position-relative">
                                                <img src="assets/images/<?php echo htmlspecialchars($item['image'] ?? 'placeholder.jpg'); ?>"
                                                     class="product-image" 
                                                     alt="<?php echo htmlspecialchars($item['name']); ?>">
                                                <?php if ($item['quantity'] > $item['stock_quantity']): ?>
                                                    <div class="position-absolute top-0 end-0 m-2">
                                                        <span class="stock-warning">
                                                            <i class="fas fa-exclamation-triangle me-1"></i>Limited Stock
                                                        </span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <!-- Product Info -->
                                        <div class="col-md-4">
                                            <h5 class="mb-2">
                                                <a href="product.php?id=<?php echo $item['product_id']; ?>" 
                                                   class="text-decoration-none text-dark fw-bold">
                                                    <?php echo htmlspecialchars($item['name']); ?>
                                                </a>
                                            </h5>
                                            <div class="mb-2">
                                                <span class="category-badge">
                                                    <i class="fas fa-tag me-1"></i><?php echo htmlspecialchars($item['category_name']); ?>
                                                </span>
                                            </div>
                                            <div class="price-display">
                                                <?php echo formatCurrency($item['price']); ?>
                                            </div>
                                            <?php if ($item['quantity'] > $item['stock_quantity']): ?>
                                                <div class="mt-2">
                                                    <span class="stock-warning">
                                                        <i class="fas fa-exclamation-triangle me-1"></i>
                                                        Only <?php echo $item['stock_quantity']; ?> left in stock
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Quantity Controls -->
                                        <div class="col-md-3">
                                            <div class="quantity-controls justify-content-center">
                                                <form method="POST" action="" class="d-inline">
                                                    <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                                    <input type="hidden" name="quantity" value="<?php echo max(1, $item['quantity'] - 1); ?>">
                                                    <input type="hidden" name="update_cart" value="1">
                                                    <button type="submit" class="quantity-btn" 
                                                            <?php echo $item['quantity'] <= 1 ? 'disabled' : ''; ?>>
                                                        <i class="fas fa-minus"></i>
                                                    </button>
                                                </form>

                                                <span class="quantity-display"><?php echo $item['quantity']; ?></span>

                                                <form method="POST" action="" class="d-inline">
                                                    <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                                    <input type="hidden" name="quantity" value="<?php echo $item['quantity'] + 1; ?>">
                                                    <input type="hidden" name="update_cart" value="1">
                                                    <button type="submit" class="quantity-btn">
                                                        <i class="fas fa-plus"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>

                                        <!-- Price & Actions -->
                                        <div class="col-md-3 text-end">
                                            <div class="price-display mb-3">
                                                <?php echo formatCurrency($item['price'] * $item['quantity']); ?>
                                            </div>
                                            <form method="POST" action="" class="d-inline">
                                                <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                                <input type="hidden" name="remove_item" value="1">
                                                <button type="submit" class="remove-btn" 
                                                        onclick="return confirm('Remove this item from cart?')">
                                                    <i class="fas fa-trash me-1"></i>Remove
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="col-lg-4">
                <div class="summary-card p-4">
                    <h5 class="mb-4">
                        <i class="fas fa-receipt me-2"></i>Order Summary
                    </h5>
                    
                    <div class="d-flex justify-content-between mb-3">
                        <span>Subtotal (<?php echo count($cart_items); ?> items):</span>
                        <span class="fw-bold"><?php echo formatCurrency($total); ?></span>
                    </div>
                    
                    <div class="d-flex justify-content-between mb-3">
                        <span>Shipping:</span>
                        <span class="text-success">Free</span>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between mb-4">
                        <span class="fs-5 fw-bold">Total:</span>
                        <span class="fs-5 fw-bold text-success"><?php echo formatCurrency($total); ?></span>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <a href="checkout.php" class="btn btn-primary-custom btn-custom">
                            <i class="fas fa-credit-card me-2"></i>Proceed to Checkout
                        </a>
                        <a href="shop.php" class="btn btn-outline-primary btn-custom">
                            <i class="fas fa-arrow-left me-2"></i>Continue Shopping
                        </a>
                    </div>
                    
                    <div class="mt-4 p-3 bg-light rounded">
                        <h6 class="mb-2">
                            <i class="fas fa-shield-alt me-2 text-success"></i>Secure Checkout
                        </h6>
                        <p class="small text-muted mb-0">
                            Your payment information is encrypted and secure. We never store your payment details.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>