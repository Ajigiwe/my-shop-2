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
include 'includes/header-new.php';
?>

<div class="row">
    <div class="col-12">
        <?php if ($success): ?>
            <div class="alert alert-success border-0 rounded-4 mb-4 small fw-bold animate-up">
                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger border-0 rounded-4 mb-4 small fw-bold animate-up">
                <ul class="mb-0">
                    <?php foreach ($errors as $e): ?><li><?php echo htmlspecialchars($e); ?></li><?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="admin-card animate-up">
            <div class="admin-card-header d-flex justify-content-between align-items-center">
                <h5 class="admin-card-title mb-0">Active Slides <span class="badge bg-light text-dark ms-2 rounded-pill"><?php echo count($slides); ?></span></h5>
                <button type="button" class="btn-premium py-1 px-3 small" data-bs-toggle="modal" data-bs-target="#heroModal">
                    <i class="fas fa-plus me-2"></i>Add Slide
                </button>
            </div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Preview</th>
                            <th>Title</th>
                            <th class="text-center">Order</th>
                            <th class="text-center">Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($slides as $s): ?>
                        <tr>
                            <td>
                                <div class="rounded-4 shadow-sm border overflow-hidden" style="width: 100px; height: 60px;">
                                    <img src="<?php echo (strpos($s['image_path'], 'assets/') === 0) ? '../'.$s['image_path'] : '../assets/images/'.$s['image_path']; ?>" 
                                         class="w-100 h-100 object-fit-cover">
                                </div>
                            </td>
                            <td>
                                <div class="fw-black text-[15px]"><?php echo htmlspecialchars($s['title_black']); ?></div>
                                <div class="small text-muted fw-bold uppercase tracking-widest text-[10px]"><?php echo htmlspecialchars($s['badge_text']); ?></div>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark rounded-pill px-3"><?php echo $s['display_order']; ?></span>
                            </td>
                            <td class="text-center">
                                <span class="badge rounded-pill px-3 py-2 bg-<?php echo $s['is_active'] ? 'success' : 'danger'; ?>-subtle text-<?php echo $s['is_active'] ? 'success' : 'danger'; ?> fw-bold">
                                    <?php echo $s['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="manage_hero.php?action=edit&id=<?php echo $s['id']; ?>" class="btn-premium-outline px-3 py-1 text-decoration-none small">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" action="manage_hero.php" class="d-inline" onsubmit="return confirmAction(event, 'Permanently delete this slide?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $s['id']; ?>">
                                        <button type="submit" class="btn-premium-outline px-3 py-1 text-danger border-danger/20">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($slides)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted fw-bold">No slides found. Add your first one!</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Hero Modal -->
<div class="modal fade" id="heroModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header border-0 bg-dark text-white p-4">
                <h5 class="modal-title fw-bold">
                    <i class="fas <?php echo $edit ? 'fa-edit' : 'fa-plus-circle'; ?> me-2"></i>
                    <?php echo $edit ? 'Edit Hero Slide' : 'Create New Hero Slide'; ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form method="POST" action="manage_hero.php" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="<?php echo $edit ? 'update' : 'create'; ?>">
                    <?php if ($edit): ?>
                        <input type="hidden" name="id" value="<?php echo $edit['id']; ?>">
                        <input type="hidden" name="existing_image" value="<?php echo $edit['image_path']; ?>">
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="stat-label small mb-1 uppercase tracking-wider fw-bold">Badge Text</label>
                            <input type="text" name="badge_text" class="form-control rounded-3" placeholder="e.g. New Arrival" value="<?php echo $edit['badge_text'] ?? ''; ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="stat-label small mb-1 uppercase tracking-wider fw-bold">Main Title</label>
                            <input type="text" name="title_black" class="form-control rounded-3" placeholder="Primary Title Text" value="<?php echo $edit['title_black'] ?? ''; ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="stat-label small mb-1 uppercase tracking-wider fw-bold">Secondary Title (Grayed)</label>
                        <input type="text" name="title_gray" class="form-control rounded-3" placeholder="Sub-heading or suffix" value="<?php echo $edit['title_gray'] ?? ''; ?>">
                    </div>

                    <div class="mb-3">
                        <label class="stat-label small mb-1 uppercase tracking-wider fw-bold">Description</label>
                        <textarea name="description" class="form-control rounded-3" rows="3" placeholder="Tell the story of this slide..."><?php echo $edit['description'] ?? ''; ?></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="stat-label small mb-1 uppercase tracking-wider fw-bold">Button Text</label>
                            <input type="text" name="button_text" class="form-control rounded-3" value="<?php echo $edit['button_text'] ?? 'Shop Now'; ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="stat-label small mb-1 uppercase tracking-wider fw-bold">Button Link</label>
                            <input type="text" name="button_link" class="form-control rounded-3" value="<?php echo $edit['button_link'] ?? 'shop.php'; ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="stat-label small mb-1 uppercase tracking-wider fw-bold">Slide Background Visual</label>
                        <input type="file" name="image" class="form-control rounded-3" <?php echo !$edit ? 'required' : ''; ?>>
                        <?php if ($edit): ?>
                            <div class="mt-3 p-2 bg-light rounded-3 d-flex align-items-center gap-3">
                                <img src="<?php echo (strpos($edit['image_path'], 'assets/') === 0) ? '../'.$edit['image_path'] : '../assets/images/'.$edit['image_path']; ?>" 
                                     class="rounded-2 shadow-sm" style="width: 100px; height: 50px; object-fit: cover;">
                                <div class="small text-muted fw-bold">Current Active Background</div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="row align-items-center mb-4">
                        <div class="col-6">
                            <label class="stat-label small mb-1 uppercase tracking-wider fw-bold">Display Order</label>
                            <input type="number" name="display_order" class="form-control rounded-3" value="<?php echo $edit['display_order'] ?? 0; ?>">
                        </div>
                        <div class="col-6">
                            <div class="form-check form-switch mt-4 ps-5">
                                <input class="form-check-input scale-125" type="checkbox" name="is_active" <?php echo (!isset($edit['is_active']) || $edit['is_active']) ? 'checked' : ''; ?>>
                                <label class="form-check-label stat-label ms-2 small fw-bold uppercase tracking-wider">Slide Active</label>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn-premium w-100 py-3 rounded-3 shadow-sm">
                            <i class="fas fa-save me-2"></i><?php echo $edit ? 'Apply Update' : 'Publish Slide'; ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($edit || !empty($errors)): ?>
    var myModal = new bootstrap.Modal(document.getElementById('heroModal'));
    myModal.show();
    <?php endif; ?>
});
</script>

<?php include 'includes/footer-new.php'; ?>
