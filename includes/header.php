<?php
/**
 * Header include — Avazonia-style storefront shell.
 * Renders <head> (fonts, tokens, PWA), announcement bar, nav, mobile menu
 * and opens #page-wrapper. Page content goes between this and footer.php.
 */
require_once __DIR__ . '/db.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$base = '';
$current_path = $_SERVER['PHP_SELF'] ?? '';
if (preg_match('/\/(admin|user|legal)\//', $current_path)) {
    $base = '../';
}

// Compute the app root path from the actual request so AJAX/SHOP_URL works on
// any host (localhost or production) regardless of the SITE_URL env value.
$script_dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
$app_root   = rtrim(preg_replace('#/[^/]+/\.\.(?=/|$)#', '', $script_dir . '/' . $base), '/');

// Fetch site settings
$settings = loadSiteSettings($pdo);

$site_name = $settings['site_name'] ?? 'ASO Online Market';
$primary_color = $settings['primary_color'] ?? '#0a4722';
$dbSettings = $settings; // alias so ported components can read global $dbSettings

// Avazonia-style runtime constants used by ported views
if (!defined('APP_URL'))       define('APP_URL', rtrim(SITE_URL, '/'));
if (!defined('APP_NAME'))      define('APP_NAME', $site_name);
if (!defined('APP_PATH'))      define('APP_PATH', parse_url(SITE_URL, PHP_URL_PATH) ?: '');
if (!defined('PRIMARY_COLOR')) define('PRIMARY_COLOR', $primary_color);
if (!defined('SITE_EMAIL'))    define('SITE_EMAIL', $settings['support_email'] ?? 'hello@asoonlinemarket.com.gh');
if (!defined('FOOTER_NOTICE')) define('FOOTER_NOTICE', $settings['footer_notice'] ?? '© ' . date('Y') . ' ' . $site_name . ' — Crafted in Takoradi, Ghana');
$whatsapp_number = preg_replace('/\D/', '', $settings['social_whatsapp'] ?? '233240000000');
if (!defined('WHATSAPP_NUMBER')) define('WHATSAPP_NUMBER', $whatsapp_number);

// Maintenance mode: show branded hold screen to everyone except admins.
// Auth pages stay reachable so admins can sign back in to disable it.
if (($settings['maintenance_mode'] ?? '0') === '1' && ($_SESSION['user_role'] ?? '') !== 'admin') {
    $page_basename = basename($current_path);
    if (!in_array($page_basename, ['login.php', 'register.php', 'forgot_password.php', 'reset_password.php', 'verify_email.php', 'logout.php'])) {
        $maintenance_msg = $settings['maintenance_message'] ?? "We're doing a little maintenance. We'll be back shortly.";
        http_response_code(503);
        ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Maintenance - <?php echo htmlspecialchars($site_name); ?></title>
    <style>
        body { margin: 0; background: #0D0D0D; color: #FFFFFF; font-family: 'Inter', 'Segoe UI', Arial, sans-serif; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; }
        .hold { max-width: 460px; text-align: center; }
        .logo { font-family: 'Outfit', 'Segoe UI', Arial, sans-serif; font-weight: 900; font-size: 22px; letter-spacing: -0.02em; margin-bottom: 28px; }
        .logo span { color: #E8002D; }
        .tag { display: inline-block; border: 1px solid rgba(255,255,255,0.15); border-radius: 999px; padding: 6px 14px; font-size: 10px; font-weight: 800; letter-spacing: 0.18em; text-transform: uppercase; color: rgba(255,255,255,0.55); margin-bottom: 20px; }
        h1 { font-family: 'Outfit', 'Segoe UI', Arial, sans-serif; font-size: 40px; font-weight: 900; letter-spacing: -0.03em; line-height: 1.1; margin: 0 0 14px; }
        p { color: rgba(255,255,255,0.6); font-size: 15px; line-height: 1.6; margin: 0 0 28px; }
        .spin { width: 34px; height: 34px; border: 3px solid rgba(255,255,255,0.15); border-top-color: #E8002D; border-radius: 50%; animation: s 0.9s linear infinite; margin: 0 auto 10px; }
        @keyframes s { to { transform: rotate(360deg); } }
        .btn { display: inline-block; padding: 12px 22px; border: 1px solid rgba(255,255,255,0.25); border-radius: 999px; color: #FFFFFF; text-decoration: none; font-size: 13px; font-weight: 800; transition: background 0.2s; }
        .btn:hover { background: rgba(255,255,255,0.08); }
    </style>
</head>
<body>
    <div class="hold">
        <div class="logo"><?php echo htmlspecialchars($site_name); ?><span>.</span></div>
        <div class="tag">Maintenance mode</div>
        <h1>We'll be back shortly.</h1>
        <p><?php echo nl2br(htmlspecialchars($maintenance_msg)); ?></p>
        <div class="spin"></div>
        <a class="btn" href="login.php">Admin? Sign in</a>
    </div>
</body>
</html>
        <?php
        exit;
    }
}

// Nav data
$cart_count = asoCartCount($pdo ?? null);
$wishlist_count = 0;
if (isset($_SESSION['user_id']) && isset($pdo)) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as c FROM wishlist WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $wishlist_count = (int)($stmt->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);
    } catch (PDOException $e) { $wishlist_count = 0; }
}
$navCategories = [];
try {
    $navCategories = $pdo->query("SELECT category_id, category_name FROM categories ORDER BY category_id ASC LIMIT 10")->fetchAll();
} catch (PDOException $e) { $navCategories = []; }

function avazonia_cat_icon($name) {
    $slug = strtolower(str_replace([' ', '&', '-'], '', $name));
    $map = [
        'electronics' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="2" y1="20" x2="22" y2="20"></line></svg>',
        'clothing' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.38 3.46L16 2a4 4 0 0 1-8 0L3.62 3.46a2 2 0 0 0-1.34 2.23l.58 3.47a1 1 0 0 0 .99.84H6v10c0 1.1.9 2 2 2h8a2 2 0 0 0 2-2V10h2.15a1 1 0 0 0 .99-.84l.58-3.47a2 2 0 0 0-1.34-2.23z"></path></svg>',
        'homegarden' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>',
        'smartphones' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect><line x1="12" y1="18" x2="12.01" y2="18"></line></svg>',
        'wearables' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="7"></circle><polyline points="12 9 12 12 13.5 13.5"></polyline><path d="M16.51 17.35l-.35 3.83a2 2 0 0 1-2 1.82H9.84a2 2 0 0 1-2-1.82l-.35-3.83m.01-10.7l.35-3.83A2 2 0 0 1 9.84 1H14.16a2 2 0 0 1 2 1.82l.35 3.83"></path></svg>',
        'audio' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 18v-6a9 9 0 0 1 18 0v6"></path><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"></path></svg>',
        'accessories' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>',
        'beauty' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.38 3.46L16 2a4 4 0 0 1-8 0L3.62 3.46a2 2 0 0 0-1.34 2.23l.58 3.47a1 1 0 0 0 .99.84H6v10c0 1.1.9 2 2 2h8a2 2 0 0 0 2-2V10h2.15a1 1 0 0 0 .99-.84l.58-3.47a2 2 0 0 0-1.34-2.23z"></path></svg>',
    ];
    foreach ($map as $k => $svg) {
        if (strpos($slug, $k) !== false) return $svg;
    }
    return '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4a2 2 0 0 0 1-1.73z"></path></svg>';
}

$is_home = (basename($current_path) === 'index.php' || $current_path === '/');
$meta_title = $page_title ?? $site_name;
$meta_description = $settings['site_description'] ?? $site_name . ' — your one-stop market.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($meta_title); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($meta_description); ?>">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?php echo htmlspecialchars($meta_title); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($meta_description); ?>">
    <meta property="og:image" content="<?php echo SITE_URL; ?>assets/images/logo-v3.png">
    <meta property="og:site_name" content="<?php echo htmlspecialchars($site_name); ?>">

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($meta_title); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($meta_description); ?>">

    <?php if (!empty($json_ld)): ?>
        <script type="application/ld+json"><?php echo $json_ld; ?></script>
    <?php endif; ?>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $base; ?>assets/css/aso.css?v=<?php echo time(); ?>">
    <style>
        :root {
            --red: <?php echo $primary_color; ?>;
            --red-deep: <?php echo $primary_color; ?>;
            --nav-offset: 0px;
        }
        .nav-brand-name {
            font-family: var(--f-display);
            font-weight: 900;
            font-size: 24px;
            letter-spacing: -0.02em;
            color: var(--ink);
            text-transform: uppercase;
            white-space: nowrap;
            line-height: 1;
        }
        @media (max-width: 480px) {
            .nav-brand-name { font-size: 20px; }
        }
    </style>
    <link rel="icon" type="image/png" href="<?php echo $base; ?>assets/images/logo-rounded.png?v=2">

    <!-- PWA Support -->
    <link rel="manifest" href="<?php echo $base; ?>manifest.php">
    <meta name="theme-color" content="<?php echo $primary_color; ?>">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="<?php echo htmlspecialchars($site_name); ?>">
    <link rel="apple-touch-icon" href="<?php echo $base; ?>assets/images/logo-rounded.png?v=2">

    <script>
        window.SHOP_URL = window.location.origin + '/<?php echo ltrim($app_root, '/'); ?>/';
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                const swPath = window.SHOP_URL ? window.SHOP_URL + 'service-worker.js' : 'service-worker.js';
                navigator.serviceWorker.register(swPath).catch(err => console.error('SW registration failed:', err));
            });
        }
    </script>
</head>
<body>

<?php if (!empty($settings['announcement_text'])): ?>
<div style="background: var(--red); color: #fff; text-align: center; padding: 8px 16px; font-size: 12px; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase;">
    <?php echo htmlspecialchars($settings['announcement_text']); ?>
</div>
<?php endif; ?>

<div class="ghana-topbar" id="ghana-topbar">
    <a href="<?php echo $base; ?>local.php" class="ghana-topbar-link">
        <span class="ghana-topbar-flag">🇬🇭</span>
        <span class="ghana-topbar-text">Made in Ghana — authentic local goods, delivered worldwide</span>
        <span class="ghana-topbar-cta">Explore <span class="ghana-topbar-arrow">→</span></span>
    </a>
    <button type="button" class="ghana-topbar-close" id="ghana-topbar-close" aria-label="Dismiss Made in Ghana banner">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
    </button>
</div>
<style>
.ghana-topbar { position: relative; background: linear-gradient(90deg,#1f1407,#3a2410); color: #fff; }
.ghana-topbar-link { display: flex; align-items: center; justify-content: center; gap: 12px; padding: 10px 48px; text-decoration: none; color: #fff; font-family: var(--f-semi); font-size: 13px; letter-spacing: 0.02em; text-align: center; }
.ghana-topbar-flag { font-size: 15px; line-height: 1; }
.ghana-topbar-text { color: rgba(255,255,255,0.85); }
.ghana-topbar-cta { font-family: var(--f-mono); font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.1em; color: #f0c36a; white-space: nowrap; }
.ghana-topbar-arrow { transition: transform 0.25s ease; display: inline-block; }
.ghana-topbar-link:hover .ghana-topbar-arrow { transform: translateX(4px); }
.ghana-topbar-close { position: absolute; right: 14px; top: 50%; transform: translateY(-50%); background: none; border: none; color: rgba(255,255,255,0.5); cursor: pointer; padding: 4px; display: flex; align-items: center; justify-content: center; }
.ghana-topbar-close:hover { color: #fff; }
@media (max-width: 640px) { .ghana-topbar-link { flex-direction: column; gap: 2px; padding: 8px 40px; } .ghana-topbar-text { font-size: 12px; } }
</style>
<script>
(function(){
    var bar = document.getElementById('ghana-topbar');
    if (!bar) return;
    if (sessionStorage.getItem('aso_ghana_topbar_dismissed') === '1') { bar.style.display = 'none'; }
    var closeBtn = document.getElementById('ghana-topbar-close');
    if (closeBtn) closeBtn.addEventListener('click', function(){
        sessionStorage.setItem('aso_ghana_topbar_dismissed', '1');
        bar.style.display = 'none';
    });
})();
</script>

<nav class="nav" id="main-nav">
    <div class="container-fluid nav-inner">
        <!-- Row 1: Actions & Brand -->
        <div class="nav-top">
            <button type="button" class="nav-icon-btn hamburger-square mobile-only" id="nav-toggle" aria-label="Open Menu">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
            </button>

            <a href="<?php echo $base; ?>index.php" class="nav-brand">
                <span class="nav-brand-name"><?php echo htmlspecialchars($site_name); ?></span>
            </a>

            <!-- Search -->
            <form action="<?php echo $base; ?>shop.php" method="GET" class="nav-search-pill" id="nav-search-form">
                <button type="button" class="mobile-search-close" id="mobile-search-close" aria-label="Close Search">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
                <input type="text" name="q" id="nav-search-input" placeholder="Search for products..." required autocomplete="off" value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>">
                <button type="submit" class="search-btn">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                </button>
                <div id="search-suggestions" class="search-suggestions" style="display: none;"></div>
            </form>

            <!-- Right: Icons -->
            <div class="nav-right-icons">
                <button type="button" class="nav-icon-btn mobile-only" id="mobile-search-toggle" aria-label="Open Search">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                </button>

                <div class="nav-cat-trigger desktop-only" id="cat-trigger">
                    <div class="hamburger-mini">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="7" height="7"></rect>
                            <rect x="14" y="3" width="7" height="7"></rect>
                            <rect x="14" y="14" width="7" height="7"></rect>
                            <rect x="3" y="14" width="7" height="7"></rect>
                        </svg>
                    </div>
                    <div class="cat-dropdown">
                        <?php foreach ($navCategories as $cat): ?>
                            <a href="<?php echo $base; ?>shop.php?category=<?php echo urlencode($cat['category_name']); ?>" class="cat-drop-item">
                                <span class="cat-drop-icon"><?php echo avazonia_cat_icon($cat['category_name']); ?></span>
                                <?php echo htmlspecialchars($cat['category_name']); ?>
                            </a>
                        <?php endforeach; ?>
                        <a href="<?php echo $base; ?>shop.php" class="cat-drop-item" style="border-top: 1px solid rgba(0,0,0,0.05); margin-top: 8px;">
                            <span class="cat-drop-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="1"></circle><circle cx="19" cy="12" r="1"></circle><circle cx="5" cy="12" r="1"></circle></svg>
                            </span>
                            Browse All
                        </a>
                    </div>
                </div>

                <a href="<?php echo $base; ?>user/wishlist.php" class="nav-icon-btn desktop-only" aria-label="Wishlist">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
                </a>

                <div class="nav-account-trigger" id="acc-trigger">
                    <button class="nav-icon-btn" aria-label="Account Menu">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    </button>
                    <div class="acc-dropdown">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <a href="<?php echo $base; ?>user/wishlist.php" class="acc-link">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l8.84-8.84 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
                                Wishlist
                            </a>
                            <a href="<?php echo $base; ?>user/dashboard.php" class="acc-link">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                                My Account
                            </a>
                            <a href="<?php echo $base; ?>user/orders.php" class="acc-link">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 8h-2V5a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2v3H3a1 1 0 0 0-1 1v11a1 1 0 0 0 1 1h18a1 1 0 0 0 1-1V9a1 1 0 0 0-1-1zM7 5h10v3H7V5zm12 15H5V10h14v10z"></path></svg>
                                My Orders
                            </a>
                            <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
                            <a href="<?php echo $base; ?>admin/dashboard.php" class="acc-link">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                                Admin Panel
                            </a>
                            <?php endif; ?>
                            <a href="<?php echo $base; ?>logout.php" class="acc-link logout">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                                Logout
                            </a>
                        <?php else: ?>
                            <a href="<?php echo $base; ?>login.php" class="acc-link">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><polyline points="10 17 15 12 10 7"></polyline><line x1="15" y1="12" x2="3" y2="12"></line></svg>
                                Login
                            </a>
                            <a href="<?php echo $base; ?>register.php" class="acc-link">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
                                Register
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <a href="<?php echo $base; ?>cart.php" class="nav-icon-btn nav-cart" aria-label="Cart">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
                    <span class="cart-badge"><?php echo $cart_count; ?></span>
                </a>
            </div>
        </div>
    </div>
</nav>

<div class="menu-overlay" id="menu-overlay"></div>

<div class="mobile-menu" id="mobile-menu">
    <div class="mobile-menu-header">
        <button class="menu-close" id="menu-close">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>
    </div>
    <nav style="display: flex; flex-direction: column; gap: 4px; margin-top: 20px;">
        <a href="<?php echo $base; ?>deals.php" class="mobile-link" style="color: var(--red); font-weight: 800; border-left: 3px solid var(--red); padding-left: 12px; margin-left: -12px;">Flash Deals</a>
        <div style="height: 1px; background: rgba(0,0,0,0.05); margin: 10px 0;"></div>
        <a href="<?php echo $base; ?>index.php" class="mobile-link">Store Home</a>
        <a href="<?php echo $base; ?>shop.php" class="mobile-link">Shop All</a>
        <a href="<?php echo $base; ?>local.php" class="mobile-link" style="color: var(--red); font-weight: 800;">🇬🇭 Made in Ghana</a>
        <div style="height: 1px; background: rgba(0,0,0,0.05); margin: 10px 0;"></div>
        <?php foreach ($navCategories as $cat): ?>
            <a href="<?php echo $base; ?>shop.php?category=<?php echo urlencode($cat['category_name']); ?>" class="mobile-link"><?php echo htmlspecialchars($cat['category_name']); ?></a>
        <?php endforeach; ?>
        <div style="height: 1px; background: rgba(0,0,0,0.05); margin: 10px 0;"></div>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="<?php echo $base; ?>user/profile.php" class="mobile-link">Profile Settings</a>
            <a href="<?php echo $base; ?>logout.php" class="mobile-link" style="opacity: 0.5;">Logout ↗</a>
        <?php else: ?>
            <a href="<?php echo $base; ?>login.php" class="mobile-link">Login</a>
            <a href="<?php echo $base; ?>register.php" class="mobile-link">Sign Up</a>
        <?php endif; ?>
    </nav>
</div>

<script>
    (function() {
        const mainNav = document.getElementById('main-nav');

        function handleScroll() {
            if (!mainNav) return;
            const scrollThreshold = 50;
            const isScrolled = window.scrollY > scrollThreshold;
            const basePath = '<?php echo addslashes(APP_PATH); ?>'.replace(/\/$/, '');
            let currentPath = window.location.pathname;
            if (basePath && currentPath.indexOf(basePath) === 0) {
                currentPath = currentPath.substring(basePath.length);
            }
            const cleanPath = currentPath.replace(/\/$/, '') || '/';
            const isHome = cleanPath === '/' || cleanPath === '/index.php' || cleanPath === '';

            if (isScrolled) {
                mainNav.classList.add('nav-scrolled');
                mainNav.classList.remove('nav-home');
            } else {
                if (isHome) {
                    mainNav.classList.remove('nav-scrolled');
                    mainNav.classList.add('nav-home');
                } else {
                    mainNav.classList.add('nav-scrolled');
                    mainNav.classList.remove('nav-home');
                }
            }
        }

        window.addEventListener('scroll', handleScroll, { passive: true });
        handleScroll();

        document.addEventListener('click', (e) => {
            if (e.target.closest('#nav-toggle')) toggleMenu(true);
            if (e.target.closest('#menu-close') || e.target.closest('#menu-overlay')) toggleMenu(false);
            if (e.target.closest('#mobile-search-toggle')) {
                const sf = document.getElementById('nav-search-form');
                if (sf) { sf.classList.add('is-active'); const si = document.getElementById('nav-search-input'); if (si) setTimeout(() => si.focus(), 100); }
            }
            if (e.target.closest('#mobile-search-close')) {
                const sf = document.getElementById('nav-search-form');
                if (sf) sf.classList.remove('is-active');
                const box = document.getElementById('search-suggestions');
                if (box) box.style.display = 'none';
            }
        });

        window.toggleMenu = function(show) {
            const menu = document.getElementById('mobile-menu');
            const overlay = document.getElementById('menu-overlay');
            if (menu) menu.classList.toggle('active', show);
            if (overlay) overlay.classList.toggle('active', show);
            document.body.style.overflow = show ? 'hidden' : '';
        };

        document.addEventListener('click', (e) => {
            const trigger = e.target.closest('.nav-cat-trigger, .nav-account-trigger');
            const dropdowns = document.querySelectorAll('.nav-cat-trigger, .nav-account-trigger');
            if (trigger) {
                if (e.target.closest('a')) return;
                e.preventDefault(); e.stopPropagation();
                const isActive = trigger.classList.contains('active');
                dropdowns.forEach(d => d.classList.remove('active'));
                if (!isActive) trigger.classList.add('active');
            } else if (!e.target.closest('.cat-dropdown') && !e.target.closest('.acc-dropdown')) {
                dropdowns.forEach(d => d.classList.remove('active'));
            }
        });

        window.showToast = function(msg) {
            let t = document.getElementById('toast');
            if (!t) { t = document.createElement('div'); t.id = 'toast'; document.body.appendChild(t); }
            t.innerText = msg; t.classList.add('show');
            setTimeout(() => t.classList.remove('show'), 3000);
        };

        let searchTimeout;
        document.addEventListener('input', (e) => {
            if (e.target.id === 'nav-search-input') {
                const box = document.getElementById('search-suggestions');
                if (!box) return;
                clearTimeout(searchTimeout);
                const q = e.target.value.trim();
                if (q.length < 2) { box.style.display = 'none'; return; }
                searchTimeout = setTimeout(async () => {
                    try {
                        const res = await fetch(window.SHOP_URL + 'api/search_suggestions.php?q=' + encodeURIComponent(q));
                        const data = await res.json();
                        if (data && data.length > 0) {
                            box.innerHTML = data.map(i => `<a href="${window.SHOP_URL}product.php?id=${i.id}" class="suggestion-item"><img src="${i.image_url || ''}" alt="" onerror="this.style.display='none'"><span>${i.name}</span></a>`).join('');
                            box.style.display = 'block';
                        } else {
                            box.innerHTML = '<div class="suggestion-empty">No products found</div>';
                            box.style.display = 'block';
                        }
                    } catch (err) { console.error(err); }
                }, 300);
            }
        });

        document.addEventListener('click', (e) => {
            const box = document.getElementById('search-suggestions');
            const input = document.getElementById('nav-search-input');
            if (box && input && !input.contains(e.target) && !box.contains(e.target)) box.style.display = 'none';
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                const box = document.getElementById('search-suggestions');
                if (box) box.style.display = 'none';
                const sf = document.getElementById('nav-search-form');
                if (sf) sf.classList.remove('is-active');
            }
        });

        window.initSlider = function() {
            const slides = document.querySelectorAll('.hero-slide');
            const dots = document.querySelectorAll('.dot');
            if (!slides.length) return;
            let currentSlide = 0, slideInterval;
            const showSlide = (n) => {
                slides.forEach(s => s.classList.remove('active'));
                dots.forEach(d => d.classList.remove('active'));
                if (slides[n]) slides[n].classList.add('active');
                if (dots[n]) dots[n].classList.add('active');
                currentSlide = n;
            };
            const nextSlide = () => showSlide((currentSlide + 1) % slides.length);
            const startAutoPlay = () => { clearInterval(slideInterval); slideInterval = setInterval(nextSlide, 5000); };
            dots.forEach((dot, index) => dot.addEventListener('click', () => { clearInterval(slideInterval); showSlide(index); startAutoPlay(); }));
            showSlide(0);
            startAutoPlay();
        };

        window.initScrollReveal = function() {
            const reveals = document.querySelectorAll('.reveal');
            if (!('IntersectionObserver' in window)) { reveals.forEach(el => el.classList.add('in')); return; }
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => { if (entry.isIntersecting) entry.target.classList.add('in'); });
            }, { root: null, threshold: 0.1, rootMargin: "0px 0px -50px 0px" });
            reveals.forEach(el => observer.observe(el));
        };

        window.scrollSlider = function(id, direction) {
            const slider = document.getElementById(id);
            if (!slider) return;
            const firstCard = slider.querySelector('.card');
            if (!firstCard) return;
            slider.scrollBy({ left: (firstCard.offsetWidth + 12) * direction, behavior: 'smooth' });
        };

        window.initBestsellersAutoplay = function() {
            const slider = document.getElementById('bestsellers-slider');
            if (!slider) return;
            let autoplayInterval;
            const startAutoplay = () => {
                clearInterval(autoplayInterval);
                autoplayInterval = setInterval(() => {
                    const maxScrollLeft = slider.scrollWidth - slider.clientWidth;
                    if (slider.scrollLeft >= maxScrollLeft - 10) slider.scrollTo({ left: 0, behavior: 'smooth' });
                    else window.scrollSlider('bestsellers-slider', 1);
                }, 5000);
            };
            slider.addEventListener('mouseenter', () => clearInterval(autoplayInterval));
            slider.addEventListener('mouseleave', startAutoplay);
            slider.addEventListener('touchstart', () => clearInterval(autoplayInterval), { passive: true });
            startAutoplay();
        };

        window.reinitScripts = function() {
            window.initSlider();
            window.initScrollReveal();
            window.initBestsellersAutoplay();
        };

        document.addEventListener('DOMContentLoaded', window.reinitScripts);
    })();
</script>

<div id="page-wrapper" class="page-fade">
