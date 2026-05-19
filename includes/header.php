<?php
/**
 * Header include
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

// Fetch site settings
$settings = [];
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {
    // Fallback if table doesn't exist
}

$site_name = $settings['site_name'] ?? 'ASO Online Market';
$primary_color = $settings['primary_color'] ?? '#1A1A1A'; // FreshTech Premium Black

// Define root URL for JS
$root_url = SITE_URL;
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) : 'Home'; ?> - <?php echo htmlspecialchars($site_name); ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?php echo $base; ?>assets/images/logo-rounded.png" />

    <!-- PWA Manifest & Meta Tags -->
    <link rel="manifest" href="<?php echo $base; ?>manifest.json" />
    <meta name="theme-color" content="<?php echo $primary_color; ?>" />
    <meta name="mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-status-bar-style" content="default" />
    <meta name="apple-mobile-web-app-title" content="<?php echo htmlspecialchars($site_name); ?>" />
    <link rel="apple-touch-icon" href="<?php echo $base; ?>assets/images/logo-rounded.png" />
    
    <!-- Tailwind CSS with Plugins -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
    
    <script>
        window.SHOP_URL = '<?php echo SITE_URL; ?>';
        
        // PWA Service Worker Registration
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                const swPath = window.SHOP_URL ? window.SHOP_URL + 'service-worker.js' : 'service-worker.js';
                navigator.serviceWorker.register(swPath)
                    .then(reg => console.log('Service Worker registered.'))
                    .catch(err => console.error('Service Worker registration failed:', err));
            });
        }
    </script>
    
    <!-- Swiper.js -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    
    <script id="tailwind-config">
      tailwind.config = {
        darkMode: "class",
        theme: {
          extend: {
            "colors": {
                    "on-secondary-fixed-variant": "#334d37",
                    "secondary": "#4a654e",
                    "on-error": "#ffffff",
                    "surface-tint": "#1b6d24",
                    "secondary-container": "#c9e7ca",
                    "tertiary-fixed": "#a6eff3",
                    "secondary-fixed-dim": "#b1ceb2",
                    "tertiary-fixed-dim": "#8ad3d7",
                    "surface-container": "#F8FAFC", // Slate 50
                    "tertiary": "#005f63",
                    "on-surface-variant": "#475569", // Slate 600
                    "on-tertiary-fixed-variant": "#004f53",
                    "tertiary-container": "#2b787c",
                    "surface-bright": "#FFFFFF",
                    "background": "#F8FAFC", // Slate 50
                    "surface-container-high": "#F1F5F9", // Slate 100
                    "on-primary-fixed-variant": "#005312",
                    "on-primary-fixed": "#002204",
                    "primary": "<?php echo $primary_color; ?>",
                    "on-primary": "#ffffff",
                    "inverse-primary": "#88d982",
                    "on-secondary-container": "#4e6952",
                    "surface-container-highest": "#E2E8F0", // Slate 200
                    "on-tertiary": "#ffffff",
                    "primary-fixed": "#a3f69c",
                    "on-secondary": "#ffffff",
                    "surface": "#F8FAFC",
                    "on-background": "#0F172A", // Slate 900
                    "outline-variant": "#E2E8F0",
                    "inverse-on-surface": "#F8FAFC",
                    "on-error-container": "#93000a",
                    "surface-variant": "#F1F5F9",
                    "on-tertiary-fixed": "#002021",
                    "surface-dim": "#F1F5F9",
                    "outline": "#64748B", // Slate 500
                    "on-tertiary-container": "#bffcff",
                    "primary-fixed-dim": "#88d982",
                    "secondary-fixed": "#cceacd",
                    "primary-container": "<?php echo $primary_color; ?>",
                    "on-surface": "#0F172A", // Slate 900
                    "surface-container-low": "#F1F5F9", // Slate 100
                    "surface-container-lowest": "#ffffff",
                    "inverse-surface": "#263238",
                    "error": "#ba1a1a",
                    "on-primary-container": "#cbffc2",
                    "on-secondary-fixed": "#07200e",
                    "error-container": "#ffdad6"
            },
            "borderRadius": {
                    "DEFAULT": "0.25rem",
                    "lg": "0.5rem",
                    "xl": "0.75rem",
                    "full": "9999px"
            },
            "spacing": {
                    "gutter": "24px",
                    "lg": "48px",
                    "container-max": "1280px",
                    "xl": "80px",
                    "base": "8px",
                    "md": "24px",
                    "sm": "12px",
                    "xs": "4px",
                    "margin-mobile": "16px"
            },
            "fontFamily": {
                    "body-lg": ["Inter"],
                    "headline-md": ["Inter"],
                    "label-sm": ["Inter"],
                    "body-sm": ["Inter"],
                    "body-md": ["Inter"],
                    "headline-lg-mobile": ["Inter"],
                    "headline-lg": ["Inter"],
                    "headline-xl-mobile": ["Inter"],
                    "headline-xl": ["Inter"],
                    "label-lg": ["Inter"]
            },
            "fontSize": {
                    "body-lg": ["18px", {"lineHeight": "28px", "fontWeight": "400"}],
                    "headline-md": ["24px", {"lineHeight": "32px", "fontWeight": "600"}],
                    "label-sm": ["12px", {"lineHeight": "16px", "letterSpacing": "0.02em", "fontWeight": "500"}],
                    "body-sm": ["14px", {"lineHeight": "20px", "fontWeight": "400"}],
                    "body-md": ["16px", {"lineHeight": "24px", "fontWeight": "400"}],
                    "headline-lg-mobile": ["24px", {"lineHeight": "32px", "letterSpacing": "-0.01em", "fontWeight": "600"}],
                    "headline-lg": ["32px", {"lineHeight": "40px", "letterSpacing": "-0.01em", "fontWeight": "600"}],
                    "headline-xl-mobile": ["32px", {"lineHeight": "40px", "letterSpacing": "-0.02em", "fontWeight": "700"}],
                    "headline-xl": ["40px", {"lineHeight": "48px", "letterSpacing": "-0.02em", "fontWeight": "700"}],
                    "label-lg": ["14px", {"lineHeight": "20px", "letterSpacing": "0.01em", "fontWeight": "600"}]
            }
          },
        },
      }
    </script>
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        .material-symbols-outlined.fill-1 {
            font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        .hide-scrollbar::-webkit-scrollbar { display: none; }
        .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="bg-background text-on-background font-body-md min-h-screen flex flex-col">
    <?php if (!empty($settings['announcement_text'])): ?>
        <div class="bg-primary text-white py-2 px-4 text-center text-[12px] font-bold tracking-wide">
            <?php echo htmlspecialchars($settings['announcement_text']); ?>
        </div>
    <?php endif; ?>
    <?php include __DIR__ . '/navbar.php'; ?>
