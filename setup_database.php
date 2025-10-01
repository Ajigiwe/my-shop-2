<?php
/**
 * Database Setup Helper
 * This script helps set up the database for the e-commerce site
 */

echo "<!DOCTYPE html>";
echo "<html><head><title>Database Setup Helper</title>";
echo "<style>body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }";
echo ".step { background: #f5f5f5; padding: 15px; margin: 10px 0; border-radius: 5px; }";
echo ".success { color: green; } .error { color: red; } .warning { color: orange; }";
echo "</style></head><body>";

echo "<h1>Database Setup Helper</h1>";
echo "<p>This page will help you set up the database for the ASO Online Market.</p>";

$steps = [
    [
        'title' => '1. Access phpMyAdmin',
        'description' => 'Open your web browser and go to: <strong>http://localhost/phpmyadmin</strong>',
        'action' => 'Go to phpMyAdmin'
    ],
    [
        'title' => '2. Create Database',
        'description' => 'Click "New" and create a database named: <strong>ecommerce_db</strong>',
        'action' => 'Create Database'
    ],
    [
        'title' => '3. Import Schema',
        'description' => 'Go to "Import" tab and select the file: <strong>database_setup.sql</strong>',
        'action' => 'Import SQL File'
    ],
    [
        'title' => '4. Run Setup Script',
        'description' => 'Click "Go" to execute the database setup script',
        'action' => 'Execute Import'
    ],
    [
        'title' => '5. Verify Setup',
        'description' => 'Check that all tables were created successfully',
        'action' => 'Verify Tables'
    ]
];

foreach ($steps as $index => $step) {
    echo "<div class='step'>";
    echo "<h3>{$step['title']}</h3>";
    echo "<p>{$step['description']}</p>";
    echo "<a href='{$step['action']}' target='_blank' style='background: #007cba; color: white; padding: 8px 16px; text-decoration: none; border-radius: 3px;'>{$step['action']}</a>";
    echo "</div>";
}

echo "<h2>Quick Database Check</h2>";
echo "<p>Once you've completed the setup above, you can verify everything is working:</p>";
echo "<ul>";
echo "<li><a href='cart_debug.php'>Test Cart Functionality</a></li>";
echo "<li><a href='shop.php'>Browse Products</a></li>";
echo "<li><a href='cart.php'>View Shopping Cart</a></li>";
echo "</ul>";

echo "<h2>Alternative: Quick Setup</h2>";
echo "<p>If you prefer, you can run this SQL directly in phpMyAdmin:</p>";
echo "<textarea style='width: 100%; height: 200px; font-family: monospace;' readonly>";
echo file_get_contents('database_setup.sql');
echo "</textarea>";

echo "<p><a href='index.php'>← Back to Home</a></p>";
echo "</body></html>";
?>
