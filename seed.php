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

    // Ensure Admin Account Exists
    $adminEmail = 'aso@admin.gh';
    $adminPassword = 'asoadmin123';
    $hashedPassword = password_hash($adminPassword, PASSWORD_DEFAULT);
    
    $checkStmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
    $checkStmt->execute([$adminEmail]);
    if ($checkStmt->fetch()) {
        $upd = $pdo->prepare("UPDATE users SET password = ?, role = 'admin' WHERE email = ?");
        $upd->execute([$hashedPassword, $adminEmail]);
    } else {
        $ins = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES ('Administrator', ?, ?, 'admin')");
        $ins->execute([$adminEmail, $hashedPassword]);
    }

    echo "<ul>";
    echo "<li><strong>Categories Seeded:</strong> $cat_count</li>";
    echo "<li><strong>Products Seeded:</strong> $prod_count</li>";
    echo "<li><strong>Users Seeded:</strong> $user_count</li>";
    echo "<li><strong>Site Settings Configured:</strong> $setting_count</li>";
    echo "</ul>";

    echo "<div style='background:#f4f4f4; border:1px solid #ccc; padding:15px; border-radius:8px; margin:20px 0;'>";
    echo "<h3 style='margin-top:0;'>🔑 Admin Login Credentials</h3>";
    echo "<p><strong>Admin URL:</strong> <a href='admin/dashboard.php'>https://asoonlinemarket.com/login.php</a></p>";
    echo "<p><strong>Email:</strong> <code>aso@admin.gh</code></p>";
    echo "<p><strong>Password:</strong> <code>asoadmin123</code></p>";
    echo "</div>";

    echo "<p style='margin-top:20px;'><a href='login.php' style='background:#0a4722; color:#fff; padding:10px 20px; border-radius:8px; text-decoration:none; font-weight:bold;'>Go to Login →</a></p>";

} catch (Throwable $e) {
    echo "<p style='color:red; font-weight:bold;'>✗ Database Seeding Failed:</p>";
    echo "<p style='color:red;'>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
