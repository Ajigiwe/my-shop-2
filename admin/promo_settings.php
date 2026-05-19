<?php
require_once '../includes/db.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $promo_enabled = isset($_POST['promo_popup_enabled']) ? '1' : '0';
    $promo_title = $_POST['promo_popup_title'] ?? '';
    $promo_content = $_POST['promo_popup_content'] ?? '';
    $promo_btn_text = $_POST['promo_popup_btn_text'] ?? '';
    $promo_btn_link = $_POST['promo_popup_btn_link'] ?? '';
    $promo_frequency = $_POST['promo_popup_frequency'] ?? 'session';
    
    // Handle image upload
    $promo_image = $_POST['current_image'] ?? '';
    if (isset($_FILES['promo_popup_image']) && $_FILES['promo_popup_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../assets/images/promo/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        $file_name = time() . '_' . basename($_FILES['promo_popup_image']['name']);
        $target_file = $upload_dir . $file_name;
        
        $image_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($image_type, $allowed_types)) {
            if (move_uploaded_file($_FILES['promo_popup_image']['tmp_name'], $target_file)) {
                $promo_image = 'promo/' . $file_name;
            } else {
                $error_msg = "Failed to upload image.";
            }
        } else {
            $error_msg = "Invalid image format.";
        }
    }

    if (empty($error_msg)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
            $stmt->execute(['promo_popup_enabled', $promo_enabled]);
            $stmt->execute(['promo_popup_title', $promo_title]);
            $stmt->execute(['promo_popup_content', $promo_content]);
            $stmt->execute(['promo_popup_btn_text', $promo_btn_text]);
            $stmt->execute(['promo_popup_btn_link', $promo_btn_link]);
            $stmt->execute(['promo_popup_image', $promo_image]);
            $stmt->execute(['promo_popup_frequency', $promo_frequency]);
            $success_msg = 'Promo settings updated successfully!';
        } catch (PDOException $e) {
            $error_msg = 'Update failed: ' . $e->getMessage();
        }
    }
}

// Fetch current settings
$settings = [];
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {}

$page_title = 'Promo Popup Settings';
include 'includes/header-new.php';
?>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="admin-card animate-up">
            <div class="admin-card-header d-flex justify-content-between align-items-center">
                <h5 class="admin-card-title mb-0">Promo Popup Configuration</h5>
                <div class="form-check form-switch fs-5">
                    <input class="form-check-input" type="checkbox" role="switch" id="headerEnableToggle" <?php echo (isset($settings['promo_popup_enabled']) && $settings['promo_popup_enabled'] == '1') ? 'checked' : ''; ?> onchange="document.getElementById('realEnableToggle').checked = this.checked;">
                    <label class="form-check-label small fw-bold text-muted" for="headerEnableToggle">Enable Popup</label>
                </div>
            </div>
            <div class="card-body p-4">
                <?php if ($success_msg): ?>
                    <div class="alert alert-success border-0 rounded-4 mb-4 small fw-bold">
                        <i class="fas fa-check-circle me-2"></i><?php echo $success_msg; ?>
                    </div>
                <?php endif; ?>
                <?php if ($error_msg): ?>
                    <div class="alert alert-danger border-0 rounded-4 mb-4 small fw-bold">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error_msg; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" class="space-y-4">
                    <!-- Hidden sync for toggle -->
                    <input type="checkbox" name="promo_popup_enabled" id="realEnableToggle" class="d-none" <?php echo (isset($settings['promo_popup_enabled']) && $settings['promo_popup_enabled'] == '1') ? 'checked' : ''; ?>>
                    
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="stat-label">Popup Title</label>
                            <input type="text" name="promo_popup_title" value="<?php echo htmlspecialchars($settings['promo_popup_title'] ?? 'Welcome to Our Store!'); ?>" class="form-control rounded-3 py-2" placeholder="e.g., Summer Sale is Here!">
                        </div>
                        <div class="col-md-6">
                            <label class="stat-label">Display Frequency</label>
                            <select name="promo_popup_frequency" class="form-select rounded-3 py-2 fw-bold text-[#1A1A1A]">
                                <option value="always" <?php echo (isset($settings['promo_popup_frequency']) && $settings['promo_popup_frequency'] == 'always') ? 'selected' : ''; ?>>Always (Every Visit)</option>
                                <option value="session" <?php echo (!isset($settings['promo_popup_frequency']) || $settings['promo_popup_frequency'] == 'session') ? 'selected' : ''; ?>>Once Per Session (Browser Open)</option>
                                <option value="daily" <?php echo (isset($settings['promo_popup_frequency']) && $settings['promo_popup_frequency'] == 'daily') ? 'selected' : ''; ?>>Once Per Day</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="stat-label">Popup Message Content</label>
                        <textarea name="promo_popup_content" rows="3" class="form-control rounded-3 py-2" placeholder="e.g., Get 20% off all summer items this weekend."><?php echo htmlspecialchars($settings['promo_popup_content'] ?? 'Discover our amazing collection.'); ?></textarea>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="stat-label">Button Text</label>
                            <input type="text" name="promo_popup_btn_text" value="<?php echo htmlspecialchars($settings['promo_popup_btn_text'] ?? 'Shop Now'); ?>" class="form-control rounded-3 py-2" placeholder="e.g., Shop Collection">
                        </div>
                        <div class="col-md-6">
                            <label class="stat-label">Button Link</label>
                            <input type="text" name="promo_popup_btn_link" value="<?php echo htmlspecialchars($settings['promo_popup_btn_link'] ?? 'shop.php'); ?>" class="form-control rounded-3 py-2" placeholder="e.g., shop.php?category=sale">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="stat-label">Featured Image</label>
                        <div class="d-flex align-items-center gap-3">
                            <?php if (!empty($settings['promo_popup_image'])): ?>
                                <div class="bg-light p-2 rounded-3 border" style="width: 100px; height: 100px;">
                                    <img src="../assets/images/<?php echo htmlspecialchars($settings['promo_popup_image']); ?>" class="w-100 h-100 object-fit-contain rounded-2" alt="Current Promo Image">
                                </div>
                            <?php else: ?>
                                <div class="bg-light p-2 rounded-3 border d-flex justify-content-center align-items-center text-muted" style="width: 100px; height: 100px;">
                                    <i class="fas fa-image fs-1 opacity-25"></i>
                                </div>
                            <?php endif; ?>
                            <div class="flex-grow-1">
                                <input type="file" name="promo_popup_image" class="form-control rounded-3 py-2" accept="image/*">
                                <p class="text-[10px] text-muted fw-bold uppercase tracking-widest mt-2 mb-0">Recommended: Square or portrait image (e.g., 600x600px). Leave empty to keep current image.</p>
                                <input type="hidden" name="current_image" value="<?php echo htmlspecialchars($settings['promo_popup_image'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 pt-3 border-top">
                        <button type="submit" class="btn-premium px-5 py-3 float-end">
                            <i class="fas fa-save me-2"></i>Save Configuration
                        </button>
                        <div class="clearfix"></div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="admin-card animate-up" style="animation-delay: 0.1s;">
            <div class="admin-card-header">
                <h5 class="admin-card-title mb-0">Quick Tips</h5>
            </div>
            <div class="card-body p-4 text-muted small">
                <div class="mb-3">
                    <strong class="text-dark d-block mb-1">Dismissal Logic</strong>
                    The popup will automatically include a <span class="badge bg-light text-dark">Don't show again</span> button. If a user clicks this, the popup will be permanently hidden for them on their current browser, overriding the frequency setting.
                </div>
                <div class="mb-3">
                    <strong class="text-dark d-block mb-1">Frequency Setting</strong>
                    If the user just clicks the standard "X" to close, the popup will reappear based on your selected frequency.
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer-new.php'; ?>
