<?php
/**
 * Paystack Verification & Order Creation
 * - Verifies Paystack transaction via server-side API
 * - Creates order and items on success, updates stock, clears cart
 */
require_once 'includes/db.php';
require_once 'includes/paystack_config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = 'user/checkout.php';
    header('Location: login.php');
    exit();
}

$reference = $_GET['reference'] ?? '';
if (!$reference) {
    header('Location: user/checkout.php');
    exit();
}

// Must have pending checkout state from checkout.php
$pending = $_SESSION['pending_checkout'] ?? null;
if (!$pending || ($pending['payment_method'] ?? '') !== 'paystack') {
    header('Location: user/checkout.php');
    exit();
}

// 1) Verify with Paystack
$verify = paystackVerify($reference);
if (!$verify['success']) {
    error_log('Paystack verify failed: ' . $verify['error']);
    $_SESSION['flash_error'] = 'Payment verification failed: ' . htmlspecialchars($verify['error']);
    header('Location: user/checkout.php');
    exit();
}
$data = $verify['data'];

// Expect successful status
$status = $data['status'] ?? '';
$currency = $data['currency'] ?? '';
$amount_kobo = (int)($data['amount'] ?? 0); // Paystack amount in lowest unit

if ($status !== 'success' || strtoupper($currency) !== 'GHS' || $amount_kobo <= 0) {
    $_SESSION['flash_error'] = 'Invalid payment response. Please contact support.';
    header('Location: user/checkout.php');
    exit();
}

// 2) Get cart from session and compute total
$total = 0;
$cart_items = [];

// Get cart items from session
if (!isset($_SESSION['cart']['items']) || empty($_SESSION['cart']['items'])) {
    $_SESSION['flash_error'] = 'Your cart is empty.';
    header('Location: cart.php');
    exit();
}

$session_cart = $_SESSION['cart']['items'];
$product_ids = array_keys($session_cart);

// Get product details from database
try {
    $placeholders = rtrim(str_repeat('?,', count($product_ids)), ',');
    $stmt = $pdo->prepare("SELECT product_id, name, price, image, stock_quantity FROM products WHERE product_id IN ($placeholders)");
    $stmt->execute($product_ids);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Merge session cart with product details
    foreach ($products as $product) {
        $product_id = $product['product_id'];
        if (isset($session_cart[$product_id])) {
            $quantity = $session_cart[$product_id]['quantity'];
            $price = (float)$product['price'];
            
            $cart_items[] = [
                'product_id' => $product_id,
                'name' => $product['name'],
                'price' => $price,
                'quantity' => $quantity,
                'image' => $product['image']
            ];
            
            $total += $price * $quantity;
        }
    }
} catch (PDOException $e) {
    error_log('Paystack verify cart fetch error: ' . $e->getMessage());
    $_SESSION['flash_error'] = 'Error processing your cart.';
    header('Location: user/checkout.php');
    exit();
}

if (empty($cart_items)) {
    $_SESSION['flash_error'] = 'Your cart is empty.';
    header('Location: cart.php');
    exit();
}

// Compare amounts (convert to pesewas)
$expected_amount = (int) round($total * 100);
if ($expected_amount !== $amount_kobo) {
    // Allow small difference? For simplicity, require exact match
    $_SESSION['flash_error'] = 'Payment amount mismatch. Expected ' . formatCurrency($total) . '.';
    header('Location: user/checkout.php');
    exit();
}

// 3) Create order atomically
$shipping_address = $pending['shipping_address'] ?? '';
$billing_address = $pending['billing_address'] ?? '';

try {
    $pdo->beginTransaction();

    // Create order
    $stmt = $pdo->prepare('INSERT INTO orders (user_id, total_amount, payment_method, shipping_address, billing_address, status) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$_SESSION['user_id'], $total, 'paystack', $shipping_address, $billing_address, 'processing']);
    $order_id = $pdo->lastInsertId();

    // Add items + decrement stock
    $stmtItem = $pdo->prepare('INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)');
    $stmtStock = $pdo->prepare('UPDATE products SET stock_quantity = stock_quantity - ? WHERE product_id = ?');
    foreach ($cart_items as $item) {
        $stmtItem->execute([$order_id, $item['product_id'], $item['quantity'], $item['price']]);
        $stmtStock->execute([$item['quantity'], $item['product_id']]);
    }

    $pdo->commit();

    // Clear session cart and pending checkout
    unset($_SESSION['cart']);
    unset($_SESSION['pending_checkout']);
    
    // Redirect to confirmation page
    header('Location: order_confirmation.php?order_id=' . $order_id);
    exit();

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log('Paystack order creation error: ' . $e->getMessage());
    $_SESSION['flash_error'] = 'Could not complete your order due to a server error.';
    header('Location: user/checkout.php');
    exit();
}
