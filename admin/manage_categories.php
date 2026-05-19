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
                <h5 class="admin-card-title mb-0">Existing Categories <span class="badge bg-light text-dark ms-2 rounded-pill"><?php echo count($categories); ?></span></h5>
                <button type="button" class="btn-premium py-1 px-3 small" data-bs-toggle="modal" data-bs-target="#categoryModal">
                    <i class="fas fa-plus me-2"></i>Add Category
                </button>
            </div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Category Info</th>
                            <th>Description</th>
                            <th class="text-center">Products</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $c): ?>
                        <tr>
                            <td style="width: 50px;">
                                <?php if (!empty($c['image'])): ?>
                                    <img src="../assets/images/categories/<?php echo htmlspecialchars($c['image']); ?>" width="36" height="36" class="rounded-2 shadow-sm object-fit-cover" alt="img">
                                <?php else: ?>
                                    <div class="w-[36px] h-[36px] bg-light rounded-2 flex items-center justify-center text-muted">
                                        <i class="fas fa-image" style="font-size: 12px;"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-black text-[13px]"><?php echo htmlspecialchars($c['category_name']); ?></div>
                                <div class="small text-muted fw-bold uppercase tracking-widest text-[9px] mt-0.5">ID: #<?php echo $c['category_id']; ?></div>
                            </td>
                            <td>
                                <div class="text-muted small" style="max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    <?php echo htmlspecialchars($c['description']); ?>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-dark rounded-pill px-2 py-1 small"><?php echo (int)$c['product_count']; ?> Items</span>
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-1">
                                    <a class="btn-premium-outline px-2 py-1 text-decoration-none text-[12px]" href="manage_categories.php?action=edit&id=<?php echo $c['category_id']; ?>">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" action="" class="d-inline" onsubmit="return confirmAction(event, 'Delete this category?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="category_id" value="<?php echo $c['category_id']; ?>">
                                        <button class="btn-premium-outline px-2 py-1 text-danger border-danger/20 text-[12px]" type="submit">
                                            <i class="fas fa-trash"></i>
                                        </button>
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

<!-- Category Modal -->
<div class="modal fade" id="categoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header border-0 bg-dark text-white p-4">
                <h5 class="modal-title fw-bold">
                    <i class="fas <?php echo $edit ? 'fa-edit' : 'fa-plus-circle'; ?> me-2"></i>
                    <?php echo $edit ? 'Edit Category' : 'Add New Category'; ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form method="POST" action="" enctype="multipart/form-data">
                    <?php if ($edit): ?>
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="category_id" value="<?php echo $edit['category_id']; ?>">
                    <?php else: ?>
                        <input type="hidden" name="action" value="create">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="stat-label small mb-1 uppercase tracking-wider fw-bold">Category Name</label>
                        <input type="text" class="form-control rounded-3" name="category_name" value="<?php echo htmlspecialchars($edit['category_name'] ?? ''); ?>" required placeholder="e.g. Laptops">
                    </div>

                    <div class="mb-3">
                        <label class="stat-label small mb-1 uppercase tracking-wider fw-bold">Description</label>
                        <textarea class="form-control rounded-3" name="description" rows="3" placeholder="Briefly describe this category..."><?php echo htmlspecialchars($edit['description'] ?? ''); ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="stat-label small mb-1 uppercase tracking-wider fw-bold">Category Image</label>
                        <div class="d-flex gap-2 mb-2">
                            <button type="button" class="btn btn-light btn-sm rounded-3 border fw-bold text-[11px] px-3 py-2" onclick="toggleLibrary()">
                                <i class="fas fa-th-large me-2"></i>Choose from Library
                            </button>
                            <span class="text-muted small align-self-center">or upload custom</span>
                        </div>
                        
                        <input type="file" class="form-control rounded-3" name="image" id="category_image_input" accept="image/*">
                        <input type="hidden" name="library_icon" id="library_icon_input">

                        <div id="iconLibrary" class="mt-3 p-3 bg-light rounded-4 border" style="display: none;">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0 fw-bold small uppercase tracking-wider">Icon Library</h6>
                                <button type="button" class="btn-close small" onclick="toggleLibrary()" style="font-size: 10px;"></button>
                            </div>
                            <div class="row g-2 overflow-auto" style="max-height: 200px;">
                                <?php foreach ($library_icons as $icon): ?>
                                    <div class="col-3">
                                        <div class="icon-option p-2 rounded-3 border bg-white text-center cursor-pointer hover:border-dark transition-all" 
                                             onclick="selectIcon('<?php echo $icon; ?>', this)">
                                            <img src="../assets/images/category-icons/<?php echo $icon; ?>" class="w-100 h-auto object-contain" style="max-height: 40px;">
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div id="iconPreview" class="mt-3 p-2 bg-light rounded-3 d-flex align-items-center gap-3" <?php echo ($edit && !empty($edit['image'])) ? '' : 'style="display: none;"'; ?>>
                            <img id="currentIconImg" src="<?php echo ($edit && !empty($edit['image'])) ? '../assets/images/categories/'.htmlspecialchars($edit['image']) : ''; ?>" 
                                 class="rounded-2 shadow-sm" style="width: 60px; height: 60px; object-fit: contain; background: white;">
                            <div class="small text-muted fw-bold" id="iconPreviewText">
                                <?php echo ($edit && !empty($edit['image'])) ? 'Current Category Visual' : 'Selected Icon'; ?>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button class="btn-premium w-100 py-3 rounded-3 shadow-sm" type="submit">
                            <i class="fas fa-save me-2"></i><?php echo $edit ? 'Save Changes' : 'Create Category'; ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function toggleLibrary() {
    const lib = document.getElementById('iconLibrary');
    lib.style.display = lib.style.display === 'none' ? 'block' : 'none';
}

function selectIcon(filename, element) {
    // Remove selected class from others
    document.querySelectorAll('.icon-option').forEach(el => el.classList.remove('border-dark', 'bg-dark-subtle'));
    
    // Add to current
    element.classList.add('border-dark', 'bg-dark-subtle');
    
    // Set hidden input
    document.getElementById('library_icon_input').value = filename;
    
    // Clear file input
    document.getElementById('category_image_input').value = '';
    
    // Show preview
    const preview = document.getElementById('iconPreview');
    const img = document.getElementById('currentIconImg');
    const text = document.getElementById('iconPreviewText');
    
    preview.style.display = 'flex';
    img.src = '../assets/images/category-icons/' + filename;
    img.style.objectFit = 'contain';
    text.innerText = 'Selected Library Icon: ' + filename;
}

document.getElementById('category_image_input').addEventListener('change', function() {
    if (this.value) {
        document.getElementById('library_icon_input').value = '';
        document.querySelectorAll('.icon-option').forEach(el => el.classList.remove('border-dark', 'bg-dark-subtle'));
        document.getElementById('iconPreviewText').innerText = 'Custom Image Selected';
    }
});

document.addEventListener('DOMContentLoaded', function() {
    <?php if ($edit || !empty($errors)): ?>
    var myModal = new bootstrap.Modal(document.getElementById('categoryModal'));
    myModal.show();
    <?php endif; ?>
});
</script>

<?php include 'includes/footer-new.php'; ?>
