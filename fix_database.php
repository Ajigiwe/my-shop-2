<?php
/**
 * Simple Database Fix Script
 * This script creates the database and tables needed for cart functionality
 */

// Simple database setup
$host = 'localhost';
$dbname = 'ecommerce_db';
$username = 'root';
$password = '';

echo "<!DOCTYPE html>";
echo "<html><head><title>Database Fix Script</title>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.success { color: green; }
.error { color: red; }
.info { color: blue; }
</style></head><body>";

echo "<h1>🛠️ Database Fix Script</h1>";
echo "<p>Setting up database for cart functionality...</p>";

try {
    // Create PDO connection
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "<p class='success'>✓ MySQL connection successful</p>";

    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname`");
    echo "<p class='success'>✓ Database '$dbname' ready</p>";

    // Use the database
    $pdo->exec("USE `$dbname`");

    // Create users table
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        user_id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        phone VARCHAR(20),
        address TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    echo "<p class='success'>✓ Users table ready</p>";

    // Create categories table first
    $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
        category_id INT AUTO_INCREMENT PRIMARY KEY,
        category_name VARCHAR(100) UNIQUE NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "<p class='success'>✓ Categories table ready</p>";

    // Insert sample categories BEFORE creating products table
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM categories WHERE category_name = ?");
    $stmt->execute(['Electronics']);
    if ($stmt->fetch()['count'] == 0) {
        $pdo->exec("INSERT INTO categories (category_name, description) VALUES
            ('Electronics', 'Electronic devices and accessories'),
            ('Clothing', 'Fashion and apparel')");
        echo "<p class='success'>✓ Sample categories added</p>";
    }

    // Create products table with proper foreign key
    $pdo->exec("CREATE TABLE IF NOT EXISTS products (
        product_id INT AUTO_INCREMENT PRIMARY KEY,
        category_id INT NOT NULL,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        price DECIMAL(10, 2) NOT NULL,
        stock_quantity INT NOT NULL DEFAULT 0,
        image VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE CASCADE
    )");
    echo "<p class='success'>✓ Products table ready</p>";

    // Now insert sample product after categories exist
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM products WHERE name = ?");
    $stmt->execute(['Test Product']);
    if ($stmt->fetch()['count'] == 0) {
        // Get the Electronics category ID
        $stmt = $pdo->prepare("SELECT category_id FROM categories WHERE category_name = ? LIMIT 1");
        $stmt->execute(['Electronics']);
        $category = $stmt->fetch();

        if ($category) {
            $pdo->prepare("INSERT INTO products (category_id, name, description, price, stock_quantity, image) VALUES (?, ?, ?, ?, ?, ?)")
                ->execute([$category['category_id'], 'Test Product', 'Test product for cart testing', 10.00, 100, 'test.jpg']);
            echo "<p class='success'>✓ Sample product added</p>";
        } else {
            echo "<p class='warning'>⚠ Could not find Electronics category for sample product</p>";
        }
    }

    echo "<hr>";
    echo "<h2 class='success'>🎉 Database Setup Complete!</h2>";
    echo "<p>All tables created successfully. Cart functionality should now work.</p>";

    echo "<div style='margin: 20px 0; padding: 15px; background: #e7f3ff; border-radius: 8px;'>";
    echo "<h3>Next Steps:</h3>";
    echo "<ol>";
    echo "<li><a href='login.php'>Log in</a> with admin@shop.com / admin123</li>";
    echo "<li><a href='shop.php'>Go to shop</a> and add items to cart</li>";
    echo "<li><a href='cart.php'>Test cart</a> operations (remove items, clear cart)</li>";
    echo "</ol>";
    echo "</div>";

    echo "<p><a href='cart.php' style='background: #28a745; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px;'>Test Cart Now</a></p>";

} catch(PDOException $e) {
    echo "<p class='error'>❌ Database setup failed: " . $e->getMessage() . "</p>";
    echo "<p><strong>Troubleshooting:</strong></p>";
    echo "<ul>";
    echo "<li>Make sure XAMPP is running</li>";
    echo "<li>Check if MySQL service is started</li>";
    echo "<li>Verify root user has no password in XAMPP</li>";
    echo "<li>Try restarting XAMPP completely</li>";
    echo "</ul>";
}

echo "</body></html>";
?>
