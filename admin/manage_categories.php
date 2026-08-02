<?php
/**
 * Admin: Manage Categories
 * - Admin-only CRUD for categories
 * - Lists categories with product counts and quick actions
 */
require_once '../includes/db.php';
session_start();
require_once '../includes/admin_guard.php';

$page_title = 'Manage Categories';
$errors = [];
$success = '';

/**
 * Handle category image upload
 */
function handleCategoryImageUpload($fieldName, $existing = '') {
    $targetDir = realpath(__DIR__ . '/../assets/images/categories');
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
    $newName = 'cat_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = $targetDir . DIRECTORY_SEPARATOR . $newName;
    if (move_uploaded_file($fileTmp, $dest)) {
        return $newName;
    }
    return $existing;
}

/**
 * Handle icon selection from library
 */
function handleLibraryIcon($iconFile, $existing = '') {
    if (!$iconFile) return $existing;
    $source = realpath(__DIR__ . '/../assets/images/category-icons/') . DIRECTORY_SEPARATOR . $iconFile;
    if (!file_exists($source)) return $existing;
    
    $ext = pathinfo($iconFile, PATHINFO_EXTENSION);
    $newName = 'cat_lib_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $destDir = realpath(__DIR__ . '/../assets/images/categories');
    if (!is_dir($destDir)) @mkdir($destDir, 0777, true);
    $dest = $destDir . DIRECTORY_SEPARATOR . $newName;
    
    if (copy($source, $dest)) {
        if ($existing) {
            $oldPath = $destDir . DIRECTORY_SEPARATOR . $existing;
            if (file_exists($oldPath)) @unlink($oldPath);
        }
        return $newName;
    }
    return $existing;
}

// Handle create/update/delete
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($action === 'create') {
            // Create new category
            $name = sanitizeInput($_POST['category_name'] ?? '');
            $description = sanitizeInput($_POST['description'] ?? '');
            if (!$name) {
                $errors[] = 'Category name is required';
            }
            if (empty($errors)) {
                $image = handleCategoryImageUpload('image');
                if (!$image && !empty($_POST['library_icon'])) {
                    $image = handleLibraryIcon($_POST['library_icon']);
                }
                $stmt = $pdo->prepare('INSERT INTO categories (category_name, description, image) VALUES (?, ?, ?)');
                $stmt->execute([$name, $description, $image]);
                $success = 'Category created successfully';
            }
        } elseif ($action === 'update') {
            // Update category
            $id = (int)($_POST['category_id'] ?? 0);
            $name = sanitizeInput($_POST['category_name'] ?? '');
            $description = sanitizeInput($_POST['description'] ?? '');
            if ($id <= 0) $errors[] = 'Invalid category';
            if (!$name) $errors[] = 'Category name is required';
            if (empty($errors)) {
                // Get current image to delete it later
                $stmt = $pdo->prepare('SELECT image FROM categories WHERE category_id = ?');
                $stmt->execute([$id]);
                $currentImage = $stmt->fetchColumn();

                $image = handleCategoryImageUpload('image', $currentImage);
                if ($image === $currentImage && !empty($_POST['library_icon'])) {
                    $image = handleLibraryIcon($_POST['library_icon'], $currentImage);
                }
                
                $stmt = $pdo->prepare('UPDATE categories SET category_name = ?, description = ?, image = ? WHERE category_id = ?');
                $stmt->execute([$name, $description, $image, $id]);
                
                $success = 'Category updated successfully';
            }
        } elseif ($action === 'delete') {
            // Delete category (cascade effects depend on DB constraints)
            $id = (int)($_POST['category_id'] ?? 0);
            if ($id <= 0) $errors[] = 'Invalid category';
            if (empty($errors)) {
                // Get image before delete
                $stmt = $pdo->prepare('SELECT image FROM categories WHERE category_id = ?');
                $stmt->execute([$id]);
                $image = $stmt->fetchColumn();

                $stmt = $pdo->prepare('DELETE FROM categories WHERE category_id = ?');
                $stmt->execute([$id]);

                // Delete file
                if (!empty($image)) {
                    $path = realpath(__DIR__ . '/../assets/images/categories/') . DIRECTORY_SEPARATOR . $image;
                    if (file_exists($path)) @unlink($path);
                }

                $success = 'Category deleted';
            }
        }
    }
} catch (PDOException $e) {
    error_log('Categories CRUD error: ' . $e->getMessage());
    $errors[] = 'Database error occurred';
}

// Fetch categories
$categories = [];
try {
    // Fetch categories with product counts for display
    $stmt = $pdo->query('SELECT c.*, COUNT(p.product_id) AS product_count FROM categories c LEFT JOIN products p ON p.category_id = c.category_id GROUP BY c.category_id ORDER BY c.category_name ASC');
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Fetch categories error: ' . $e->getMessage());
}

// Edit mode
$edit = null;
if (($action === 'edit') && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare('SELECT * FROM categories WHERE category_id = ?');
        $stmt->execute([$id]);
        $edit = $stmt->fetch();
    } catch (PDOException $e) {
        error_log('Fetch category error: ' . $e->getMessage());
    }
}

// Fetch library icons
$library_icons = [];
$icon_dir = realpath(__DIR__ . '/../assets/images/category-icons');
if (is_dir($icon_dir)) {
    $files = scandir($icon_dir);
    foreach ($files as $file) {
        if (in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['png','svg','jpg','jpeg','webp'])) {
            $library_icons[] = $file;
        }
    }
}
?>

<?php
$page_title = 'Category Management';
include 'includes/avazonia_header.php';
?>

<style>
.cat-thumb { width: 40px; height: 40px; border: 1px solid var(--light-gray); object-fit: cover; background: var(--off); border-radius: 4px; }
.icon-option { padding: 8px; border: 1px solid var(--light-gray); background: #fff; text-align: center; cursor: pointer; border-radius: 4px; transition: all 0.2s; }
.icon-option:hover, .icon-option.selected { border-color: var(--ink); background: var(--off); }
.icon-option img { max-height: 40px; object-fit: contain; width: 100%; }
.icon-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; max-height: 200px; overflow-y: auto; padding: 12px; background: var(--off); border: 1px solid var(--light-gray); border-radius: 4px; }
.file-input {
    width: 100%; padding: 12px 14px; border: 1px dashed var(--light-gray); border-radius: 4px;
    box-sizing: border-box; font-size: 13px; background: #fff; font-family: inherit;
}
</style>

<div class="admin-header">
    <h1>Category Management</h1>
    <button class="btn-red" onclick="openModal('categoryModal')">+ Add Category</button>
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
        <div class="panel-title">Existing Categories <span style="opacity: 0.4;">(<?php echo count($categories); ?>)</span></div>
    </div>
    <div class="table-container" style="border: none; margin-bottom: 0; border-radius: 0;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width: 70px;">Image</th>
                    <th>Category Info</th>
                    <th>Description</th>
                    <th style="text-align: center;">Products</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $c): ?>
                <tr>
                    <td>
                        <?php if (!empty($c['image'])): ?>
                            <img src="../assets/images/categories/<?php echo htmlspecialchars($c['image']); ?>" class="cat-thumb" alt="img">
                        <?php else: ?>
                            <div class="cat-thumb" style="display: flex; align-items: center; justify-content: center; opacity: 0.3;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="font-weight: 800;"><?php echo htmlspecialchars($c['category_name']); ?></div>
                        <div style="font-size: 10px; opacity: 0.5; font-family: var(--f-mono); margin-top: 2px;">ID: #<?php echo $c['category_id']; ?></div>
                    </td>
                    <td>
                        <div style="color: var(--mid-gray); font-size: 12px; max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                            <?php echo htmlspecialchars($c['description']); ?>
                        </div>
                    </td>
                    <td style="text-align: center;">
                        <span class="status-badge" style="background: var(--off); color: var(--ink);"><?php echo (int)$c['product_count']; ?> Items</span>
                    </td>
                    <td style="text-align: right;">
                        <div class="d-flex justify-content-end gap-2">
                            <a class="action-btn" href="manage_categories.php?action=edit&id=<?php echo $c['category_id']; ?>">Edit</a>
                            <form method="POST" action="" class="d-inline" onsubmit="return confirmAction(event, 'Delete this category?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="category_id" value="<?php echo $c['category_id']; ?>">
                                <button class="action-btn danger" type="submit">Del</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Category Modal -->
<div class="modal-overlay" id="categoryModal">
    <div class="modal-content">
        <button type="button" class="modal-close" onclick="closeModal('categoryModal')">×</button>
        <div class="modal-title"><?php echo $edit ? 'Edit Category' : 'Add New Category'; ?></div>
        <form method="POST" action="" enctype="multipart/form-data">
            <?php if ($edit): ?>
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="category_id" value="<?php echo $edit['category_id']; ?>">
            <?php else: ?>
                <input type="hidden" name="action" value="create">
            <?php endif; ?>

            <div class="field-group">
                <label class="field-label">Category Name</label>
                <input type="text" class="field-input" name="category_name" value="<?php echo htmlspecialchars($edit['category_name'] ?? ''); ?>" required placeholder="e.g. Laptops">
            </div>

            <div class="field-group">
                <label class="field-label">Description</label>
                <textarea class="field-input" name="description" rows="3" placeholder="Briefly describe this category..."><?php echo htmlspecialchars($edit['description'] ?? ''); ?></textarea>
            </div>

            <div class="field-group">
                <label class="field-label">Category Image</label>
                <div class="d-flex gap-2" style="margin-bottom: 10px; align-items: center;">
                    <button type="button" class="btn-ink" style="height: 36px; padding: 0 14px; font-size: 10px;" onclick="toggleLibrary()">Choose from Library</button>
                    <span class="field-sub" style="margin: 0;">or upload custom</span>
                </div>

                <input type="file" class="file-input" name="image" id="category_image_input" accept="image/*">
                <input type="hidden" name="library_icon" id="library_icon_input">

                <div id="iconLibrary" class="icon-grid" style="display: none; margin-top: 12px;">
                    <?php foreach ($library_icons as $icon): ?>
                        <div class="icon-option" onclick="selectIcon('<?php echo $icon; ?>', this)">
                            <img src="../assets/images/category-icons/<?php echo $icon; ?>" alt="icon">
                        </div>
                    <?php endforeach; ?>
                </div>

                <div id="iconPreview" class="d-flex align-items-center gap-2" style="margin-top: 12px; <?php echo ($edit && !empty($edit['image'])) ? '' : 'display: none;'; ?>">
                    <img id="currentIconImg" src="<?php echo ($edit && !empty($edit['image'])) ? '../assets/images/categories/'.htmlspecialchars($edit['image']) : ''; ?>" style="width: 48px; height: 48px; object-fit: contain; background: var(--off); border: 1px solid var(--light-gray); border-radius: 4px;">
                    <div class="field-sub" style="margin: 0;" id="iconPreviewText">
                        <?php echo ($edit && !empty($edit['image'])) ? 'Current Category Visual' : 'Selected Icon'; ?>
                    </div>
                </div>
            </div>

            <div class="modal-btn-row">
                <button type="button" class="btn-ink" style="flex: 1; justify-content: center;" onclick="closeModal('categoryModal')">Cancel</button>
                <button class="btn-red" style="flex: 1; justify-content: center;" type="submit"><?php echo $edit ? 'Save Changes' : 'Create Category'; ?></button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleLibrary() {
    const lib = document.getElementById('iconLibrary');
    lib.style.display = lib.style.display === 'none' ? 'grid' : 'none';
}

function selectIcon(filename, element) {
    document.querySelectorAll('.icon-option').forEach(el => el.classList.remove('selected'));
    element.classList.add('selected');
    document.getElementById('library_icon_input').value = filename;
    document.getElementById('category_image_input').value = '';
    const preview = document.getElementById('iconPreview');
    const img = document.getElementById('currentIconImg');
    const text = document.getElementById('iconPreviewText');
    preview.style.display = 'flex';
    img.src = '../assets/images/category-icons/' + filename;
    text.innerText = 'Selected Library Icon: ' + filename;
}

document.getElementById('category_image_input').addEventListener('change', function() {
    if (this.value) {
        document.getElementById('library_icon_input').value = '';
        document.querySelectorAll('.icon-option').forEach(el => el.classList.remove('selected'));
        document.getElementById('iconPreviewText').innerText = 'Custom Image Selected';
    }
});

document.addEventListener('DOMContentLoaded', function() {
    <?php if ($edit || !empty($errors)): ?>
    openModal('categoryModal');
    <?php endif; ?>
});
</script>

<?php include 'includes/avazonia_footer.php'; ?>
