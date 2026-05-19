<?php
/**
 * Admin: Manage Subcategories
 * - Admin-only CRUD for subcategories
 * - Links each subcategory to a parent category
 * - Lists subcategories with parent category and actions
 */
require_once '../includes/db.php';
session_start();
require_once '../includes/admin_guard.php';

$page_title = 'Manage Subcategories';
$errors = [];
$success = '';

// Fetch categories for select
$categories = [];
try {
    $stmt = $pdo->query('SELECT category_id, category_name FROM categories ORDER BY category_name');
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Fetch categories error: ' . $e->getMessage());
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($action === 'create') {
            // Create new subcategory
            $category_id = (int)($_POST['category_id'] ?? 0);
            $name = sanitizeInput($_POST['subcategory_name'] ?? '');
            $description = sanitizeInput($_POST['description'] ?? '');
            if ($category_id <= 0) $errors[] = 'Category is required';
            if (!$name) $errors[] = 'Subcategory name is required';
            if (empty($errors)) {
                $stmt = $pdo->prepare('INSERT INTO subcategories (category_id, subcategory_name, description) VALUES (?, ?, ?)');
                $stmt->execute([$category_id, $name, $description]);
                $success = 'Subcategory created successfully';
            }
        } elseif ($action === 'update') {
            // Update subcategory
            $id = (int)($_POST['subcategory_id'] ?? 0);
            $category_id = (int)($_POST['category_id'] ?? 0);
            $name = sanitizeInput($_POST['subcategory_name'] ?? '');
            $description = sanitizeInput($_POST['description'] ?? '');
            if ($id <= 0) $errors[] = 'Invalid subcategory';
            if ($category_id <= 0) $errors[] = 'Category is required';
            if (!$name) $errors[] = 'Subcategory name is required';
            if (empty($errors)) {
                $stmt = $pdo->prepare('UPDATE subcategories SET category_id = ?, subcategory_name = ?, description = ? WHERE subcategory_id = ?');
                $stmt->execute([$category_id, $name, $description, $id]);
                $success = 'Subcategory updated successfully';
            }
        } elseif ($action === 'delete') {
            // Delete subcategory
            $id = (int)($_POST['subcategory_id'] ?? 0);
            if ($id <= 0) $errors[] = 'Invalid subcategory';
            if (empty($errors)) {
                $stmt = $pdo->prepare('DELETE FROM subcategories WHERE subcategory_id = ?');
                $stmt->execute([$id]);
                $success = 'Subcategory deleted';
            }
        }
    }
} catch (PDOException $e) {
    error_log('Subcategories CRUD error: ' . $e->getMessage());
    $errors[] = 'Database error occurred';
}

// Edit mode
$edit = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare('SELECT * FROM subcategories WHERE subcategory_id = ?');
        $stmt->execute([$id]);
        $edit = $stmt->fetch();
    } catch (PDOException $e) {
        error_log('Fetch subcategory error: ' . $e->getMessage());
    }
}

// Fetch subcategories list
$subcategories = [];
try {
    // Fetch subcategories with their parent category for display
    $stmt = $pdo->query('SELECT s.*, c.category_name FROM subcategories s JOIN categories c ON c.category_id = s.category_id ORDER BY c.category_name, s.subcategory_name');
    $subcategories = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Fetch subcategories list error: ' . $e->getMessage());
}
?>

<?php
$page_title = 'Subcategory Management';
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
                <h5 class="admin-card-title mb-0">Existing Subcategories <span class="badge bg-light text-dark ms-2 rounded-pill"><?php echo count($subcategories); ?></span></h5>
                <button type="button" class="btn-premium py-1 px-3 small" data-bs-toggle="modal" data-bs-target="#subcategoryModal">
                    <i class="fas fa-plus me-2"></i>Add Subcategory
                </button>
            </div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Subcategory</th>
                            <th>Parent Category</th>
                            <th>Description</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subcategories as $s): ?>
                        <tr>
                            <td>
                                <div class="fw-black text-[13px]"><?php echo htmlspecialchars($s['subcategory_name']); ?></div>
                                <div class="small text-muted fw-bold uppercase tracking-widest text-[9px] mt-0.5">ID: #<?php echo $s['subcategory_id']; ?></div>
                            </td>
                            <td>
                                <span class="badge bg-dark rounded-pill px-2 py-1 small"><?php echo htmlspecialchars($s['category_name']); ?></span>
                            </td>
                            <td>
                                <div class="text-muted small" style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    <?php echo htmlspecialchars($s['description']); ?>
                                </div>
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-1">
                                    <a class="btn-premium-outline px-2 py-1 text-decoration-none text-[12px]" href="manage_subcategories.php?action=edit&id=<?php echo $s['subcategory_id']; ?>">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" action="" class="d-inline" onsubmit="return confirmAction(event, 'Delete this subcategory?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="subcategory_id" value="<?php echo $s['subcategory_id']; ?>">
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

<!-- Subcategory Modal -->
<div class="modal fade" id="subcategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header border-0 bg-dark text-white p-4">
                <h5 class="modal-title fw-bold">
                    <i class="fas <?php echo $edit ? 'fa-edit' : 'fa-plus-circle'; ?> me-2"></i>
                    <?php echo $edit ? 'Edit Subcategory' : 'Add New Subcategory'; ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form method="POST" action="">
                    <?php if ($edit): ?>
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="subcategory_id" value="<?php echo $edit['subcategory_id']; ?>">
                    <?php else: ?>
                        <input type="hidden" name="action" value="create">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="stat-label small mb-1 uppercase tracking-wider fw-bold">Parent Category</label>
                        <select class="form-select rounded-3 fw-bold" name="category_id" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $c): ?>
                                <option value="<?php echo $c['category_id']; ?>" <?php echo ($edit && $edit['category_id']==$c['category_id'])?'selected':''; ?>>
                                    <?php echo htmlspecialchars($c['category_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="stat-label small mb-1 uppercase tracking-wider fw-bold">Subcategory Name</label>
                        <input type="text" class="form-control rounded-3" name="subcategory_name" value="<?php echo htmlspecialchars($edit['subcategory_name'] ?? ''); ?>" required placeholder="e.g. Gaming Laptops">
                    </div>

                    <div class="mb-3">
                        <label class="stat-label small mb-1 uppercase tracking-wider fw-bold">Description</label>
                        <textarea class="form-control rounded-3" name="description" rows="3" placeholder="Briefly describe this subcategory..."><?php echo htmlspecialchars($edit['description'] ?? ''); ?></textarea>
                    </div>

                    <div class="mt-4">
                        <button class="btn-premium w-100 py-3 rounded-3 shadow-sm" type="submit">
                            <i class="fas fa-save me-2"></i><?php echo $edit ? 'Save Changes' : 'Create Subcategory'; ?>
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
    var myModal = new bootstrap.Modal(document.getElementById('subcategoryModal'));
    myModal.show();
    <?php endif; ?>
});
</script>

<?php include 'includes/footer-new.php'; ?>



