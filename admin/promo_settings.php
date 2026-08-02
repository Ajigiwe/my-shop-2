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
include 'includes/avazonia_header.php';
?>

<?php if ($success_msg): ?>
    <div class="alert-box alert-success"><?php echo htmlspecialchars($success_msg); ?></div>
<?php endif; ?>
<?php if ($error_msg): ?>
    <div class="alert-box alert-error"><?php echo htmlspecialchars($error_msg); ?></div>
<?php endif; ?>

<div class="settings-grid">
    <div class="panel">
        <div class="panel-header">
            <div class="panel-title">Promo Popup Configuration</div>
            <label class="check-row" style="padding-top: 0;">
                <input type="checkbox" class="field-check" id="headerEnableToggle" <?php echo (isset($settings['promo_popup_enabled']) && $settings['promo_popup_enabled'] == '1') ? 'checked' : ''; ?> onchange="document.getElementById('realEnableToggle').checked = this.checked;">
                <span class="field-label" style="margin: 0;">Enable Popup</span>
            </label>
        </div>
        <div class="panel-body">
            <form method="POST" enctype="multipart/form-data">
                <input type="checkbox" name="promo_popup_enabled" id="realEnableToggle" class="d-none" <?php echo (isset($settings['promo_popup_enabled']) && $settings['promo_popup_enabled'] == '1') ? 'checked' : ''; ?>>

                <div class="field-grid">
                    <div class="field-group">
                        <label class="field-label">Popup Title</label>
                        <input type="text" name="promo_popup_title" value="<?php echo htmlspecialchars($settings['promo_popup_title'] ?? 'Welcome to Our Store!'); ?>" class="field-input" placeholder="e.g., Summer Sale is Here!">
                    </div>
                    <div class="field-group">
                        <label class="field-label">Display Frequency</label>
                        <select name="promo_popup_frequency" class="field-input">
                            <option value="always" <?php echo (isset($settings['promo_popup_frequency']) && $settings['promo_popup_frequency'] == 'always') ? 'selected' : ''; ?>>Always (Every Visit)</option>
                            <option value="session" <?php echo (!isset($settings['promo_popup_frequency']) || $settings['promo_popup_frequency'] == 'session') ? 'selected' : ''; ?>>Once Per Session (Browser Open)</option>
                            <option value="daily" <?php echo (isset($settings['promo_popup_frequency']) && $settings['promo_popup_frequency'] == 'daily') ? 'selected' : ''; ?>>Once Per Day</option>
                        </select>
                    </div>
                </div>

                <div class="field-group">
                    <label class="field-label">Popup Message Content</label>
                    <textarea name="promo_popup_content" rows="3" class="field-input" placeholder="e.g., Get 20% off all summer items this weekend."><?php echo htmlspecialchars($settings['promo_popup_content'] ?? 'Discover our amazing collection.'); ?></textarea>
                </div>

                <div class="field-grid">
                    <div class="field-group">
                        <label class="field-label">Button Text</label>
                        <input type="text" name="promo_popup_btn_text" value="<?php echo htmlspecialchars($settings['promo_popup_btn_text'] ?? 'Shop Now'); ?>" class="field-input" placeholder="e.g., Shop Collection">
                    </div>
                    <div class="field-group">
                        <label class="field-label">Button Link</label>
                        <input type="text" name="promo_popup_btn_link" value="<?php echo htmlspecialchars($settings['promo_popup_btn_link'] ?? 'shop.php'); ?>" class="field-input" placeholder="e.g., shop.php?category=sale">
                    </div>
                </div>

                <div class="field-group" style="margin-bottom: 0;">
                    <label class="field-label">Featured Image</label>
                    <div class="d-flex align-items-center gap-3">
                        <?php if (!empty($settings['promo_popup_image'])): ?>
                            <div style="width: 100px; height: 100px; border: 1px solid var(--light-gray); display: flex; align-items: center; justify-content: center; background: var(--off);">
                                <img src="../assets/images/<?php echo htmlspecialchars($settings['promo_popup_image']); ?>" style="max-width: 100%; max-height: 100%; object-fit: contain;" alt="Current Promo Image">
                            </div>
                        <?php else: ?>
                            <div style="width: 100px; height: 100px; border: 1px dashed var(--light-gray); display: flex; align-items: center; justify-content: center; color: var(--mid-gray); font-family: var(--f-mono); font-size: 9px; text-transform: uppercase; letter-spacing: 0.1em;">No Image</div>
                        <?php endif; ?>
                        <div style="flex: 1;">
                            <input type="file" name="promo_popup_image" class="field-input" accept="image/*">
                            <span class="field-sub">Recommended: Square or portrait image (e.g., 600x600px). Leave empty to keep current image.</span>
                            <input type="hidden" name="current_image" value="<?php echo htmlspecialchars($settings['promo_popup_image'] ?? ''); ?>">
                        </div>
                    </div>
                </div>

                <div style="margin-top: 24px; padding-top: 24px; border-top: 1px solid var(--light-gray);">
                    <button type="submit" class="btn-red">Save Configuration</button>
                </div>
            </form>
        </div>
    </div>

    <div class="panel">
        <div class="panel-header"><div class="panel-title">Quick Tips</div></div>
        <div class="panel-body" style="font-size: 13px; color: var(--mid-gray);">
            <div class="field-group">
                <strong style="color: var(--ink); display: block; margin-bottom: 4px;">Dismissal Logic</strong>
                The popup will automatically include a <span style="border: 1px solid var(--light-gray); padding: 1px 8px; font-family: var(--f-mono); font-size: 11px;">Don't show again</span> button. If a user clicks this, the popup will be permanently hidden for them on their current browser, overriding the frequency setting.
            </div>
            <div class="field-group" style="margin-bottom: 0;">
                <strong style="color: var(--ink); display: block; margin-bottom: 4px;">Frequency Setting</strong>
                If the user just clicks the standard "X" to close, the popup will reappear based on your selected frequency.
            </div>
        </div>
    </div>
</div>

<?php include 'includes/avazonia_footer.php'; ?>
