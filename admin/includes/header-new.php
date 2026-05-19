<?php
/**
 * Modern Admin Header
 */
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Admin Dashboard'; ?> | ASO Admin</title>
    
    <!-- CSS Dependencies -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <link rel="stylesheet" href="../assets/css/admin-new.css">
</head>
<body>

<aside class="admin-sidebar">
    <div class="admin-logo">
        <img src="../assets/images/logo.png" alt="Logo" style="height: 32px;">
        <span class="fw-black text-uppercase tracking-tighter small">Admin Portal</span>
    </div>

    <nav class="admin-nav">
        <a href="dashboard.php" class="admin-nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
            <i class="material-symbols-outlined">dashboard</i> Dashboard
        </a>
        
        <!-- Management Sections -->
        <div class="px-3 mb-2 small text-muted fw-bold uppercase tracking-widest" style="font-size: 10px; opacity: 0.5;">Management</div>
        
        <a href="manage_products.php" class="admin-nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage_products.php' ? 'active' : ''; ?>">
            <i class="material-symbols-outlined">inventory_2</i> Products
        </a>
        <a href="manage_categories.php" class="admin-nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage_categories.php' ? 'active' : ''; ?>">
            <i class="material-symbols-outlined">category</i> Categories
        </a>
        <a href="manage_orders.php" class="admin-nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage_orders.php' ? 'active' : ''; ?>">
            <i class="material-symbols-outlined">shopping_cart</i> Orders
        </a>
        <a href="manage_users.php" class="admin-nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage_users.php' ? 'active' : ''; ?>">
            <i class="material-symbols-outlined">group</i> Users
        </a>
        <a href="analytics.php" class="admin-nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'analytics.php' ? 'active' : ''; ?>">
            <i class="material-symbols-outlined">insights</i> Analytics
        </a>

        <!-- System Sections -->
        <div class="px-3 mt-4 mb-2 small text-muted fw-bold uppercase tracking-widest" style="font-size: 10px; opacity: 0.5;">System</div>

        <a href="settings.php" class="admin-nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
            <i class="material-symbols-outlined">settings</i> System Settings
        </a>
        <a href="theme_settings.php" class="admin-nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'theme_settings.php' ? 'active' : ''; ?>">
            <i class="material-symbols-outlined">palette</i> Brand Settings
        </a>
        <a href="promo_settings.php" class="admin-nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'promo_settings.php' ? 'active' : ''; ?>">
            <i class="material-symbols-outlined">campaign</i> Promo Popup
        </a>
        
        <div class="mt-auto pt-5">
            <a href="../logout.php" class="admin-nav-link text-danger mt-5">
                <i class="material-symbols-outlined">logout</i> Logout
            </a>
        </div>
    </nav>
</aside>

<main class="admin-main">
    <header class="admin-header animate-up">
        <h1 class="admin-title mb-0"><?php echo $page_title ?? 'Dashboard'; ?></h1>
        
        <div class="d-flex align-items-center gap-4">
            <a href="../index.php" class="btn-premium-outline text-decoration-none">View Website</a>
            <div class="admin-user-pill">
                <div class="bg-dark text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                    <span class="material-symbols-outlined text-[16px]">person</span>
                </div>
                <span class="fw-bold small"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></span>
                <i class="material-symbols-outlined text-[18px]">expand_more</i>
            </div>
        </div>
    </header>
