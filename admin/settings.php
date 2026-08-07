<?php
/**
 * Admin: Unified Settings Dashboard
 * - Consolidates site identity, UI density, social links, and hero slider management.
 */
require_once '../includes/db.php';
session_start();
require_once '../includes/admin_guard.php';

$page_title = 'Site Settings';
$errors = [];
$success = '';

/**
 * Handle image upload for hero slides
 */
function handleHeroUpload($fieldName, $existing = '') {
    $targetDir = realpath(__DIR__ . '/../assets/images');
    if (!is_dir($targetDir)) {
        @mkdir($targetDir, 0777, true);
    }
    if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
        return $existing;
    }
    $fileTmp = $_FILES[$fieldName]['tmp_name'];
    $origName = basename($_FILES[$fieldName]['name']);
    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','gif','webp'];
    if (!in_array($ext, $allowed)) {
        return $existing;
    }
    $newName = 'hero_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = $targetDir . DIRECTORY_SEPARATOR . $newName;
    if (move_uploaded_file($fileTmp, $dest)) {
        return $newName;
    }
    return $existing;
}

// Fetch all settings
$settings = [];
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {
    error_log("Fetch settings error: " . $e->getMessage());
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $errors[] = 'Invalid form submission. Please refresh and try again.';
        } else {
        // Handle General / UI / Social Settings
        if ($action === 'save_general') {
            $allowed_keys = [
                'site_name', 'primary_color', 'products_per_row',
                'announcement_text', 'footer_notice',
                'social_facebook', 'social_instagram', 'social_twitter', 'social_tiktok', 'social_youtube', 'social_whatsapp',
                'flash_sale_enabled', 'flash_sale_end_time', 'flash_sale_product_ids', 'homepage_hero_style', 'preorder_deposit_pct'
            ];
            
            $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
            
            foreach ($allowed_keys as $key) {
                if (isset($_POST[$key])) {
                    $val = $_POST[$key];
                    $stmt->execute([$key, $val]);
                    $settings[$key] = $val; // Update local array
                }
            }
            $success = 'General settings updated successfully';
        }
        
        // Handle Hero Slide CRUD (Integrated from manage_hero.php)
        elseif ($action === 'create_slide' || $action === 'update_slide') {
            $id = (int)($_POST['slide_id'] ?? 0);
            $badge_text = sanitizeInput($_POST['badge_text'] ?? '');
            $title_black = sanitizeInput($_POST['title_black'] ?? '');
            $title_gray = sanitizeInput($_POST['title_gray'] ?? '');
            $description = sanitizeInput($_POST['description'] ?? '');
            $button_text = sanitizeInput($_POST['button_text'] ?? 'Shop Now');
            $button_link = sanitizeInput($_POST['button_link'] ?? 'shop.php');
            $secondary_button_text = sanitizeInput($_POST['secondary_button_text'] ?? '');
            $secondary_button_link = sanitizeInput($_POST['secondary_button_link'] ?? '');
            $display_order = (int)($_POST['display_order'] ?? 0);
            $is_active = isset($_POST['is_active']) ? 1 : 0;

            if (!$title_black) $errors[] = 'Main title is required';
            
            if (empty($errors)) {
                $image = handleHeroUpload('image', $_POST['existing_image'] ?? '');
                $card_bg = $_POST['card_bg'] ?? '#FFFFFF';
                $text_color = $_POST['text_color'] ?? '#1A1A1A';
                
                if ($action === 'create_slide') {
                    $stmt = $pdo->prepare("INSERT INTO hero_slides (badge_text, title_black, title_gray, description, button_text, button_link, secondary_button_text, secondary_button_link, image_path, card_bg, text_color, display_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$badge_text, $title_black, $title_gray, $description, $button_text, $button_link, $secondary_button_text, $secondary_button_link, $image, $card_bg, $text_color, $display_order, $is_active]);
                    $success = 'Hero slide added successfully';
                } else {
                    $stmt = $pdo->prepare("UPDATE hero_slides SET badge_text = ?, title_black = ?, title_gray = ?, description = ?, button_text = ?, button_link = ?, secondary_button_text = ?, secondary_button_link = ?, image_path = ?, card_bg = ?, text_color = ?, display_order = ?, is_active = ? WHERE id = ?");
                    $stmt->execute([$badge_text, $title_black, $title_gray, $description, $button_text, $button_link, $secondary_button_text, $secondary_button_link, $image, $card_bg, $text_color, $display_order, $is_active, $id]);
                    $success = 'Hero slide updated successfully';
                }
            }
        } elseif ($action === 'create_promo' || $action === 'update_promo') {
            $id = $_POST['promo_id'] ?? null;
            $product_id = !empty($_POST['product_id']) ? (int)$_POST['product_id'] : null;
            $badge_text = $_POST['badge_text'] ?? '';
            $title = $_POST['title'] ?? '';
            $subtitle = $_POST['subtitle'] ?? '';
            $price_text = $_POST['price_text'] ?? '';
            $button_text = $_POST['button_text'] ?? '';
            $button_link = $_POST['button_link'] ?? '';
            $card_bg = $_POST['card_bg'] ?? '#F2F4F7';
            $text_color = $_POST['text_color'] ?? '#1A1A1A';
            $badge_color = $_POST['badge_color'] ?? '#666666';
            $is_button = isset($_POST['is_button']) ? 1 : 0;
            $display_order = $_POST['display_order'] ?? 0;
            $is_active = isset($_POST['is_active']) ? 1 : 0;

            if (empty($title) && empty($product_id)) $errors[] = 'Either Title or Linked Product is required';

            if (empty($errors)) {
                $image = handleHeroUpload('image', $_POST['existing_image'] ?? '');
                
                if ($action === 'create_promo') {
                    $stmt = $pdo->prepare("INSERT INTO promo_cards (product_id, badge_text, title, subtitle, price_text, button_text, button_link, image_path, card_bg, text_color, badge_color, is_button, display_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$product_id, $badge_text, $title, $subtitle, $price_text, $button_text, $button_link, $image, $card_bg, $text_color, $badge_color, $is_button, $display_order, $is_active]);
                    $success = 'Promo card added successfully';
                } else {
                    $stmt = $pdo->prepare("UPDATE promo_cards SET product_id = ?, badge_text = ?, title = ?, subtitle = ?, price_text = ?, button_text = ?, button_link = ?, image_path = ?, card_bg = ?, text_color = ?, badge_color = ?, is_button = ?, display_order = ?, is_active = ? WHERE id = ?");
                    $stmt->execute([$product_id, $badge_text, $title, $subtitle, $price_text, $button_text, $button_link, $image, $card_bg, $text_color, $badge_color, $is_button, $display_order, $is_active, $id]);
                    $success = 'Promo card updated successfully';
                }
            }
        } elseif ($action === 'delete_promo') {
            $id = (int)($_POST['promo_id'] ?? 0);
            if ($id > 0) {
                $stmt = $pdo->prepare("SELECT image_path FROM promo_cards WHERE id = ?");
                $stmt->execute([$id]);
                $img = $stmt->fetchColumn();
                if ($img && strpos($img, 'assets/') !== 0) {
                    $filePath = realpath(__DIR__ . '/../assets/images/') . DIRECTORY_SEPARATOR . $img;
                    if (file_exists($filePath)) @unlink($filePath);
                }
                $pdo->prepare("DELETE FROM promo_cards WHERE id = ?")->execute([$id]);
                $success = 'Promo card deleted successfully';
            }
        } elseif ($action === 'delete_slide') {
            $id = (int)($_POST['slide_id'] ?? 0);
            if ($id > 0) {
                // Delete image file
                $stmt = $pdo->prepare("SELECT image_path FROM hero_slides WHERE id = ?");
                $stmt->execute([$id]);
                $img = $stmt->fetchColumn();
                if ($img && strpos($img, 'assets/') !== 0) {
                    $filePath = realpath(__DIR__ . '/../assets/images/') . DIRECTORY_SEPARATOR . $img;
                    if (file_exists($filePath)) @unlink($filePath);
                }
                
                $stmt =                 $pdo->prepare("DELETE FROM hero_slides WHERE id = ?");
                $stmt->execute([$id]);
                $success = 'Slide deleted successfully';
            }
        } elseif ($action === 'save_shipping_zones') {
            // Upsert each zone row from the grid
            $zone_names   = $_POST['zone_name']   ?? [];
            $zone_types   = $_POST['zone_type']   ?? [];
            $zone_rates   = $_POST['flat_rate']   ?? [];
            $zone_free    = $_POST['free_threshold'] ?? [];
            $zone_est     = $_POST['estimated_days'] ?? [];
            $zone_flags   = $_POST['flag_emoji'] ?? [];
            $zone_active  = $_POST['zone_active'] ?? [];
            $zone_ccodes  = $_POST['country_codes'] ?? [];

            $stmt_u = $pdo->prepare("UPDATE shipping_zones SET zone_name = ?, zone_type = ?, flat_rate = ?, free_threshold = ?, estimated_days = ?, flag_emoji = ?, is_active = ?, country_codes = ? WHERE zone_id = ?");
            foreach ((array)$zone_names as $i => $name) {
                $zid = (int)($_POST['zone_id'][$i] ?? 0);
                if ($zid <= 0) continue;
                $rate = (float)($zone_rates[$i] ?? 0);
                $free = isset($zone_free[$i]) && $zone_free[$i] !== '' ? (float)$zone_free[$i] : null;
                $ccodes = isset($zone_ccodes[$i]) && trim($zone_ccodes[$i]) !== '' ? trim($zone_ccodes[$i]) : null;
                $stmt_u->execute([
                    sanitizeInput($name),
                    sanitizeInput($zone_types[$i] ?? 'domestic') === 'international' ? 'international' : 'domestic',
                    $rate,
                    $free,
                    sanitizeInput($zone_est[$i] ?? ''),
                    sanitizeInput($zone_flags[$i] ?? ''),
                    isset($zone_active[$i]) ? 1 : 0,
                    $ccodes,
                    $zid
                ]);
            }

            // Create a new zone if a name was provided
            if (!empty(trim($_POST['new_zone_name'] ?? ''))) {
                $stmt_i = $pdo->prepare("INSERT INTO shipping_zones (zone_name, zone_type, country_codes, flat_rate, free_threshold, estimated_days, flag_emoji, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, (SELECT COALESCE(MAX(sort_order),0)+1 FROM shipping_zones), 1)");
                $stmt_i->execute([
                    sanitizeInput($_POST['new_zone_name']),
                    ($_POST['new_zone_type'] ?? 'international') === 'international' ? 'international' : 'domestic',
                    !empty(trim($_POST['new_country_codes'] ?? '')) ? trim($_POST['new_country_codes']) : null,
                    (float)($_POST['new_flat_rate'] ?? 0),
                    isset($_POST['new_free_threshold']) && $_POST['new_free_threshold'] !== '' ? (float)$_POST['new_free_threshold'] : null,
                    sanitizeInput($_POST['new_estimated_days'] ?? ''),
                    sanitizeInput($_POST['new_flag_emoji'] ?? ''),
                ]);
            }

            // Delete zones marked for removal
            foreach ((array)($_POST['delete_zone'] ?? []) as $zid) {
                $pdo->prepare("DELETE FROM shipping_zones WHERE zone_id = ?")->execute([(int)$zid]);
            }

            $success = 'Shipping zones updated successfully';
        } elseif ($action === 'save_ad_banner') {
            ensureAdBannersSchema($pdo);
            $ad_id = (int)($_POST['ad_id'] ?? 0);
            $ad_title = sanitizeInput($_POST['ad_title'] ?? '');
            $ad_description = sanitizeInput($_POST['ad_description'] ?? '');
            $ad_btn = sanitizeInput($_POST['ad_button_text'] ?? 'Shop Now');
            $ad_link = sanitizeInput($_POST['ad_button_link'] ?? 'shop.php');
            $ad_order = (int)($_POST['ad_display_order'] ?? 0);
            $ad_active = isset($_POST['ad_is_active']) ? 1 : 0;
            $ad_image = sanitizeInput($_POST['existing_ad_image'] ?? '');

            // Handle uploaded image, if any
            if (isset($_FILES['ad_image'])) {
                $fErr = $_FILES['ad_image']['error'];
                if ($fErr === UPLOAD_ERR_OK) {
                    $adsDir = __DIR__ . '/../assets/images/ads';
                    if (!is_dir($adsDir)) { @mkdir($adsDir, 0755, true); }
                    $targetDir = realpath($adsDir);
                    if (!$targetDir || !is_dir($targetDir)) {
                        $errors[] = 'Could not create the ad image upload folder (assets/images/ads).';
                    } else {
                        $fileTmp = $_FILES['ad_image']['tmp_name'];
                        $origName = basename($_FILES['ad_image']['name']);
                        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
                        $allowed = ['jpg','jpeg','png','gif','webp'];
                        if (!in_array($ext, $allowed)) {
                            $errors[] = 'Ad image must be JPG, PNG, GIF or WebP';
                        } else {
                            $newName = 'ad_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                            $dest = $targetDir . DIRECTORY_SEPARATOR . $newName;
                            if (move_uploaded_file($fileTmp, $dest)) {
                                // Clean up old image on update
                                if ($ad_id > 0 && $ad_image && strpos($ad_image, 'http') !== 0) {
                                    $oldPath = realpath(__DIR__ . '/../assets/images/') . DIRECTORY_SEPARATOR . $ad_image;
                                    if (file_exists($oldPath)) @unlink($oldPath);
                                }
                                $ad_image = 'ads/' . $newName;
                            } else {
                                $errors[] = 'Failed to move the uploaded image. Check folder permissions on assets/images/ads.';
                            }
                        }
                    }
                }
            }

            if ($ad_id === 0 && empty($ad_image) && empty($errors)) {
                $errors[] = 'An ad banner image is required';
            }

            if (empty($errors)) {
                $stmt = ($ad_id > 0)
                    ? $pdo->prepare("UPDATE ad_banners SET image_path = ?, title = ?, description = ?, button_text = ?, button_link = ?, display_order = ?, is_active = ? WHERE id = ?")
                    : $pdo->prepare("INSERT INTO ad_banners (image_path, title, description, button_text, button_link, display_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $params = [$ad_image, $ad_title, $ad_description, $ad_btn, $ad_link, $ad_order, $ad_active];
                if ($ad_id > 0) $params[] = $ad_id;
                $stmt->execute($params);
                $success = $ad_id > 0 ? 'Ad banner updated successfully' : 'Ad banner created successfully';
            }
        } elseif ($action === 'delete_ad_banner') {
            $ad_id = (int)($_POST['ad_id'] ?? 0);
            if ($ad_id > 0) {
                $stmt = $pdo->prepare("SELECT image_path FROM ad_banners WHERE id = ?");
                $stmt->execute([$ad_id]);
                $img = $stmt->fetchColumn();
                if ($img && strpos($img, 'assets/') !== 0 && strpos($img, 'http') !== 0) {
                    $filePath = realpath(__DIR__ . '/../assets/images/') . DIRECTORY_SEPARATOR . $img;
                    if (file_exists($filePath)) @unlink($filePath);
                }
                $pdo->prepare("DELETE FROM ad_banners WHERE id = ?")->execute([$ad_id]);
                $success = 'Ad banner deleted successfully';
            }
        }
        } // end else (CSRF valid)
    }
} catch (PDOException $e) {
    $errors[] = 'Database error: ' . $e->getMessage();
}

// Fetch all slides for the Hero tab
$slides = $pdo->query("SELECT * FROM hero_slides ORDER BY display_order ASC, created_at DESC")->fetchAll();

// Editing slide helper
$edit_slide = null;
if ($action === 'edit_slide' && isset($_GET['slide_id'])) {
    $sid = (int)$_GET['slide_id'];
    foreach ($slides as $s) {
        if ($s['id'] == $sid) {
            $edit_slide = $s;
            break;
        }
    }
}

$active_tab = 'branding';
if ($action === 'edit_slide' || $edit_slide) $active_tab = 'hero';
if (isset($_GET['action']) && $_GET['action'] === 'edit_promo') $active_tab = 'promo';
if (isset($_GET['action']) && $_GET['action'] === 'edit_ad') $active_tab = 'adbanners';

// Shipping zones data for the Shipping tab
$shipping_zones = [];
try {
    $shipping_zones = $pdo->query("SELECT * FROM shipping_zones ORDER BY sort_order ASC, zone_id ASC")->fetchAll();
} catch (PDOException $e) {
    error_log("Fetch shipping zones error: " . $e->getMessage());
}

// Ad banners data for the Ad Banners tab
ensureAdBannersSchema($pdo);
$ad_banners = [];
try {
    $ad_banners = $pdo->query("SELECT * FROM ad_banners ORDER BY display_order ASC, id ASC")->fetchAll();
} catch (PDOException $e) {
    error_log("Fetch ad banners error: " . $e->getMessage());
}

// Editing ad banner helper
$edit_ad = null;
if ($action === 'edit_ad' && isset($_GET['ad_id'])) {
    $aid = (int)$_GET['ad_id'];
    foreach ($ad_banners as $a) {
        if ((int)$a['id'] === $aid) {
            $edit_ad = $a;
            break;
        }
    }
}

include 'includes/avazonia_header.php';
?>
<style>
.settings-grid .panel:last-child { margin-bottom: 0; }
.hero-thumb { width: 80px; height: 50px; object-fit: cover; border: 1px solid var(--light-gray); border-radius: 4px; }
.promo-box { display: flex; align-items: center; justify-content: center; width: 60px; height: 60px; border: 1px solid var(--light-gray); border-radius: 6px; }
.promo-box img { max-width: 100%; max-height: 100%; object-fit: contain; }
</style>

<div class="settings-layout">
    <nav class="settings-nav">
        <button class="settings-tab-btn <?php echo $active_tab === 'branding' ? 'active' : ''; ?>" data-target="branding"><span>01</span>Branding &amp; UI</button>
        <button class="settings-tab-btn <?php echo $active_tab === 'social' ? 'active' : ''; ?>" data-target="social"><span>02</span>Social Links</button>
        <button class="settings-tab-btn <?php echo $active_tab === 'hero' ? 'active' : ''; ?>" data-target="hero"><span>03</span>Hero Slider</button>
        <button class="settings-tab-btn <?php echo $active_tab === 'promo' ? 'active' : ''; ?>" data-target="promo"><span>04</span>Promo Grid</button>
        <button class="settings-tab-btn <?php echo $active_tab === 'flash' ? 'active' : ''; ?>" data-target="flash"><span>05</span>Flash Sale</button>
        <button class="settings-tab-btn <?php echo $active_tab === 'shipping' ? 'active' : ''; ?>" data-target="shipping"><span>06</span>Shipping Zones</button>
        <button class="settings-tab-btn <?php echo $active_tab === 'adbanners' ? 'active' : ''; ?>" data-target="adbanners"><span>07</span>Ad Banners</button>
    </nav>

    <div class="settings-content-area">
        <?php if ($success): ?>
            <div class="alert-box alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if ($errors): ?>
            <div class="alert-box alert-error">
                <ul style="margin: 0; padding-left: 20px;">
                    <?php foreach ($errors as $e): ?><li><?php echo htmlspecialchars($e); ?></li><?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Branding & UI -->
        <section class="settings-section <?php echo $active_tab === 'branding' ? 'active' : ''; ?>" id="branding">
            <div class="section-header">
                <h2>Branding &amp; UI</h2>
                <p>Core identity, brand color, and grid density that define the storefront.</p>
            </div>
            <form method="POST">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="save_general">
                <div class="settings-grid">
                    <div class="panel">
                        <div class="panel-header"><div class="panel-title">Core Identity</div></div>
                        <div class="panel-body">
                            <div class="field-group">
                                <label class="field-label">Marketplace Name</label>
                                <input type="text" name="site_name" class="field-input" value="<?php echo htmlspecialchars($settings['site_name'] ?? 'ASO Online Market'); ?>">
                            </div>
                            <div class="field-group">
                                <label class="field-label">Primary Brand Color</label>
                                <div class="field-grid">
                                    <input type="color" name="primary_color" class="color-input" value="<?php echo htmlspecialchars($settings['primary_color'] ?? '#1A1A1A'); ?>">
                                    <input type="text" class="field-input" style="font-family: var(--f-mono);" value="<?php echo htmlspecialchars($settings['primary_color'] ?? '#1A1A1A'); ?>" readonly>
                                </div>
                            </div>
                            <div class="field-group">
                                <label class="field-label">Products Per Row (Desktop)</label>
                                <select name="products_per_row" class="field-input">
                                    <?php for($i=2; $i<=6; $i++): ?>
                                        <option value="<?php echo $i; ?>" <?php echo (($settings['products_per_row'] ?? '6') == $i) ? 'selected' : ''; ?>><?php echo $i; ?> Products</option>
                                    <?php endfor; ?>
                                </select>
                                <span class="field-sub">Adjusts the grid density on the homepage and shop.</span>
                            </div>
                            <div class="field-group" style="margin-bottom: 0;">
                                <label class="field-label">Homepage Hero Layout Style</label>
                                <select name="homepage_hero_style" class="field-input">
                                    <option value="carousel" <?php echo (($settings['homepage_hero_style'] ?? 'carousel') === 'carousel') ? 'selected' : ''; ?>>Interactive Carousel (Categories Showcase)</option>
                                    <option value="split" <?php echo (($settings['homepage_hero_style'] ?? 'split') === 'split') ? 'selected' : ''; ?>>Minimal Split Screen (Trust Signals &amp; CTA)</option>
                                    <option value="gradient" <?php echo (($settings['homepage_hero_style'] ?? 'gradient') === 'gradient') ? 'selected' : ''; ?>>Animated Brand Gradient (High Energy Campaign)</option>
                                    <option value="circular" <?php echo (($settings['homepage_hero_style'] ?? 'circular') === 'circular') ? 'selected' : ''; ?>>Facing Deck: Circular Rotate</option>
                                    <option value="sidebyside" <?php echo (($settings['homepage_hero_style'] ?? 'sidebyside') === 'sidebyside') ? 'selected' : ''; ?>>Facing Deck: Side by Side Flip</option>
                                    <option value="stacked" <?php echo (($settings['homepage_hero_style'] ?? 'stacked') === 'stacked') ? 'selected' : ''; ?>>Facing Deck: Stacked Slide</option>
                                    <option value="orbit" <?php echo (($settings['homepage_hero_style'] ?? 'orbit') === 'orbit') ? 'selected' : ''; ?>>Facing Deck: Orbit Motion</option>
                                </select>
                                <span class="field-sub">Pick which responsive layout structure renders for your homepage top header.</span>
                            </div>
                        </div>
                    </div>
                    <div class="panel">
                        <div class="panel-header"><div class="panel-title">Interface Content</div></div>
                        <div class="panel-body">
                            <div class="field-group">
                                <label class="field-label">Announcement Bar Text</label>
                                <input type="text" name="announcement_text" class="field-input" value="<?php echo htmlspecialchars($settings['announcement_text'] ?? ''); ?>" placeholder="e.g. Free delivery on orders over GH₵500!">
                            </div>
                            <div class="field-group" style="margin-bottom: 0;">
                                <label class="field-label">Footer Copyright Notice</label>
                                <input type="text" name="footer_notice" class="field-input" value="<?php echo htmlspecialchars($settings['footer_notice'] ?? ''); ?>" placeholder="e.g. © 2026 ASO Online Market. All rights reserved.">
                            </div>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn-red" style="margin-top: 24px;">Save Visual Changes</button>
            </form>
        </section>

        <!-- Social Links -->
        <section class="settings-section <?php echo $active_tab === 'social' ? 'active' : ''; ?>" id="social">
            <div class="section-header">
                <h2>Social Links</h2>
                <p>Where customers can follow and reach the marketplace.</p>
            </div>
            <form method="POST">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="save_general">
                <div class="panel">
                    <div class="panel-header"><div class="panel-title">Social Connectivity</div></div>
                    <div class="panel-body">
                        <div class="field-grid">
                            <div class="field-group">
                                <label class="field-label">Facebook Profile</label>
                                <input type="url" name="social_facebook" class="field-input" value="<?php echo htmlspecialchars($settings['social_facebook'] ?? ''); ?>">
                            </div>
                            <div class="field-group">
                                <label class="field-label">Instagram Handle</label>
                                <input type="url" name="social_instagram" class="field-input" value="<?php echo htmlspecialchars($settings['social_instagram'] ?? ''); ?>">
                            </div>
                            <div class="field-group">
                                <label class="field-label">Twitter / X</label>
                                <input type="url" name="social_twitter" class="field-input" value="<?php echo htmlspecialchars($settings['social_twitter'] ?? ''); ?>">
                            </div>
                            <div class="field-group">
                                <label class="field-label">TikTok Profile</label>
                                <input type="url" name="social_tiktok" class="field-input" value="<?php echo htmlspecialchars($settings['social_tiktok'] ?? ''); ?>">
                            </div>
                            <div class="field-group">
                                <label class="field-label">YouTube Channel</label>
                                <input type="url" name="social_youtube" class="field-input" value="<?php echo htmlspecialchars($settings['social_youtube'] ?? ''); ?>">
                            </div>
                            <div class="field-group">
                                <label class="field-label">WhatsApp (Number or Link)</label>
                                <input type="text" name="social_whatsapp" class="field-input" placeholder="e.g. +233240987670 or https://wa.me/..." value="<?php echo htmlspecialchars($settings['social_whatsapp'] ?? ''); ?>">
                            </div>
                        </div>
                        <button type="submit" class="btn-red" style="margin-top: 8px;">Save Social Links</button>
                    </div>
                </div>
            </form>
        </section>

        <!-- Hero Slider -->
        <section class="settings-section <?php echo $active_tab === 'hero' ? 'active' : ''; ?>" id="hero">
            <div class="section-header">
                <h2>Hero Slider</h2>
                <p>Manage the rotating banners at the top of the homepage.</p>
            </div>
            <div class="settings-grid">
                <div class="panel">
                    <div class="panel-header"><div class="panel-title"><?php echo $edit_slide ? 'Edit Slide' : 'New Slide'; ?></div></div>
                    <div class="panel-body">
                        <form method="POST" enctype="multipart/form-data">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="action" value="<?php echo $edit_slide ? 'update_slide' : 'create_slide'; ?>">
                            <?php if ($edit_slide): ?>
                                <input type="hidden" name="slide_id" value="<?php echo $edit_slide['id']; ?>">
                                <input type="hidden" name="existing_image" value="<?php echo $edit_slide['image_path']; ?>">
                            <?php endif; ?>
                            <div class="field-group">
                                <label class="field-label">Main Title</label>
                                <input type="text" name="title_black" class="field-input" value="<?php echo $edit_slide['title_black'] ?? ''; ?>" required>
                            </div>
                            <div class="field-group">
                                <label class="field-label">Subtitle (Gray Text)</label>
                                <input type="text" name="title_gray" class="field-input" value="<?php echo $edit_slide['title_gray'] ?? ''; ?>">
                            </div>
                            <div class="field-group">
                                <label class="field-label">Badge Text</label>
                                <input type="text" name="badge_text" class="field-input" value="<?php echo $edit_slide['badge_text'] ?? ''; ?>">
                            </div>
                            <div class="field-group">
                                <label class="field-label">Slide Image</label>
                                <input type="file" name="image" class="field-input" <?php echo !$edit_slide ? 'required' : ''; ?>>
                                <?php if ($edit_slide): ?>
                                    <div class="d-flex align-items-center gap-2" style="margin-top: 12px;">
                                        <img src="<?php echo (strpos($edit_slide['image_path'], 'assets/') === 0) ? '../'.$edit_slide['image_path'] : '../assets/images/'.$edit_slide['image_path']; ?>" class="hero-thumb" alt="bg">
                                        <span class="field-sub" style="margin: 0;">Current Active Background</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="field-grid">
                                <div class="field-group">
                                    <label class="field-label">Card Background</label>
                                    <input type="color" name="card_bg" class="color-input" value="<?php echo $edit_slide['card_bg'] ?? '#FFFFFF'; ?>">
                                </div>
                                <div class="field-group">
                                    <label class="field-label">Primary Text Color</label>
                                    <input type="color" name="text_color" class="color-input" value="<?php echo $edit_slide['text_color'] ?? '#1A1A1A'; ?>">
                                </div>
                            </div>
                            <div class="field-grid">
                                <div class="field-group">
                                    <label class="field-label">Primary Btn Text</label>
                                    <input type="text" name="button_text" class="field-input" value="<?php echo $edit_slide['button_text'] ?? 'Shop Now'; ?>">
                                </div>
                                <div class="field-group">
                                    <label class="field-label">Primary Btn Link</label>
                                    <input type="text" name="button_link" class="field-input" value="<?php echo $edit_slide['button_link'] ?? 'shop.php'; ?>">
                                </div>
                            </div>
                            <div class="field-grid">
                                <div class="field-group">
                                    <label class="field-label">Secondary Btn Text (Optional)</label>
                                    <input type="text" name="secondary_button_text" class="field-input" value="<?php echo $edit_slide['secondary_button_text'] ?? ''; ?>" placeholder="e.g. Our Farms">
                                </div>
                                <div class="field-group">
                                    <label class="field-label">Secondary Btn Link</label>
                                    <input type="text" name="secondary_button_link" class="field-input" value="<?php echo $edit_slide['secondary_button_link'] ?? ''; ?>" placeholder="shop.php">
                                </div>
                            </div>
                            <div class="field-grid">
                                <div class="field-group">
                                    <label class="field-label">Order</label>
                                    <input type="number" name="display_order" class="field-input" value="<?php echo $edit_slide['display_order'] ?? 0; ?>">
                                </div>
                                <div class="check-row">
                                    <input type="checkbox" name="is_active" value="1" class="field-check" <?php echo (!isset($edit_slide['is_active']) || $edit_slide['is_active']) ? 'checked' : ''; ?>>
                                    <label class="field-label" style="margin: 0;">Slide Active</label>
                                </div>
                            </div>
                            <button type="submit" class="btn-red w-100" style="justify-content: center;"><?php echo $edit_slide ? 'Update Slide' : 'Create Slide'; ?></button>
                            <?php if ($edit_slide): ?>
                                <a href="settings.php#hero" class="btn-ink w-100" style="justify-content: center; margin-top: 12px;">Cancel Editing</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
                <div class="panel">
                    <div class="panel-header"><div class="panel-title">Active Slides</div></div>
                    <div class="table-container" style="border: none; margin-bottom: 0; border-radius: 0;">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Preview</th>
                                    <th>Title</th>
                                    <th style="text-align: center;">Order</th>
                                    <th style="text-align: right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($slides as $s): ?>
                                <tr>
                                    <td>
                                        <img src="<?php echo (strpos($s['image_path'], 'assets/') === 0) ? '../'.$s['image_path'] : '../assets/images/'.$s['image_path']; ?>" class="hero-thumb" alt="slide">
                                    </td>
                                    <td>
                                        <div style="font-weight: 800; font-size: 14px;"><?php echo htmlspecialchars($s['title_black']); ?></div>
                                        <div style="font-size: 10px; opacity: 0.5; font-family: var(--f-mono); text-transform: uppercase; letter-spacing: 0.08em; margin-top: 2px;"><?php echo htmlspecialchars($s['badge_text']); ?></div>
                                    </td>
                                    <td style="text-align: center;">
                                        <span class="order-badge"><?php echo $s['display_order']; ?></span>
                                    </td>
                                    <td style="text-align: right;">
                                        <div class="d-flex justify-content-end gap-2">
                                            <a href="settings.php?action=edit_slide&slide_id=<?php echo $s['id']; ?>#hero" class="action-btn">Edit</a>
                                            <form method="POST" class="d-inline" onsubmit="return confirmAction(event, 'Delete slide?');">
                                                <?php echo csrfField(); ?>
                                                <input type="hidden" name="action" value="delete_slide">
                                                <input type="hidden" name="slide_id" value="<?php echo $s['id']; ?>">
                                                <button type="submit" class="action-btn danger">Del</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>

        <!-- Promo Grid -->
        <section class="settings-section <?php echo $active_tab === 'promo' ? 'active' : ''; ?>" id="promo">
            <?php
            $edit_promo = null;
            if (isset($_GET['action']) && $_GET['action'] === 'edit_promo' && isset($_GET['promo_id'])) {
                $stmt = $pdo->prepare("SELECT * FROM promo_cards WHERE id = ?");
                $stmt->execute([$_GET['promo_id']]);
                $edit_promo = $stmt->fetch();
            }
            $all_products = $pdo->query("SELECT product_id, name FROM products ORDER BY name ASC")->fetchAll();
            $promos = $pdo->query("SELECT * FROM promo_cards ORDER BY display_order ASC")->fetchAll();
            ?>
            <div class="section-header">
                <h2>Promo Grid</h2>
                <p>Promotional cards that spotlight products across the homepage.</p>
            </div>
            <div class="settings-grid">
                <div class="panel">
                    <div class="panel-header"><div class="panel-title"><?php echo $edit_promo ? 'Edit Promo Card' : 'Create New Promo'; ?></div></div>
                    <div class="panel-body">
                        <form method="POST" enctype="multipart/form-data">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="action" value="<?php echo $edit_promo ? 'update_promo' : 'create_promo'; ?>">
                            <?php if ($edit_promo): ?>
                                <input type="hidden" name="promo_id" value="<?php echo $edit_promo['id']; ?>">
                                <input type="hidden" name="existing_image" value="<?php echo $edit_promo['image_path']; ?>">
                            <?php endif; ?>
                            <div class="field-group">
                                <label class="field-label">Linked Product (Optional)</label>
                                <select name="product_id" class="field-input">
                                    <option value="">-- No Linked Product --</option>
                                    <?php foreach ($all_products as $ap): ?>
                                        <option value="<?php echo $ap['product_id']; ?>" <?php echo (isset($edit_promo['product_id']) && $edit_promo['product_id'] == $ap['product_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($ap['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <span class="field-sub">If selected, title, price, and image will auto-fill from the product.</span>
                            </div>
                            <div class="field-group">
                                <label class="field-label">Main Title (Override)</label>
                                <input type="text" name="title" class="field-input" value="<?php echo $edit_promo['title'] ?? ''; ?>" placeholder="Leave blank to use product name">
                            </div>
                            <div class="field-grid">
                                <div class="field-group">
                                    <label class="field-label">Subtitle (e.g. From)</label>
                                    <input type="text" name="subtitle" class="field-input" value="<?php echo $edit_promo['subtitle'] ?? 'From'; ?>">
                                </div>
                                <div class="field-group">
                                    <label class="field-label">Price/Value (Override)</label>
                                    <input type="text" name="price_text" class="field-input" value="<?php echo $edit_promo['price_text'] ?? ''; ?>" placeholder="Leave blank to use product price">
                                </div>
                            </div>
                            <div class="field-group">
                                <label class="field-label">Badge Text (e.g. 10% OFF)</label>
                                <input type="text" name="badge_text" class="field-input" value="<?php echo $edit_promo['badge_text'] ?? ''; ?>">
                            </div>
                            <div class="field-group">
                                <label class="field-label">Promo Image (Override Product Image)</label>
                                <input type="file" name="image" class="field-input">
                                <span class="field-sub">Leave blank to use the linked product's image.</span>
                            </div>
                            <div class="field-grid">
                                <div class="field-group">
                                    <label class="field-label">Card BG</label>
                                    <input type="color" name="card_bg" class="color-input" value="<?php echo $edit_promo['card_bg'] ?? '#F2F4F7'; ?>">
                                </div>
                                <div class="field-group">
                                    <label class="field-label">Text Color</label>
                                    <input type="color" name="text_color" class="color-input" value="<?php echo $edit_promo['text_color'] ?? '#1A1A1A'; ?>">
                                </div>
                            </div>
                            <div class="field-grid">
                                <div class="field-group">
                                    <label class="field-label">Badge Color</label>
                                    <input type="color" name="badge_color" class="color-input" value="<?php echo $edit_promo['badge_color'] ?? '#666666'; ?>">
                                </div>
                                <div class="field-group">
                                    <label class="field-label">Order</label>
                                    <input type="number" name="display_order" class="field-input" value="<?php echo $edit_promo['display_order'] ?? 0; ?>">
                                </div>
                            </div>
                            <div class="field-grid">
                                <div class="check-row">
                                    <input type="checkbox" name="is_button" value="1" class="field-check" <?php echo (!isset($edit_promo['is_button']) || $edit_promo['is_button']) ? 'checked' : ''; ?>>
                                    <label class="field-label" style="margin: 0;">As Button</label>
                                </div>
                                <div class="check-row">
                                    <input type="checkbox" name="is_active" value="1" class="field-check" <?php echo (!isset($edit_promo['is_active']) || $edit_promo['is_active']) ? 'checked' : ''; ?>>
                                    <label class="field-label" style="margin: 0;">Active</label>
                                </div>
                            </div>
                            <button type="submit" class="btn-red w-100" style="justify-content: center; margin-top: 8px;"><?php echo $edit_promo ? 'Update Promo Card' : 'Add to Grid'; ?></button>
                            <?php if ($edit_promo): ?>
                                <a href="settings.php#promo" class="btn-ink w-100" style="justify-content: center; margin-top: 12px;">Cancel Editing</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
                <div class="panel">
                    <div class="panel-header"><div class="panel-title">Current Promo Grid</div></div>
                    <div class="table-container" style="border: none; margin-bottom: 0; border-radius: 0;">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Preview</th>
                                    <th>Content</th>
                                    <th style="text-align: center;">Order</th>
                                    <th style="text-align: right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($promos as $p): ?>
                                <tr>
                                    <td>
                                        <div class="promo-box" style="background-color: <?php echo $p['card_bg']; ?>;">
                                            <img src="<?php echo (strpos($p['image_path'], 'assets/') === 0) ? '../'.$p['image_path'] : '../assets/images/'.$p['image_path']; ?>" alt="promo">
                                        </div>
                                    </td>
                                    <td>
                                        <div style="font-weight: 800; font-size: 13px;"><?php echo htmlspecialchars($p['title']); ?></div>
                                        <div style="font-size: 10px; font-family: var(--f-mono); text-transform: uppercase; letter-spacing: 0.08em; margin-top: 2px; color: <?php echo $p['badge_color']; ?>;">
                                            <?php echo htmlspecialchars($p['badge_text']); ?>
                                        </div>
                                    </td>
                                    <td style="text-align: center;">
                                        <span class="order-badge"><?php echo $p['display_order']; ?></span>
                                    </td>
                                    <td style="text-align: right;">
                                        <div class="d-flex justify-content-end gap-2">
                                            <a href="settings.php?action=edit_promo&promo_id=<?php echo $p['id']; ?>#promo" class="action-btn">Edit</a>
                                            <form method="POST" class="d-inline" onsubmit="return confirmAction(event, 'Delete promo card?');">
                                                <?php echo csrfField(); ?>
                                                <input type="hidden" name="action" value="delete_promo">
                                                <input type="hidden" name="promo_id" value="<?php echo $p['id']; ?>">
                                                <button type="submit" class="action-btn danger">Del</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($promos)): ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; padding: 48px; color: var(--mid-gray);">No promo cards yet. Create your first one!</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>

        <!-- Flash Sale -->
        <section class="settings-section <?php echo $active_tab === 'flash' ? 'active' : ''; ?>" id="flash">
            <div class="section-header">
                <h2>Flash Sale</h2>
                <p>Configure the timed countdown sale that surfaces on the homepage.</p>
            </div>
            <form method="POST">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="save_general">
                <div class="settings-grid">
                    <div class="panel">
                        <div class="panel-header"><div class="panel-title">Flash Sale Configuration</div></div>
                        <div class="panel-body">
                            <div class="field-group">
                                <label class="field-label">Enable Flash Sale</label>
                                <select name="flash_sale_enabled" class="field-input">
                                    <option value="1" <?php echo (($settings['flash_sale_enabled'] ?? '1') == '1') ? 'selected' : ''; ?>>Active / Visible</option>
                                    <option value="0" <?php echo (($settings['flash_sale_enabled'] ?? '1') == '0') ? 'selected' : ''; ?>>Hidden / Inactive</option>
                                </select>
                            </div>
                            <div class="field-group">
                                <label class="field-label">End Date &amp; Time</label>
                                <input type="datetime-local" name="flash_sale_end_time" class="field-input" value="<?php echo htmlspecialchars(!empty($settings['flash_sale_end_time']) ? date('Y-m-d\TH:i', strtotime($settings['flash_sale_end_time'])) : ''); ?>">
                                <span class="field-sub">Specify the date and time when the Flash Sale should end and countdown ticks to 0.</span>
                            </div>
                            <div class="field-group" style="margin-bottom: 0;">
                                <label class="field-label">Curated Flash Sale Product IDs (Comma Separated)</label>
                                <input type="text" name="flash_sale_product_ids" class="field-input" style="font-family: var(--f-mono);" value="<?php echo htmlspecialchars($settings['flash_sale_product_ids'] ?? ''); ?>" placeholder="e.g. 1,4,7,12">
                                <span class="field-sub">Enter specific product IDs to display in the sale. Leave empty to automatically display all items currently having a discount (where price is less than original price).</span>
                            </div>
                        </div>
                    </div>
                    <div class="panel">
                        <div class="panel-header"><div class="panel-title">Flash Sale Helper Tips</div></div>
                        <div class="panel-body" style="font-size: 13px; color: var(--mid-gray);">
                            <div class="field-group">
                                <strong style="color: var(--ink); display: block; margin-bottom: 4px;">Product IDs Directory</strong>
                                You can find product IDs in the <a href="manage_products.php" style="font-weight: 800; color: var(--red);">Products Catalog</a> page. Simply copy the IDs and paste them separated by commas (e.g. <code>1,4,12</code>).
                            </div>
                            <div class="field-group" style="margin-bottom: 0;">
                                <strong style="color: var(--ink); display: block; margin-bottom: 4px;">Dynamic Stock Urgency</strong>
                                The homepage elements automatically calculate and render urgency triggers based on the product stock, remaining time, and sale percentage, to optimize buyer conversion.
                            </div>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn-red" style="margin-top: 24px;">Save Flash Sale Settings</button>
            </form>
        </section>

        <!-- Shipping Zones -->
        <section class="settings-section <?php echo $active_tab === 'shipping' ? 'active' : ''; ?>" id="shipping">
            <div class="section-header">
                <h2>Shipping Zones</h2>
                <p>Configure delivery zones — domestic (Ghana) and international (abroad) — with flat-rate fees used at checkout. 🇬🇭 local goods ship both ways.</p>
            </div>
            <?php if (empty($shipping_zones)): ?>
                <div class="alert-box alert-error">No shipping zones found. Run the migration (<code>run_migration-local.php</code>) first, then refresh.</div>
            <?php else: ?>
                <form method="POST">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="action" value="save_shipping_zones">
                    <div class="panel">
                        <div class="panel-header"><div class="panel-title">Zone Rates</div></div>
                        <div class="table-container" style="border: none; border-bottom: 1px solid var(--light-gray); border-radius: 0; margin: 0;">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Zone</th>
                                        <th>Type</th>
                                        <th>Countries (ISO-2, JSON)</th>
                                        <th>Flat Rate (₵)</th>
                                        <th>Free over (₵)</th>
                                        <th>ETA</th>
                                        <th>Active</th>
                                        <th>Delete</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($shipping_zones as $z): ?>
                                        <tr>
                                            <input type="hidden" name="zone_id[]" value="<?php echo (int)$z['zone_id']; ?>">
                                            <td>
                                                <input type="text" name="zone_name[]" class="field-input" style="height: 38px; min-width: 150px;" value="<?php echo htmlspecialchars($z['zone_name']); ?>">
                                            </td>
                                            <td>
                                                <select name="zone_type[]" class="field-input" style="height: 38px; padding: 0 8px;">
                                                    <option value="domestic" <?php echo $z['zone_type'] === 'domestic' ? 'selected' : ''; ?>>Domestic</option>
                                                    <option value="international" <?php echo $z['zone_type'] === 'international' ? 'selected' : ''; ?>>International</option>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="text" name="country_codes[]" class="field-input" style="height: 38px; min-width: 120px; font-family: var(--f-mono); font-size: 11px;" value="<?php echo htmlspecialchars($z['country_codes'] ?? ''); ?>" placeholder='["US","GB",...]'>
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" min="0" name="flat_rate[]" class="field-input" style="height: 38px; width: 90px;" value="<?php echo htmlspecialchars($z['flat_rate']); ?>">
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" min="0" name="free_threshold[]" class="field-input" style="height: 38px; width: 90px;" value="<?php echo $z['free_threshold'] !== null && $z['free_threshold'] !== '' ? htmlspecialchars($z['free_threshold']) : ''; ?>" placeholder="—">
                                            </td>
                                            <td>
                                                <input type="text" name="estimated_days[]" class="field-input" style="height: 38px; width: 110px;" value="<?php echo htmlspecialchars($z['estimated_days'] ?? ''); ?>">
                                            </td>
                                            <td style="text-align: center;">
                                                <input type="checkbox" name="zone_active[]" value="<?php echo (int)$z['zone_id']; ?>" class="field-check" <?php echo !empty($z['is_active']) ? 'checked' : ''; ?>>
                                            </td>
                                            <td style="text-align: center;">
                                                <input type="checkbox" name="delete_zone[]" value="<?php echo (int)$z['zone_id']; ?>" class="field-check">
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <button type="submit" class="btn-red" style="margin: 24px;">Save Zone Changes</button>
                    </div>
                </form>

                <div class="panel" style="margin-top: 32px;">
                    <div class="panel-header"><div class="panel-title">Add New Zone</div></div>
                    <div class="panel-body">
                        <form method="POST">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="action" value="save_shipping_zones">
                            <div class="field-grid">
                                <div class="field-group">
                                    <label class="field-label">Zone Name</label>
                                    <input type="text" name="new_zone_name" class="field-input" placeholder="e.g. Asia">
                                </div>
                                <div class="field-group">
                                    <label class="field-label">Type</label>
                                    <select name="new_zone_type" class="field-input">
                                        <option value="international">International</option>
                                        <option value="domestic">Domestic</option>
                                    </select>
                                </div>
                            </div>
                            <div class="field-grid">
                                <div class="field-group">
                                    <label class="field-label">Flat Rate (₵)</label>
                                    <input type="number" step="0.01" min="0" name="new_flat_rate" class="field-input" value="0">
                                </div>
                                <div class="field-group">
                                    <label class="field-label">Free over (₵) — optional</label>
                                    <input type="number" step="0.01" min="0" name="new_free_threshold" class="field-input">
                                </div>
                            </div>
                            <div class="field-grid">
                                <div class="field-group">
                                    <label class="field-label">Country Codes (ISO-2 JSON)</label>
                                    <input type="text" name="new_country_codes" class="field-input" style="font-family: var(--f-mono);" placeholder='["CN","JP","IN"]'>
                                </div>
                                <div class="field-group">
                                    <label class="field-label">Estimated Delivery</label>
                                    <input type="text" name="new_estimated_days" class="field-input" placeholder="e.g. 12–20 days">
                                </div>
                            </div>
                            <div class="field-group" style="margin-bottom: 0;">
                                <label class="field-label">Flag Emoji</label>
                                <input type="text" name="new_flag_emoji" class="field-input" placeholder="🌏">
                            </div>
                            <button type="submit" class="btn-red" style="margin-top: 16px;">Add Zone</button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </section>

        <!-- Ad Banners -->
        <section class="settings-section <?php echo $active_tab === 'adbanners' ? 'active' : ''; ?>" id="adbanners">
            <div class="section-header">
                <h2>Ad Banners</h2>
                <p>Homepage advertising slider. Add images with a link, caption and call-to-action. Slides auto-rotate between the hero and product grid.</p>
            </div>

            <div class="panel" style="margin-bottom: 32px;">
                <div class="panel-header"><div class="panel-title">Banner Slides <span style="opacity: 0.4;">(<?php echo count($ad_banners); ?>)</span></div></div>
                <div class="table-container" style="border: none; border-bottom: 1px solid var(--light-gray); border-radius: 0; margin: 0;">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Preview</th>
                                <th>Title</th>
                                <th>Link</th>
                                <th style="text-align: center;">Order</th>
                                <th style="text-align: center;">Active</th>
                                <th style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ad_banners as $a): ?>
                            <tr>
                                <td><img class="hero-thumb" src="<?php echo (strpos($a['image_path'], 'assets/') === 0) ? '../' . $a['image_path'] : '../assets/images/' . $a['image_path']; ?>" alt="ad"></td>
                                <td><div style="font-weight: 800; font-size: 14px;"><?php echo htmlspecialchars($a['title'] ?: '(untitled)'); ?></div></td>
                                <td><span style="font-family: var(--f-mono); font-size: 11px; color: var(--mid-gray);"><?php echo htmlspecialchars($a['button_link']); ?></span></td>
                                <td style="text-align: center;"><span class="order-badge"><?php echo (int)$a['display_order']; ?></span></td>
                                <td style="text-align: center;"><span class="status-badge <?php echo $a['is_active'] ? 'status-active' : 'status-suspended'; ?>"><?php echo $a['is_active'] ? 'Active' : 'Inactive'; ?></span></td>
                                <td style="text-align: right;">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="settings.php?action=edit_ad&ad_id=<?php echo (int)$a['id']; ?>#adbanners" class="action-btn">Edit</a>
                                        <form method="POST" class="d-inline" onsubmit="return confirmAction(event, 'Delete this ad banner?');">
                                            <?php echo csrfField(); ?>
                                            <input type="hidden" name="action" value="delete_ad_banner">
                                            <input type="hidden" name="ad_id" value="<?php echo (int)$a['id']; ?>">
                                            <button type="submit" class="action-btn danger">Del</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($ad_banners)): ?>
                            <tr><td colspan="6" style="text-align: center; padding: 48px; color: var(--mid-gray);">No ad banners yet. Add your first slide below.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="panel">
                <div class="panel-header"><div class="panel-title"><?php echo $edit_ad ? 'Edit Ad Banner' : 'Add New Ad Banner'; ?></div></div>
                <div class="panel-body">
                    <form method="POST" enctype="multipart/form-data">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="action" value="save_ad_banner">
                        <input type="hidden" name="ad_id" value="<?php echo $edit_ad ? (int)$edit_ad['id'] : 0; ?>">
                        <?php if ($edit_ad): ?>
                            <input type="hidden" name="existing_ad_image" value="<?php echo htmlspecialchars($edit_ad['image_path']); ?>">
                        <?php endif; ?>

                        <div class="field-grid">
                            <div class="field-group">
                                <label class="field-label">Banner Image <span style="color: var(--red);">*</span></label>
                                <?php if ($edit_ad && !empty($edit_ad['image_path'])): ?>
                                    <div style="margin-bottom: 10px; display: flex; align-items: center; gap: 12px;">
                                        <img class="hero-thumb" style="width: 140px; height: 80px;" src="<?php echo (strpos($edit_ad['image_path'], 'assets/') === 0) ? '../' . $edit_ad['image_path'] : '../assets/images/' . $edit_ad['image_path']; ?>" alt="current">
                                        <span class="field-sub" style="margin: 0;">Current image (replace by choosing a new file)</span>
                                    </div>
                                <?php endif; ?>
                                <input type="file" name="ad_image" class="file-input" accept="image/*" <?php echo !$edit_ad ? 'required' : ''; ?>>
                                <span class="field-sub">Landscape recommended (e.g. 1200×500). Shown in the homepage ad slider.</span>
                            </div>
                        </div>

                        <div class="field-grid">
                            <div class="field-group">
                                <label class="field-label">Title / Caption</label>
                                <input type="text" name="ad_title" class="field-input" placeholder="e.g. *Summer Sale* – Up to 40% Off" value="<?php echo $edit_ad ? htmlspecialchars($edit_ad['title']) : ''; ?>">
                                <span class="field-sub">Wrap a word in asterisks to highlight it in gold, e.g. <code>*Summer Sale*</code>.</span>
                            </div>
                            <div class="field-group">
                                <label class="field-label">Description</label>
                                <textarea name="ad_description" class="field-input" rows="2" placeholder="Short line shown under the title..."><?php echo $edit_ad ? htmlspecialchars($edit_ad['description']) : ''; ?></textarea>
                            </div>
                        </div>

                        <div class="field-grid">
                            <div class="field-group">
                                <label class="field-label">Button Text</label>
                                <input type="text" name="ad_button_text" class="field-input" value="<?php echo $edit_ad ? htmlspecialchars($edit_ad['button_text']) : 'Shop Now'; ?>">
                            </div>
                            <div class="field-group">
                                <label class="field-label">Button Link</label>
                                <input type="text" name="ad_button_link" class="field-input" value="<?php echo $edit_ad ? htmlspecialchars($edit_ad['button_link']) : 'shop.php'; ?>" placeholder="e.g. shop.php?category=deals">
                            </div>
                        </div>

                        <div class="field-grid">
                            <div class="field-group">
                                <label class="field-label">Display Order</label>
                                <input type="number" name="ad_display_order" class="field-input" value="<?php echo $edit_ad ? (int)$edit_ad['display_order'] : 0; ?>">
                            </div>
                            <div class="field-group" style="display: flex; align-items: center; gap: 10px; padding-top: 24px;">
                                <input type="checkbox" name="ad_is_active" id="adActive" value="1" <?php echo (!$edit_ad || $edit_ad['is_active']) ? 'checked' : ''; ?> style="width: 16px; height: 16px; accent-color: var(--red);">
                                <label class="field-label" for="adActive" style="margin: 0;">Banner Active</label>
                            </div>
                        </div>

                        <button type="submit" class="btn-red" style="margin-top: 8px;"><?php echo $edit_ad ? 'Update Ad Banner' : 'Publish Ad Banner'; ?></button>
                        <?php if ($edit_ad): ?>
                            <a href="settings.php#adbanners" class="btn-ink" style="justify-content: center; margin-left: 12px; margin-top: 8px; display: inline-flex; padding: 14px 32px; text-decoration: none;">Cancel Editing</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </section>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Persist active tab on refresh
    const hash = window.location.hash.replace('#', '');
    if (hash && document.getElementById(hash)) {
        document.querySelectorAll('.settings-tab-btn').forEach(function (btn) {
            btn.classList.toggle('active', btn.dataset.target === hash);
        });
        document.querySelectorAll('.settings-section').forEach(function (section) {
            section.classList.toggle('active', section.id === hash);
        });
    }

    // Update URL hash when tab changes
    document.querySelectorAll('.settings-tab-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.settings-tab-btn').forEach(function (b) { b.classList.remove('active'); });
            this.classList.add('active');
            document.querySelectorAll('.settings-section').forEach(function (s) { s.classList.remove('active'); });
            document.getElementById(this.dataset.target).classList.add('active');
            window.location.hash = this.dataset.target;
        });
    });
});
</script>

<?php include 'includes/avazonia_footer.php'; ?>
