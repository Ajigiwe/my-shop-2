<?php
/**
 * Checkout Form Test
 */

// Initialize session
session_start();

// Database connection
require_once 'includes/db.php';

// Authentication check
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Check if cart has items
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM cart WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $cart_count = $stmt->fetch()['count'];

    if ($cart_count === 0) {
        header('Location: cart.php');
        exit();
    }
} catch(PDOException $e) {
    error_log("Error checking cart: " . $e->getMessage());
    header('Location: cart.php');
    exit();
}

echo "<h1>Checkout Form Test</h1>";
echo "<p>User ID: " . $_SESSION['user_id'] . "</p>";
echo "<p>Cart items: " . $cart_count . "</p>";

// Test form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h2 style='color: green;'>Form Submitted Successfully!</h2>";
    echo "<pre>";
    echo "POST data: " . print_r($_POST, true);
    echo "</pre>";

    if (isset($_POST['place_order'])) {
        echo "<p style='color: blue;'>Place order button clicked!</p>";
    }
} else {
    echo "<p style='color: orange;'>No form submission detected</p>";
}

echo "<h2>Test Checkout Form:</h2>";
echo "<form method='post' action=''>";
echo "<input type='text' name='shipping_name' value='Test User' required>";
echo "<input type='email' name='shipping_email' value='test@example.com' required>";
echo "<input type='tel' name='shipping_phone' value='1234567890' required>";
echo "<textarea name='shipping_address' required>Test Address</textarea>";
echo "<select name='payment_method' required>";
echo "<option value='paystack'>Paystack</option>";
echo "<option value='cash_on_delivery'>Cash on Delivery</option>";
echo "</select>";
echo "<button type='submit' name='place_order'>Place Order</button>";
echo "</form>";

echo "<br><a href='cart.php'>Back to Cart</a>";
?>
