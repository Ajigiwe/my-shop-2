<?php
/**
 * Fix Admin Login
 * - Checks if the admin user exists
 * - Resets the password to 'admin123' to ensure it works
 */
require_once __DIR__ . '/includes/db.php';

try {
    $email = 'admin@shop.com';
    $password = 'admin123';
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $name = 'Administrator';
    $role = 'admin';

    // Check if user exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        // Update existing user
        $stmt = $pdo->prepare("UPDATE users SET password = ?, role = ? WHERE email = ?");
        $stmt->execute([$hashedPassword, $role, $email]);
        echo "Admin user found. Password reset to 'admin123' and role set to 'admin'.\n";
    } else {
        // Create new admin user
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $email, $hashedPassword, $role]);
        echo "Admin user created with email 'admin@shop.com' and password 'admin123'.\n";
    }

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
