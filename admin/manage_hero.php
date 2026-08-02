<?php
/**
 * Admin: Manage Hero Slider
 * - CRUD for homepage hero slides
 */
require_once '../includes/db.php';
session_start();
require_once '../includes/admin_guard.php';

$page_title = 'Manage Hero Slider';
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

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($action === 'create' || $action === 'update') {
            $id = (int)($_POST['id'] ?? 0);
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
            if ($action === 'create' && empty($_FILES['image']['name'])) {
                $errors[] = 'A slide image is required';
            }

            if (empty($errors)) {
                $image = handleHeroUpload('image', $_POST['existing_image'] ?? '');
                
                if ($action === 'create') {
                    $stmt = $pdo->prepare("INSERT INTO hero_slides (badge_text, title_black, title_gray, description, button_text, button_link, secondary_button_text, secondary_button_link, image_path, display_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$badge_text, $title_black, $title_gray, $description, $button_text, $button_link, $secondary_button_text, $secondary_button_link, $image, $display_order, $is_active]);
                    $success = 'Slide added successfully';
                } else {
                    $stmt = $pdo->prepare("UPDATE hero_slides SET badge_text = ?, title_black = ?, title_gray = ?, description = ?, button_text = ?, button_link = ?, secondary_button_text = ?, secondary_button_link = ?, image_path = ?, display_order = ?, is_active = ? WHERE id = ?");
                    $stmt->execute([$badge_text, $title_black, $title_gray, $description, $button_text, $button_link, $secondary_button_text, $secondary_button_link, $image, $display_order, $is_active, $id]);
                    $success = 'Slide updated successfully';
                }
            }
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                // Delete image file first
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

// Fetch all slides
$slides = $pdo->query("SELECT * FROM hero_slides ORDER BY display_order ASC, created_at DESC")->fetchAll();

// Editing slide
$edit = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    foreach ($slides as $s) {
        if ($s['id'] == $id) {
            $edit = $s;
            break;
        }
    }
}

?>
<?php
$page_title = 'Hero Slider Manager';
include 'includes/avazonia_header.php';
?>

<style>
.slide-thumb { width: 100px; height: 60px; border: 1px solid var(--light-gray); object-fit: cover; border-radius: 4px; background: var(--off); }
.order-badge { display: inline-block; padding: 4px 12px; border: 1px solid var(--light-gray); border-radius: 99px; font-family: var(--f-mono); font-size: 11px; font-weight: 700; }
</style>

<div class="admin-header">
    <h1>Hero Slider Manager</h1>
    <button class="btn-red" onclick="openModal('heroModal')">+ Add Slide</button>
</div>

<?php if ($success): ?>
    <div class="alert-box alert-success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>
<?php if (!empty($errors)): ?>
    <div class="alert-box alert-error">
        <ul style="margin: 0; padding-left: 20px;">
            <?php foreach ($errors as $e): ?><li><?php echo htmlspecialchars($e); ?></li><?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="panel">
    <div class="panel-header">
        <div class="panel-title">Active Slides <span style="opacity: 0.4;">(<?php echo count($slides); ?>)</span></div>
    </div>
    <div class="table-container" style="border: none; margin-bottom: 0; border-radius: 0;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Preview</th>
                    <th>Title</th>
                    <th style="text-align: center;">Order</th>
                    <th style="text-align: center;">Status</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($slides as $s): ?>
                <tr>
                    <td>
                        <img class="slide-thumb" src="<?php echo (strpos($s['image_path'], 'assets/') === 0) ? '../'.$s['image_path'] : '../assets/images/'.$s['image_path']; ?>" alt="slide">
                    </td>
                    <td>
                        <div style="font-weight: 800; font-size: 14px;"><?php echo htmlspecialchars($s['title_black']); ?></div>
                        <div style="font-size: 10px; opacity: 0.5; font-family: var(--f-mono); text-transform: uppercase; letter-spacing: 0.08em; margin-top: 2px;"><?php echo htmlspecialchars($s['badge_text']); ?></div>
                    </td>
                    <td style="text-align: center;">
                        <span class="order-badge"><?php echo $s['display_order']; ?></span>
                    </td>
                    <td style="text-align: center;">
                        <span class="status-badge <?php echo $s['is_active'] ? 'status-active' : 'status-suspended'; ?>">
                            <?php echo $s['is_active'] ? 'Active' : 'Inactive'; ?>
                        </span>
                    </td>
                    <td style="text-align: right;">
                        <div class="d-flex justify-content-end gap-2">
                            <a href="manage_hero.php?action=edit&id=<?php echo $s['id']; ?>" class="action-btn">Edit</a>
                            <form method="POST" action="manage_hero.php" class="d-inline" onsubmit="return confirmAction(event, 'Permanently delete this slide?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $s['id']; ?>">
                                <button type="submit" class="action-btn danger">Del</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($slides)): ?>
                <tr>
                    <td colspan="5" style="text-align: center; padding: 48px; color: var(--mid-gray);">No slides found. Add your first one!</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Hero Modal -->
<div class="modal-overlay" id="heroModal">
    <div class="modal-content wide">
        <button type="button" class="modal-close" onclick="closeModal('heroModal')">×</button>
        <div class="modal-title"><?php echo $edit ? 'Edit Hero Slide' : 'Create New Hero Slide'; ?></div>
        <form method="POST" action="manage_hero.php" enctype="multipart/form-data">
            <input type="hidden" name="action" value="<?php echo $edit ? 'update' : 'create'; ?>">
            <?php if ($edit): ?>
                <input type="hidden" name="id" value="<?php echo $edit['id']; ?>">
                <input type="hidden" name="existing_image" value="<?php echo $edit['image_path']; ?>">
            <?php endif; ?>

            <div class="field-grid">
                <div class="field-group">
                    <label class="field-label">Badge Text</label>
                    <input type="text" name="badge_text" class="field-input" placeholder="e.g. New Arrival" value="<?php echo $edit['badge_text'] ?? ''; ?>">
                </div>
                <div class="field-group">
                    <label class="field-label">Main Title</label>
                    <input type="text" name="title_black" class="field-input" placeholder="Primary Title Text" value="<?php echo $edit['title_black'] ?? ''; ?>" required>
                </div>
            </div>

            <div class="field-group">
                <label class="field-label">Secondary Title (Grayed)</label>
                <input type="text" name="title_gray" class="field-input" placeholder="Sub-heading or suffix" value="<?php echo $edit['title_gray'] ?? ''; ?>">
            </div>

            <div class="field-group">
                <label class="field-label">Description</label>
                <textarea name="description" class="field-input" rows="3" placeholder="Tell the story of this slide..."><?php echo $edit['description'] ?? ''; ?></textarea>
            </div>

            <div class="field-grid">
                <div class="field-group">
                    <label class="field-label">Button Text</label>
                    <input type="text" name="button_text" class="field-input" value="<?php echo $edit['button_text'] ?? 'Shop Now'; ?>">
                </div>
                <div class="field-group">
                    <label class="field-label">Button Link</label>
                    <input type="text" name="button_link" class="field-input" value="<?php echo $edit['button_link'] ?? 'shop.php'; ?>">
                </div>
            </div>

            <div class="field-group">
                <label class="field-label">Slide Background Visual</label>
                <input type="file" name="image" class="file-input" <?php echo !$edit ? 'required' : ''; ?>>
                <?php if ($edit): ?>
                    <div class="d-flex align-items-center gap-2" style="margin-top: 12px;">
                        <img src="<?php echo (strpos($edit['image_path'], 'assets/') === 0) ? '../'.$edit['image_path'] : '../assets/images/'.$edit['image_path']; ?>" style="width: 100px; height: 50px; object-fit: cover; border: 1px solid var(--light-gray); border-radius: 4px;" alt="bg">
                        <span class="field-sub" style="margin: 0;">Current Active Background</span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="field-grid">
                <div class="field-group">
                    <label class="field-label">Display Order</label>
                    <input type="number" name="display_order" class="field-input" value="<?php echo $edit['display_order'] ?? 0; ?>">
                </div>
                <div class="field-group" style="display: flex; align-items: center; gap: 10px; padding-top: 24px;">
                    <input type="checkbox" name="is_active" id="heroActive" value="1" <?php echo (!isset($edit['is_active']) || $edit['is_active']) ? 'checked' : ''; ?> style="width: 16px; height: 16px; accent-color: var(--red);">
                    <label class="field-label" for="heroActive" style="margin: 0;">Slide Active</label>
                </div>
            </div>

            <div class="modal-btn-row">
                <button type="button" class="btn-ink" style="flex: 1; justify-content: center;" onclick="closeModal('heroModal')">Cancel</button>
                <button type="submit" class="btn-red" style="flex: 1; justify-content: center;"><?php echo $edit ? 'Apply Update' : 'Publish Slide'; ?></button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($edit || !empty($errors)): ?>
    openModal('heroModal');
    <?php endif; ?>
});
</script>

<?php include 'includes/avazonia_footer.php'; ?>
