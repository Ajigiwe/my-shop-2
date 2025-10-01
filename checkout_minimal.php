<?php
/**
 * Checkout Test - Minimal version to isolate issues
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

// Check cart
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

echo "<h1>Checkout Test</h1>";
echo "<p>User ID: " . $_SESSION['user_id'] . "</p>";
echo "<p>Cart items: " . $cart_count . "</p>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h2 style='color: green;'>Form Submitted!</h2>";
    echo "<pre>POST data: " . print_r($_POST, true) . "</pre>";

    if (isset($_POST['place_order'])) {
        echo "<p style='color: blue;'>Place order button clicked!</p>";

        // Process the order
        $payment_method = $_POST['payment_method'] ?? '';

        if ($payment_method === 'cash_on_delivery') {
            echo "<h3 style='color: green;'>Processing Cash on Delivery...</h3>";
            // Create order and show success
            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare("INSERT INTO orders (user_id, shipping_name, shipping_email, shipping_phone, shipping_address, payment_method, total_amount, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
                $stmt->execute([$_SESSION['user_id'], $_POST['shipping_name'], $_POST['shipping_email'], $_POST['shipping_phone'], $_POST['shipping_address'], $payment_method, 100]); // Test amount

                $order_id = $pdo->lastInsertId();
                $pdo->commit();

                echo "<h2 style='color: green;'>Order Created! ID: $order_id</h2>";
            } catch (Exception $e) {
                echo "<h2 style='color: red;'>Error: " . $e->getMessage() . "</h2>";
            }
        } else {
            echo "<h3 style='color: blue;'>Would redirect to Paystack...</h3>";
        }
    }
} else {
    echo "<p style='color: orange;'>No form submission</p>";
}

echo "<h2>Test Form:</h2>";
echo "<form method='post' action='' style='border: 1px solid #ccc; padding: 20px; margin: 20px 0;'>";
echo "<input type='text' name='shipping_name' value='Test User' required style='margin: 5px; padding: 8px; width: 200px;'><br>";
echo "<input type='email' name='shipping_email' value='test@example.com' required style='margin: 5px; padding: 8px; width: 200px;'><br>";
echo "<input type='tel' name='shipping_phone' value='1234567890' required style='margin: 5px; padding: 8px; width: 200px;'><br>";
echo "<textarea name='shipping_address' required style='margin: 5px; padding: 8px; width: 200px; height: 60px;'>Test Address</textarea><br>";
echo "<select name='payment_method' required style='margin: 5px; padding: 8px; width: 200px;'>";
echo "<option value='paystack'>Paystack</option>";
echo "<option value='cash_on_delivery' selected>Cash on Delivery</option>";
echo "</select><br>";
echo "<button type='submit' name='place_order' style='margin: 5px; padding: 10px 20px; background: #007cba; color: white; border: none;'>Place Order</button>";
echo "</form>";

echo "<br><a href='cart.php'>Back to Cart</a>";
?>
