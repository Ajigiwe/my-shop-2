<?php
/**
 * Database Setup Test
 */

// Test database connection and table creation
try {
    require_once 'includes/db.php';

    if ($pdo !== null) {
        echo "<h1 style='color: green;'>✓ Database Connected Successfully!</h1>";

        // Check if tables exist
        $required_tables = ['users', 'categories', 'products', 'cart', 'orders', 'order_items'];
        $missing_tables = [];

        foreach ($required_tables as $table) {
            try {
                $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
                if (!$stmt->fetch()) {
                    $missing_tables[] = $table;
                }
            } catch (Exception $e) {
                $missing_tables[] = $table . " (error: " . $e->getMessage() . ")";
            }
        }

        if (empty($missing_tables)) {
            echo "<h2 style='color: green;'>✓ All Required Tables Exist!</h2>";
            echo "<p>You can now test the cart functionality.</p>";
        } else {
            echo "<h2 style='color: orange;'>⚠ Missing Tables:</h2>";
            echo "<ul>";
            foreach ($missing_tables as $table) {
                echo "<li style='color: red;'>$table</li>";
            }
            echo "</ul>";
            echo "<p><a href='setup_database.php'>Click here to set up the database</a></p>";
        }

        // Test cart operations
        session_start();
        if (isset($_SESSION['user_id'])) {
            echo "<h2>Cart Test:</h2>";

            // Test cart count
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM cart WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $result = $stmt->fetch();
            echo "<p>Items in cart: " . $result['count'] . "</p>";

            if ($result['count'] > 0) {
                echo "<p style='color: green;'>✓ Cart has items - ready for testing!</p>";
            } else {
                echo "<p style='color: orange;'>⚠ Cart is empty - add some items first</p>";
            }
        } else {
            echo "<p style='color: orange;'>⚠ Please log in first to test cart operations</p>";
        }

    } else {
        echo "<h1 style='color: red;'>✗ Database Connection Failed!</h1>";
        echo "<p>Check your .env file and database configuration.</p>";
        echo "<p><a href='setup_database.php'>Setup Instructions</a></p>";
    }

} catch (Exception $e) {
    echo "<h1 style='color: red;'>✗ Error: " . $e->getMessage() . "</h1>";
    echo "<p><a href='setup_database.php'>Check setup instructions</a></p>";
}

echo "<hr>";
echo "<h3>Quick Actions:</h3>";
echo "<a href='cart_diagnostic.php' style='background: #17a2b8; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>Run Full Diagnostic</a>";
echo "<a href='cart_new.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>Test New Cart</a>";
echo "<a href='login.php' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>Login</a>";
echo "<a href='shop.php' style='background: #ffc107; color: #212529; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>Shop</a>";
?>
