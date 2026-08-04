<?php
/**
 * Live Session & Authentication Diagnostics & Quick Setup
 * Visit: https://asoonlinemarket.com/diagnose.php
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/includes/db.php';

echo "<h1>Session & Auth Diagnostics</h1>";

// Create Admin Account On Demand
if (isset($_GET['create_admin'])) {
    try {
        $adminEmail = 'aso@admin.gh';
        $adminPassword = 'asoadmin123';
        $hashedPassword = password_hash($adminPassword, PASSWORD_DEFAULT);

        $checkStmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $checkStmt->execute([$adminEmail]);
        if ($checkStmt->fetch()) {
            $upd = $pdo->prepare("UPDATE users SET password = ?, role = 'admin' WHERE email = ?");
            $upd->execute([$hashedPassword, $adminEmail]);
            echo "<p style='color:green; font-weight:bold; font-size:18px;'>✓ Admin Account 'aso@admin.gh' Password Reset to 'asoadmin123'!</p>";
        } else {
            $ins = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES ('Administrator', ?, ?, 'admin')");
            $ins->execute([$adminEmail, $hashedPassword]);
            echo "<p style='color:green; font-weight:bold; font-size:18px;'>✓ Admin Account 'aso@admin.gh' Created Successfully!</p>";
        }
    } catch (Throwable $e) {
        echo "<p style='color:red;'>Failed to create admin: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

// Test Session Write/Read
if (isset($_GET['test_session'])) {
    $_SESSION['diag_test'] = 'session_working_123';
    header('Location: diagnose.php?step=2');
    exit();
}

if (isset($_GET['step']) && $_GET['step'] == '2') {
    if (isset($_SESSION['diag_test']) && $_SESSION['diag_test'] === 'session_working_123') {
        echo "<p style='color:green; font-weight:bold; font-size:18px;'>✓ SESSION PERSISTENCE TEST PASSED!</p>";
        echo "<p>Session cookies are working properly across redirects on this domain.</p>";
    } else {
        echo "<p style='color:red; font-weight:bold; font-size:18px;'>✗ SESSION PERSISTENCE TEST FAILED!</p>";
        echo "<p>Session variables were lost during 302 redirect. Host cookie settings or HTTPS proxy configuration is dropping session cookies.</p>";
    }
} else {
    echo "<p><a href='diagnose.php?test_session=1' style='background:#0a4722; color:#fff; padding:10px 18px; border-radius:6px; text-decoration:none; font-weight:bold;'>Click Here to Test Session Redirect Persistence →</a></p>";
}

// Test Admin User in Database
try {
    $stmt = $pdo->prepare("SELECT user_id, name, email, role, password FROM users WHERE email = ?");
    $stmt->execute(['aso@admin.gh']);
    $admin = $stmt->fetch();

    if ($admin) {
        echo "<div style='background:#eefbf2; border:1px solid #27ae60; padding:15px; border-radius:8px; margin:20px 0;'>";
        echo "<h3 style='color:#27ae60; margin-top:0;'>✓ Admin Account Found in Database</h3>";
        echo "<p><strong>User ID:</strong> {$admin['user_id']}</p>";
        echo "<p><strong>Email:</strong> {$admin['email']}</p>";
        echo "<p><strong>Role:</strong> {$admin['role']}</p>";

        $passCheck = password_verify('asoadmin123', $admin['password']);
        if ($passCheck) {
            echo "<p style='color:green; font-weight:bold;'>✓ Password 'asoadmin123' VERIFIED & MATCHES hash!</p>";
            echo "<p><a href='login.php' style='background:#0a4722; color:#fff; padding:10px 20px; border-radius:8px; text-decoration:none; font-weight:bold;'>Go to Login Page →</a></p>";
        } else {
            echo "<p style='color:red;'>✗ Password 'asoadmin123' DOES NOT MATCH hash!</p>";
            echo "<p><a href='diagnose.php?create_admin=1' style='background:#e67e22; color:#fff; padding:8px 16px; border-radius:6px; text-decoration:none; font-weight:bold;'>Reset Password to asoadmin123</a></p>";
        }
        echo "</div>";
    } else {
        echo "<div style='background:#fff0f0; border:1px solid #e74c3c; padding:15px; border-radius:8px; margin:20px 0;'>";
        echo "<h3 style='color:#e74c3c; margin-top:0;'>✗ Admin Account 'aso@admin.gh' NOT FOUND in Database</h3>";
        echo "<p style='margin-bottom:15px;'>The admin account does not exist in your live MySQL database yet.</p>";
        echo "<p><a href='diagnose.php?create_admin=1' style='background:#0a4722; color:#fff; padding:10px 20px; border-radius:6px; text-decoration:none; font-weight:bold;'>Click Here to Create Admin Account (aso@admin.gh) Now →</a></p>";
        echo "<p style='margin-top:15px;'>Or visit <a href='seed.php'>seed.php</a> to populate all sample categories, products, and admin accounts.</p>";
        echo "</div>";
    }

} catch (Throwable $e) {
    echo "<p style='color:red;'>Database Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
