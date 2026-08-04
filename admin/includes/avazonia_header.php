<?php
/**
 * ASO Admin — Avazonia shell header
 * Port of Avazonia admin/layout/header.php + sidebar.php, wired to ASO nav/filenames.
 * Expects: $page_title (set before include). Session must already be started + guarded.
 */
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$current_page = basename($_SERVER['PHP_SELF']);
function av_admin_nav_active($page, $others = []) {
    $cur = basename($_SERVER['PHP_SELF']);
    return in_array($cur, array_merge([$page], $others)) ? ' active' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Admin'; ?> — ASO</title>
    <link rel="icon" type="image/png" href="../assets/images/logo-rounded.png?v=2">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,200;0,300;0,400;0,500;0,600;0,700;0,800;1,200;1,300;1,400;1,500;1,600;1,700;1,800&family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/aso.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/admin-avazonia.css">
</head>
<body>
<aside class="admin-sidebar">
    <div class="sidebar-brand">
        <img src="../assets/images/logo.png" alt="ASO" class="logo-img admin" style="height: 32px; width: auto; margin-bottom: 4px;">
        <span>ADMIN</span>
        <button type="button" id="sidebar-collapse" aria-label="Toggle sidebar" title="Toggle sidebar">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
        </button>
    </div>
    <nav class="sidebar-nav">
        <a href="dashboard.php" class="nav-item<?php echo av_admin_nav_active('dashboard.php'); ?>">
            <span class="nav-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9"></rect><rect x="14" y="3" width="7" height="5"></rect><rect x="14" y="11" width="7" height="10"></rect><rect x="3" y="15" width="7" height="6"></rect></svg>
            </span><span class="nav-label">Dashboard</span>
        </a>
        <a href="manage_products.php" class="nav-item<?php echo av_admin_nav_active('manage_products.php', ['product_editor.php', 'csv_import.php']); ?>">
            <span class="nav-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
            </span><span class="nav-label">Products</span>
        </a>
        <a href="manage_orders.php" class="nav-item<?php echo av_admin_nav_active('manage_orders.php', ['order_details.php']); ?>">
            <span class="nav-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
            </span><span class="nav-label">Orders</span>
        </a>
        <a href="manage_categories.php" class="nav-item<?php echo av_admin_nav_active('manage_categories.php', ['manage_subcategories.php']); ?>">
            <span class="nav-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
            </span><span class="nav-label">Categories</span>
        </a>
        <a href="manage_tags.php" class="nav-item<?php echo av_admin_nav_active('manage_tags.php'); ?>">
            <span class="nav-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg>
            </span><span class="nav-label">Tags</span>
        </a>
        <a href="manage_users.php" class="nav-item<?php echo av_admin_nav_active('manage_users.php'); ?>">
            <span class="nav-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
            </span><span class="nav-label">Accounts</span>
        </a>
        <a href="manage_hero.php" class="nav-item<?php echo av_admin_nav_active('manage_hero.php'); ?>">
            <span class="nav-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
            </span><span class="nav-label">Hero Sliders</span>
        </a>
        <a href="analytics.php" class="nav-item<?php echo av_admin_nav_active('analytics.php'); ?>">
            <span class="nav-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 20V10"></path><path d="M12 20V4"></path><path d="M6 20v-6"></path></svg>
            </span><span class="nav-label">Analytics</span>
        </a>
        <a href="newsletter.php" class="nav-item<?php echo av_admin_nav_active('newsletter.php'); ?>">
            <span class="nav-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
            </span><span class="nav-label">Newsletter</span>
        </a>
        <a href="logs.php" class="nav-item<?php echo av_admin_nav_active('logs.php'); ?>">
            <span class="nav-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
            </span><span class="nav-label">System Logs</span>
        </a>
        <a href="maintenance.php" class="nav-item<?php echo av_admin_nav_active('maintenance.php'); ?>">
            <span class="nav-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="10 21 10 14 14 14 14 21"></polyline><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path></svg>
            </span><span class="nav-label">Maintenance</span>
        </a>
        <a href="settings.php" class="nav-item<?php echo av_admin_nav_active('settings.php', ['theme_settings.php', 'promo_settings.php']); ?>">
            <span class="nav-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
            </span><span class="nav-label">Settings</span><span class="nav-lock" style="margin-left: auto; opacity: 0.5;"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg></span>
        </a>
        <a href="../index.php" class="nav-item" target="_blank">
            <span class="nav-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
            </span><span class="nav-label">View Store ↗</span>
        </a>
    </nav>
    <div class="sidebar-footer">
        <a href="../logout.php" class="nav-item logout">
            <span class="nav-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
            </span><span class="nav-label">Logout</span>
        </a>
    </div>
</aside>

<div class="admin-overlay"></div>
<button id="admin-toggle" aria-label="Toggle sidebar">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
</button>

<script>
    (function () {
        const toggle = document.getElementById('admin-toggle');
        const sidebar = document.querySelector('.admin-sidebar');
        const overlay = document.querySelector('.admin-overlay');
        const collapseBtn = document.getElementById('sidebar-collapse');
        const COLLAPSE_KEY = 'aso_admin_sidebar_collapsed';

        const isMobile = () => window.innerWidth <= 900;

        if (collapseBtn && sidebar) {
            if (localStorage.getItem(COLLAPSE_KEY) === '1' && !isMobile()) {
                document.body.classList.add('sidebar-collapsed');
            }
            collapseBtn.addEventListener('click', function (e) {
                if (isMobile()) {
                    if (toggle) toggle.click();
                    return;
                }
                document.body.classList.toggle('sidebar-collapsed');
                localStorage.setItem(COLLAPSE_KEY, document.body.classList.contains('sidebar-collapsed') ? '1' : '0');
            });
        }
        if (toggle && sidebar && overlay) {
            const toggleSidebar = () => {
                sidebar.classList.toggle('active');
                toggle.classList.toggle('active');
                overlay.classList.toggle('active');
                document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : '';
            };
            toggle.addEventListener('click', toggleSidebar);
            overlay.addEventListener('click', toggleSidebar);
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && sidebar.classList.contains('active')) toggleSidebar();
            });
        }
    })();
</script>

<main class="admin-main">
