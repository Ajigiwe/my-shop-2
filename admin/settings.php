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
        // Handle General / UI / Social Settings
        if ($action === 'save_general') {
            $allowed_keys = [
                'site_name', 'primary_color', 'products_per_row',
                'announcement_text', 'footer_notice',
                'social_facebook', 'social_instagram', 'social_twitter', 'social_tiktok', 'social_youtube', 'social_whatsapp',
                'flash_sale_enabled', 'flash_sale_end_time', 'flash_sale_product_ids', 'homepage_hero_style'
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
            $display_order = (int)($_POST['display_order'] ?? 0);
            $is_active = isset($_POST['is_active']) ? 1 : 0;

            if (!$title_black) $errors[] = 'Main title is required';
            
            if (empty($errors)) {
                $image = handleHeroUpload('image', $_POST['existing_image'] ?? '');
                $card_bg = $_POST['card_bg'] ?? '#FFFFFF';
                $text_color = $_POST['text_color'] ?? '#1A1A1A';
                
                if ($action === 'create_slide') {
                    $stmt = $pdo->prepare("INSERT INTO hero_slides (badge_text, title_black, title_gray, description, button_text, button_link, image_path, card_bg, text_color, display_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$badge_text, $title_black, $title_gray, $description, $button_text, $button_link, $image, $card_bg, $text_color, $display_order, $is_active]);
                    $success = 'Hero slide added successfully';
                } else {
                    $stmt = $pdo->prepare("UPDATE hero_slides SET badge_text = ?, title_black = ?, title_gray = ?, description = ?, button_text = ?, button_link = ?, image_path = ?, card_bg = ?, text_color = ?, display_order = ?, is_active = ? WHERE id = ?");
                    $stmt->execute([$badge_text, $title_black, $title_gray, $description, $button_text, $button_link, $image, $card_bg, $text_color, $display_order, $is_active, $id]);
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
                
                $stmt = $pdo->prepare("DELETE FROM hero_slides WHERE id = ?");
                $stmt->execute([$id]);
                $success = 'Slide deleted successfully';
            }
        }
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

include 'includes/header-new.php';
?>
<style>
    .settings-nav-container {
        background: transparent;
        padding: 0;
        margin-bottom: 2.5rem;
    }
    .nav-pills-premium {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 0.5rem;
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .nav-pills-premium .nav-link {
        background: transparent;
        color: #64748B; /* Neutral Slate */
        font-weight: 800;
        font-size: 0.85rem;
        padding: 0.75rem 1.5rem;
        border-radius: 12px;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        border: none;
        white-space: nowrap;
        opacity: 0.7;
    }
    .nav-pills-premium .nav-link:hover {
        opacity: 1;
        color: #1E293B;
    }
    .nav-pills-premium .nav-link i {
        font-size: 1rem;
    }
    .nav-pills-premium .nav-link.active {
        background: #1A1A1A !important; /* Solid Black for high contrast */
        color: white !important;
        opacity: 1;
        box-shadow: none;
    }
    @media (max-width: 991px) {
        .nav-pills-premium {
            overflow-x: auto;
            justify-content: flex-start;
            padding: 0.5rem;
            no-scrollbar: true;
        }
        .nav-pills-premium::-webkit-scrollbar { display: none; }
    }
</style>

<div class="row g-4">
    <!-- Tab Navigation -->
    <div class="col-lg-12">
        <div class="settings-nav-container">
            <ul class="nav-pills-premium" id="settingsTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="branding-tab" data-bs-toggle="pill" data-bs-target="#branding" type="button" role="tab">
                        <i class="fas fa-paint-brush"></i> Branding & UI
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="social-tab" data-bs-toggle="pill" data-bs-target="#social" type="button" role="tab">
                        <i class="fas fa-share-alt"></i> Social Links
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="hero-tab" data-bs-toggle="pill" data-bs-target="#hero" type="button" role="tab">
                        <i class="fas fa-images"></i> Hero Slider
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="promo-tab" data-bs-toggle="pill" data-bs-target="#promo" type="button" role="tab">
                        <i class="fas fa-grid-2"></i> Promo Grid
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="flash-tab" data-bs-toggle="pill" data-bs-target="#flash" type="button" role="tab">
                        <i class="fas fa-bolt"></i> Flash Sale
                    </button>
                </li>
            </ul>
        </div>
    </div>

    <!-- Tab Content -->
    <div class="col-lg-12">
        <?php if ($success): ?>
            <div class="alert alert-success border-0 rounded-4 mb-4 small fw-bold animate-up">
                <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
            </div>
        <?php endif; ?>
        <?php if ($errors): ?>
            <div class="alert alert-danger border-0 rounded-4 mb-4 small fw-bold animate-up">
                <ul class="mb-0">
                    <?php foreach ($errors as $e): ?><li><?php echo $e; ?></li><?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="tab-content" id="settingsTabsContent">
            
            <!-- Branding & UI Tab -->
            <div class="tab-pane fade show active" id="branding" role="tabpanel">
                <form method="POST">
                    <input type="hidden" name="action" value="save_general">
                    <div class="row g-4">
                        <div class="col-lg-6">
                            <div class="admin-card animate-up">
                                <div class="admin-card-header"><h5 class="admin-card-title mb-0">Core Identity</h5></div>
                                <div class="card-body p-4">
                                    <div class="mb-3">
                                        <label class="stat-label">Marketplace Name</label>
                                        <input type="text" name="site_name" class="form-control rounded-3" value="<?php echo htmlspecialchars($settings['site_name'] ?? 'ASO Online Market'); ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="stat-label">Primary Brand Color</label>
                                        <div class="d-flex gap-2">
                                            <input type="color" name="primary_color" value="<?php echo htmlspecialchars($settings['primary_color'] ?? '#1A1A1A'); ?>" class="form-control form-control-color border-0 p-0 rounded-3 shadow-sm" style="width: 48px; height: 48px;">
                                            <input type="text" class="form-control rounded-3 flex-grow-1 font-monospace" value="<?php echo htmlspecialchars($settings['primary_color'] ?? '#1A1A1A'); ?>" readonly>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="stat-label">Products Per Row (Desktop)</label>
                                        <select name="products_per_row" class="form-select rounded-3 fw-bold">
                                            <?php for($i=2; $i<=6; $i++): ?>
                                                <option value="<?php echo $i; ?>" <?php echo (($settings['products_per_row'] ?? '6') == $i) ? 'selected' : ''; ?>><?php echo $i; ?> Products</option>
                                            <?php endfor; ?>
                                        </select>
                                        <p class="text-[10px] text-muted fw-bold uppercase tracking-widest mt-2">Adjusts the grid density on the homepage and shop.</p>
                                    </div>
                                    <div class="mb-3">
                                        <label class="stat-label">Homepage Hero Layout Style</label>
                                        <select name="homepage_hero_style" class="form-select rounded-3 fw-bold">
                                            <option value="carousel" <?php echo (($settings['homepage_hero_style'] ?? 'carousel') === 'carousel') ? 'selected' : ''; ?>>Interactive Carousel (Categories Showcase)</option>
                                            <option value="split" <?php echo (($settings['homepage_hero_style'] ?? 'split') === 'split') ? 'selected' : ''; ?>>Minimal Split Screen (Trust Signals & CTA)</option>
                                            <option value="gradient" <?php echo (($settings['homepage_hero_style'] ?? 'gradient') === 'gradient') ? 'selected' : ''; ?>>Animated Brand Gradient (High Energy Campaign)</option>
                                            <option value="circular" <?php echo (($settings['homepage_hero_style'] ?? 'circular') === 'circular') ? 'selected' : ''; ?>>Facing Deck: Circular Rotate</option>
                                            <option value="sidebyside" <?php echo (($settings['homepage_hero_style'] ?? 'sidebyside') === 'sidebyside') ? 'selected' : ''; ?>>Facing Deck: Side by Side Flip</option>
                                            <option value="stacked" <?php echo (($settings['homepage_hero_style'] ?? 'stacked') === 'stacked') ? 'selected' : ''; ?>>Facing Deck: Stacked Slide</option>
                                            <option value="orbit" <?php echo (($settings['homepage_hero_style'] ?? 'orbit') === 'orbit') ? 'selected' : ''; ?>>Facing Deck: Orbit Motion</option>
                                        </select>
                                        <p class="text-[10px] text-muted fw-bold uppercase tracking-widest mt-2">Pick which gorgeous responsive layout structure to render for your homepage top header.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="admin-card animate-up" style="animation-delay: 0.1s;">
                                <div class="admin-card-header"><h5 class="admin-card-title mb-0">Interface Content</h5></div>
                                <div class="card-body p-4">
                                    <div class="mb-3">
                                        <label class="stat-label">Announcement Bar Text</label>
                                        <input type="text" name="announcement_text" class="form-control rounded-3" value="<?php echo htmlspecialchars($settings['announcement_text'] ?? ''); ?>" placeholder="e.g. Free delivery on orders over GH₵500!">
                                    </div>
                                    <div class="mb-3">
                                        <label class="stat-label">Footer Copyright Notice</label>
                                        <input type="text" name="footer_notice" class="form-control rounded-3" value="<?php echo htmlspecialchars($settings['footer_notice'] ?? ''); ?>" placeholder="e.g. © 2026 ASO Online Market. All rights reserved.">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn-premium py-3 px-5">Save Visual Changes</button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Social Links Tab -->
            <div class="tab-pane fade" id="social" role="tabpanel">
                <form method="POST">
                    <input type="hidden" name="action" value="save_general">
                    <div class="admin-card animate-up">
                        <div class="admin-card-header"><h5 class="admin-card-title mb-0">Social Connectivity</h5></div>
                        <div class="card-body p-4">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="stat-label">Facebook Profile</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-0"><i class="fab fa-facebook-f text-[#1A1A1A]"></i></span>
                                        <input type="url" name="social_facebook" class="form-control rounded-end-3" value="<?php echo htmlspecialchars($settings['social_facebook'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="stat-label">Instagram Handle</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-0"><i class="fab fa-instagram text-danger"></i></span>
                                        <input type="url" name="social_instagram" class="form-control rounded-end-3" value="<?php echo htmlspecialchars($settings['social_instagram'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="stat-label">Twitter / X</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-0"><i class="fab fa-twitter text-info"></i></span>
                                        <input type="url" name="social_twitter" class="form-control rounded-end-3" value="<?php echo htmlspecialchars($settings['social_twitter'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="stat-label">TikTok Profile</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-0"><i class="fab fa-tiktok text-dark"></i></span>
                                        <input type="url" name="social_tiktok" class="form-control rounded-end-3" value="<?php echo htmlspecialchars($settings['social_tiktok'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="stat-label">YouTube Channel</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-0"><i class="fab fa-youtube text-danger"></i></span>
                                        <input type="url" name="social_youtube" class="form-control rounded-end-3" value="<?php echo htmlspecialchars($settings['social_youtube'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="stat-label">WhatsApp (Number or Link)</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-0"><i class="fab fa-whatsapp text-success"></i></span>
                                        <input type="text" name="social_whatsapp" class="form-control rounded-end-3" placeholder="e.g. +233240987670 or https://wa.me/..." value="<?php echo htmlspecialchars($settings['social_whatsapp'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn-premium py-3 px-5 mt-4">Save Social Links</button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Hero Slider Tab -->
            <div class="tab-pane fade <?php echo ($action === 'edit_slide' || $edit_slide) ? 'show active' : ''; ?>" id="hero" role="tabpanel">
                <div class="row g-4">
                    <div class="col-lg-4">
                        <div class="admin-card animate-up">
                            <div class="admin-card-header"><h5 class="admin-card-title mb-0"><?php echo $edit_slide ? 'Edit Slide' : 'New Slide'; ?></h5></div>
                            <div class="card-body p-4">
                                <form method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="action" value="<?php echo $edit_slide ? 'update_slide' : 'create_slide'; ?>">
                                    <?php if ($edit_slide): ?>
                                        <input type="hidden" name="slide_id" value="<?php echo $edit_slide['id']; ?>">
                                        <input type="hidden" name="existing_image" value="<?php echo $edit_slide['image_path']; ?>">
                                    <?php endif; ?>

                                    <div class="mb-3">
                                        <label class="stat-label">Main Title</label>
                                        <input type="text" name="title_black" class="form-control rounded-3" value="<?php echo $edit_slide['title_black'] ?? ''; ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="stat-label">Subtitle (Gray Text)</label>
                                        <input type="text" name="title_gray" class="form-control rounded-3" value="<?php echo $edit_slide['title_gray'] ?? ''; ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="stat-label">Badge Text</label>
                                        <input type="text" name="badge_text" class="form-control rounded-3" value="<?php echo $edit_slide['badge_text'] ?? ''; ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="stat-label">Slide Image</label>
                                        <input type="file" name="image" class="form-control rounded-3" <?php echo !$edit_slide ? 'required' : ''; ?>>
                                    </div>
                                    <div class="row g-2 mb-3">
                                        <div class="col-6">
                                            <label class="stat-label">Card Background</label>
                                            <input type="color" name="card_bg" value="<?php echo $edit_slide['card_bg'] ?? '#FFFFFF'; ?>" class="form-control form-control-color w-100 rounded-3">
                                        </div>
                                        <div class="col-6">
                                            <label class="stat-label">Primary Text Color</label>
                                            <input type="color" name="text_color" value="<?php echo $edit_slide['text_color'] ?? '#1A1A1A'; ?>" class="form-control form-control-color w-100 rounded-3">
                                        </div>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-6 mb-3">
                                            <label class="stat-label">Btn Text</label>
                                            <input type="text" name="button_text" class="form-control rounded-3" value="<?php echo $edit_slide['button_text'] ?? 'Shop Now'; ?>">
                                        </div>
                                        <div class="col-6 mb-3">
                                            <label class="stat-label">Btn Link</label>
                                            <input type="text" name="button_link" class="form-control rounded-3" value="<?php echo $edit_slide['button_link'] ?? 'shop.php'; ?>">
                                        </div>
                                    </div>
                                    <div class="row g-2 align-items-center">
                                        <div class="col-6">
                                            <label class="stat-label">Order</label>
                                            <input type="number" name="display_order" class="form-control rounded-3" value="<?php echo $edit_slide['display_order'] ?? 0; ?>">
                                        </div>
                                        <div class="col-6">
                                            <div class="form-check form-switch mt-4">
                                                <input class="form-check-input" type="checkbox" name="is_active" <?php echo (!isset($edit_slide['is_active']) || $edit_slide['is_active']) ? 'checked' : ''; ?>>
                                                <label class="form-check-label stat-label">Active</label>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn-premium w-100 py-3 mt-4"><?php echo $edit_slide ? 'Update Slide' : 'Create Slide'; ?></button>
                                    <?php if ($edit_slide): ?>
                                        <a href="settings.php#hero" class="btn-premium-outline w-100 mt-2 text-decoration-none text-center d-block py-2">Cancel</a>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-8">
                        <div class="admin-card animate-up" style="animation-delay: 0.1s;">
                            <div class="admin-card-header"><h5 class="admin-card-title mb-0">Active Slides</h5></div>
                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead>
                                        <tr>
                                            <th>Preview</th>
                                            <th>Title</th>
                                            <th class="text-center">Order</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($slides as $s): ?>
                                        <tr>
                                            <td>
                                                <img src="<?php echo (strpos($s['image_path'], 'assets/') === 0) ? '../'.$s['image_path'] : '../assets/images/'.$s['image_path']; ?>" 
                                                     class="rounded-3 border" style="width: 80px; height: 50px; object-fit: cover;">
                                            </td>
                                            <td>
                                                <div class="fw-black text-[14px]"><?php echo htmlspecialchars($s['title_black']); ?></div>
                                                <div class="small text-muted fw-bold uppercase tracking-widest text-[9px]"><?php echo htmlspecialchars($s['badge_text']); ?></div>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-light text-dark rounded-pill px-3"><?php echo $s['display_order']; ?></span>
                                            </td>
                                            <td class="text-end">
                                                <div class="d-flex justify-content-end gap-2">
                                                    <a href="settings.php?action=edit_slide&slide_id=<?php echo $s['id']; ?>#hero" class="btn-premium-outline px-3 py-1 small text-decoration-none"><i class="fas fa-edit"></i></a>
                                                    <form method="POST">
                                                        <input type="hidden" name="action" value="delete_slide">
                                                        <input type="hidden" name="slide_id" value="<?php echo $s['id']; ?>">
                                                        <button type="submit" class="btn-premium-outline px-3 py-1 text-danger border-danger/20" onclick="return confirmAction(event, 'Delete slide?')"><i class="fas fa-trash"></i></button>
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
                </div>
            </div>

            <!-- Promo Grid Tab -->
            <div class="tab-pane fade" id="promo" role="tabpanel">
                <div class="row g-4">
                    <div class="col-lg-5">
                        <div class="admin-card animate-up">
                            <div class="admin-card-header">
                                <h5 class="admin-card-title mb-0">
                                    <?php echo (isset($_GET['action']) && $_GET['action'] === 'edit_promo') ? 'Edit Promo Card' : 'Create New Promo'; ?>
                                </h5>
                            </div>
                            <div class="card-body p-4">
                                <?php 
                                $edit_promo = null;
                                if (isset($_GET['action']) && $_GET['action'] === 'edit_promo' && isset($_GET['promo_id'])) {
                                    $stmt = $pdo->prepare("SELECT * FROM promo_cards WHERE id = ?");
                                    $stmt->execute([$_GET['promo_id']]);
                                    $edit_promo = $stmt->fetch();
                                }
                                ?>
                                <form method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="action" value="<?php echo $edit_promo ? 'update_promo' : 'create_promo'; ?>">
                                    <?php if ($edit_promo): ?>
                                        <input type="hidden" name="promo_id" value="<?php echo $edit_promo['id']; ?>">
                                        <input type="hidden" name="existing_image" value="<?php echo $edit_promo['image_path']; ?>">
                                    <?php endif; ?>

                                    <?php 
                                    $all_products = $pdo->query("SELECT product_id, name FROM products ORDER BY name ASC")->fetchAll();
                                    ?>
                                    <div class="mb-3">
                                        <label class="stat-label">Linked Product (Optional)</label>
                                        <select name="product_id" class="form-select rounded-3">
                                            <option value="">-- No Linked Product --</option>
                                            <?php foreach ($all_products as $ap): ?>
                                                <option value="<?php echo $ap['product_id']; ?>" <?php echo (isset($edit_promo['product_id']) && $edit_promo['product_id'] == $ap['product_id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($ap['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="small text-muted mt-1">If selected, title, price, and image will auto-fill from the product.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="stat-label">Main Title (Override)</label>
                                        <input type="text" name="title" class="form-control rounded-3" value="<?php echo $edit_promo['title'] ?? ''; ?>" placeholder="Leave blank to use product name">
                                    </div>
                                    <div class="row g-2 mb-3">
                                        <div class="col-6">
                                            <label class="stat-label">Subtitle (e.g. From)</label>
                                            <input type="text" name="subtitle" class="form-control rounded-3" value="<?php echo $edit_promo['subtitle'] ?? 'From'; ?>">
                                        </div>
                                        <div class="col-6">
                                            <label class="stat-label">Price/Value (Override)</label>
                                            <input type="text" name="price_text" class="form-control rounded-3" value="<?php echo $edit_promo['price_text'] ?? ''; ?>" placeholder="Leave blank to use product price">
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="stat-label">Badge Text (e.g. 10% OFF)</label>
                                        <input type="text" name="badge_text" class="form-control rounded-3" value="<?php echo $edit_promo['badge_text'] ?? ''; ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="stat-label">Promo Image (Override Product Image)</label>
                                        <input type="file" name="image" class="form-control rounded-3">
                                        <div class="small text-muted mt-1">Leave blank to use the linked product's image.</div>
                                    </div>
                                    <div class="row g-3 mb-3">
                                        <div class="col-4">
                                            <label class="stat-label">Card BG</label>
                                            <input type="color" name="card_bg" value="<?php echo $edit_promo['card_bg'] ?? '#F2F4F7'; ?>" class="form-control form-control-color w-100 rounded-3">
                                        </div>
                                        <div class="col-4">
                                            <label class="stat-label">Text Color</label>
                                            <input type="color" name="text_color" value="<?php echo $edit_promo['text_color'] ?? '#1A1A1A'; ?>" class="form-control form-control-color w-100 rounded-3">
                                        </div>
                                        <div class="col-4">
                                            <label class="stat-label">Badge Color</label>
                                            <input type="color" name="badge_color" value="<?php echo $edit_promo['badge_color'] ?? '#666666'; ?>" class="form-control form-control-color w-100 rounded-3">
                                        </div>
                                    </div>
                                    <div class="row g-2 mb-3">
                                        <div class="col-6">
                                            <label class="stat-label">Btn Text</label>
                                            <input type="text" name="button_text" class="form-control rounded-3" value="<?php echo $edit_promo['button_text'] ?? 'Buy Now'; ?>">
                                        </div>
                                        <div class="col-6">
                                            <label class="stat-label">Btn Link</label>
                                            <input type="text" name="button_link" class="form-control rounded-3" value="<?php echo $edit_promo['button_link'] ?? 'shop.php'; ?>">
                                        </div>
                                    </div>
                                    <div class="row g-2 align-items-center">
                                        <div class="col-4">
                                            <label class="stat-label">Order</label>
                                            <input type="number" name="display_order" class="form-control rounded-3" value="<?php echo $edit_promo['display_order'] ?? 0; ?>">
                                        </div>
                                        <div class="col-4">
                                            <div class="form-check form-switch mt-4">
                                                <input class="form-check-input" type="checkbox" name="is_button" <?php echo (!isset($edit_promo['is_button']) || $edit_promo['is_button']) ? 'checked' : ''; ?>>
                                                <label class="form-check-label stat-label">As Button</label>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="form-check form-switch mt-4">
                                                <input class="form-check-input" type="checkbox" name="is_active" <?php echo (!isset($edit_promo['is_active']) || $edit_promo['is_active']) ? 'checked' : ''; ?>>
                                                <label class="form-check-label stat-label">Active</label>
                                            </div>
                                        </div>
                                    </div>
                                    <hr class="my-4">
                                    <button type="submit" class="btn-premium w-100 py-3 rounded-pill fw-bold">
                                        <?php echo $edit_promo ? 'Update Promo Card' : 'Add to Grid'; ?>
                                    </button>
                                    <?php if ($edit_promo): ?>
                                        <a href="settings.php#promo" class="btn btn-link w-100 mt-2 text-decoration-none small text-muted">Cancel Editing</a>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-7">
                        <div class="admin-card animate-up">
                            <div class="admin-card-header"><h5 class="admin-card-title mb-0">Current Promo Grid</h5></div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th class="ps-4">Preview</th>
                                                <th>Content</th>
                                                <th class="text-center">Order</th>
                                                <th class="text-end pe-4">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $promos = $pdo->query("SELECT * FROM promo_cards ORDER BY display_order ASC")->fetchAll();
                                            foreach ($promos as $p):
                                            ?>
                                            <tr>
                                                <td class="ps-4">
                                                    <div class="rounded-3 p-2 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; background-color: <?php echo $p['card_bg']; ?>;">
                                                        <img src="<?php echo (strpos($p['image_path'], 'assets/') === 0) ? '../'.$p['image_path'] : '../assets/images/'.$p['image_path']; ?>" class="w-100 h-100 object-contain">
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="fw-black text-[13px]"><?php echo htmlspecialchars($p['title']); ?></div>
                                                    <div class="small text-muted fw-bold uppercase tracking-widest text-[9px]" style="color: <?php echo $p['badge_color']; ?> !important;">
                                                        <?php echo htmlspecialchars($p['badge_text']); ?>
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-light text-dark rounded-pill px-3"><?php echo $p['display_order']; ?></span>
                                                </td>
                                                <td class="text-end pe-4">
                                                    <div class="d-flex justify-content-end gap-2">
                                                        <a href="settings.php?action=edit_promo&promo_id=<?php echo $p['id']; ?>#promo" class="btn-premium-outline px-3 py-1 small text-decoration-none"><i class="fas fa-edit"></i></a>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="action" value="delete_promo">
                                                            <input type="hidden" name="promo_id" value="<?php echo $p['id']; ?>">
                                                            <button type="submit" class="btn-premium-outline px-3 py-1 text-danger border-danger/20" onclick="return confirmAction(event, 'Delete promo card?')"><i class="fas fa-trash"></i></button>
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
                    </div>
                </div>
            </div>

            <!-- Flash Sale Tab -->
            <div class="tab-pane fade" id="flash" role="tabpanel">
                <form method="POST">
                    <input type="hidden" name="action" value="save_general">
                    <div class="row g-4">
                        <div class="col-lg-6">
                            <div class="admin-card animate-up">
                                <div class="admin-card-header">
                                    <h5 class="admin-card-title mb-0">Flash Sale Configuration</h5>
                                </div>
                                <div class="card-body p-4">
                                    <div class="mb-3">
                                        <label class="stat-label">Enable Flash Sale</label>
                                        <select name="flash_sale_enabled" class="form-select rounded-3 fw-bold">
                                            <option value="1" <?php echo (($settings['flash_sale_enabled'] ?? '1') == '1') ? 'selected' : ''; ?>>Active / Visible</option>
                                            <option value="0" <?php echo (($settings['flash_sale_enabled'] ?? '1') == '0') ? 'selected' : ''; ?>>Hidden / Inactive</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="stat-label">End Date & Time</label>
                                        <input type="datetime-local" name="flash_sale_end_time" class="form-control rounded-3" value="<?php echo htmlspecialchars(!empty($settings['flash_sale_end_time']) ? date('Y-m-d\TH:i', strtotime($settings['flash_sale_end_time'])) : ''); ?>">
                                        <p class="text-[10px] text-muted fw-bold uppercase tracking-widest mt-2">Specify the date and time when the Flash Sale should end and countdown ticks to 0.</p>
                                    </div>
                                    <div class="mb-3">
                                        <label class="stat-label">Curated Flash Sale Product IDs (Comma Separated)</label>
                                        <input type="text" name="flash_sale_product_ids" class="form-control rounded-3 font-monospace" value="<?php echo htmlspecialchars($settings['flash_sale_product_ids'] ?? ''); ?>" placeholder="e.g. 1,4,7,12">
                                        <p class="text-[10px] text-muted fw-bold uppercase tracking-widest mt-2">Enter specific product IDs to display in the sale. Leave empty to automatically display all items currently having a discount (where price is less than original price).</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="admin-card animate-up" style="animation-delay: 0.1s;">
                                <div class="admin-card-header">
                                    <h5 class="admin-card-title mb-0">Flash Sale Helper Tips</h5>
                                </div>
                                <div class="card-body p-4 text-muted small">
                                    <div class="mb-3">
                                        <strong class="text-dark d-block mb-1">Product IDs Directory</strong>
                                        You can find product IDs in the <a href="manage_products.php" class="fw-black text-primary text-decoration-none">Products Catalog</a> page. Simply copy the IDs and paste them separated by commas (e.g. <code>1,4,12</code>).
                                    </div>
                                    <div class="mb-3">
                                        <strong class="text-dark d-block mb-1">Dynamic Stock Urgency</strong>
                                        The homepage elements automatically calculate and render urgency triggers based on the product stock, remaining time, and sale percentage, to optimize buyer conversion.
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn-premium py-3 px-5">Save Flash Sale Settings</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Persist active tab on refresh
    const hash = window.location.hash;
    if (hash) {
        const tabBtn = document.querySelector(`button[data-bs-target="${hash}"]`);
        if (tabBtn) {
            // Remove active from others
            document.querySelectorAll('.nav-link').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-pane').forEach(pane => pane.classList.remove('show', 'active'));
            
            tabBtn.classList.add('active');
            document.querySelector(hash).classList.add('show', 'active');
        }
    }

    // Update URL hash when tab changes
    const tabButtons = document.querySelectorAll('button[data-bs-toggle="pill"]');
    tabButtons.forEach(btn => {
        btn.addEventListener('shown.bs.tab', function (event) {
            window.location.hash = event.target.getAttribute('data-bs-target');
        });
    });
});
</script>

<?php include 'includes/footer-new.php'; ?>
