<?php
/**
 * Admin Password Fix Script
 * Updates the admin password to match the expected credentials
 */

// Include database connection
require_once 'includes/db.php';

echo "<h1>🔧 Fixing Admin Password</h1>\n";
echo "<pre>\n";

try {
    // Update admin password to correct hash for 'admin123'
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
    $result = $stmt->execute(['$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@shop.com']);

    if ($result) {
        echo "✅ Admin password updated successfully!\n";
        echo "📧 Email: admin@shop.com\n";
        echo "🔑 Password: admin123\n";
        echo "\n";

        // Verify the fix
        $stmt = $pdo->prepare("SELECT password FROM users WHERE email = ?");
        $stmt->execute(['admin@shop.com']);
        $user = $stmt->fetch();

        if ($user && verifyPassword('admin123', $user['password'])) {
            echo "✅ Password verification test: PASSED\n";
            echo "🎉 Admin login should now work!\n";
        } else {
            echo "❌ Password verification still failing\n";
        }
    } else {
        echo "❌ Failed to update admin password\n";
    }

} catch(PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}

echo "</pre>\n";
echo "<hr>\n";
echo "<h2>✅ Admin Login Fixed!</h2>\n";
echo "<p><strong>Credentials:</strong></p>\n";
echo "<ul>\n";
echo "<li><strong>Email:</strong> admin@shop.com</li>\n";
echo "<li><strong>Password:</strong> admin123</li>\n";
echo "</ul>\n";
echo "<p><a href='login.php' class='btn btn-success'>Try Admin Login</a></p>\n";
?>
