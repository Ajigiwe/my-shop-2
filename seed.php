<?php
/**
 * Database Seeder Script for ASO Online Market
 * Visit: https://asoonlinemarket.com/seed.php
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/includes/db.php';

echo "<h1>ASO Online Market - Database Seeder</h1>";

if (!$pdo) {
    die("<p style='color:red;'>Database connection failed. Please check your .env file.</p>");
}

$sql_file = __DIR__ . '/full_database_export.sql';
if (!file_exists($sql_file)) {
    die("<p style='color:red;'>Error: full_database_export.sql file not found!</p>");
}

try {
    echo "<p>Reading full_database_export.sql...</p>";
    $sql = file_get_contents($sql_file);

    // Disable foreign key checks for clean structure import
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
    $pdo->exec($sql);
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

    echo "<p style='color:green; font-weight:bold; font-size:18px;'>✓ Database Seeded Successfully!</p>";

    // Report counts
    $cat_count = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
    $prod_count = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $user_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $setting_count = $pdo->query("SELECT COUNT(*) FROM site_settings")->fetchColumn();

    echo "<ul>";
    echo "<li><strong>Categories Seeded:</strong> $cat_count</li>";
    echo "<li><strong>Products Seeded:</strong> $prod_count</li>";
    echo "<li><strong>Users Seeded:</strong> $user_count</li>";
    echo "<li><strong>Site Settings Configured:</strong> $setting_count</li>";
    echo "</ul>";

    echo "<p style='margin-top:20px;'><a href='index.php' style='background:#0a4722; color:#fff; padding:10px 20px; border-radius:8px; text-decoration:none; font-weight:bold;'>Go to Storefront →</a></p>";

} catch (Throwable $e) {
    echo "<p style='color:red; font-weight:bold;'>✗ Database Seeding Failed:</p>";
    echo "<p style='color:red;'>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
