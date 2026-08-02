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
include 'includes/avazonia_header.php';
?>

<div class="admin-header">
    <h1>Subcategory Management</h1>
    <button class="btn-red" onclick="openModal('subcategoryModal')">+ Add Subcategory</button>
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
        <div class="panel-title">Existing Subcategories <span style="opacity: 0.4;">(<?php echo count($subcategories); ?>)</span></div>
    </div>
    <div class="table-container" style="border: none; margin-bottom: 0; border-radius: 0;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Subcategory</th>
                    <th>Parent Category</th>
                    <th>Description</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($subcategories as $s): ?>
                <tr>
                    <td>
                        <div style="font-weight: 800;"><?php echo htmlspecialchars($s['subcategory_name']); ?></div>
                        <div style="font-size: 10px; opacity: 0.5; font-family: var(--f-mono); margin-top: 2px;">ID: #<?php echo $s['subcategory_id']; ?></div>
                    </td>
                    <td>
                        <span class="status-badge" style="background: var(--off); color: var(--ink);"><?php echo htmlspecialchars($s['category_name']); ?></span>
                    </td>
                    <td>
                        <div style="color: var(--mid-gray); font-size: 12px; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                            <?php echo htmlspecialchars($s['description']); ?>
                        </div>
                    </td>
                    <td style="text-align: right;">
                        <div class="d-flex justify-content-end gap-2">
                            <a class="action-btn" href="manage_subcategories.php?action=edit&id=<?php echo $s['subcategory_id']; ?>">Edit</a>
                            <form method="POST" action="" class="d-inline" onsubmit="return confirmAction(event, 'Delete this subcategory?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="subcategory_id" value="<?php echo $s['subcategory_id']; ?>">
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

<!-- Subcategory Modal -->
<div class="modal-overlay" id="subcategoryModal">
    <div class="modal-content">
        <button type="button" class="modal-close" onclick="closeModal('subcategoryModal')">×</button>
        <div class="modal-title"><?php echo $edit ? 'Edit Subcategory' : 'Add New Subcategory'; ?></div>
        <form method="POST" action="">
            <?php if ($edit): ?>
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="subcategory_id" value="<?php echo $edit['subcategory_id']; ?>">
            <?php else: ?>
                <input type="hidden" name="action" value="create">
            <?php endif; ?>

            <div class="field-group">
                <label class="field-label">Parent Category</label>
                <select class="field-input" name="category_id" required>
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?php echo $c['category_id']; ?>" <?php echo ($edit && $edit['category_id']==$c['category_id'])?'selected':''; ?>>
                            <?php echo htmlspecialchars($c['category_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field-group">
                <label class="field-label">Subcategory Name</label>
                <input type="text" class="field-input" name="subcategory_name" value="<?php echo htmlspecialchars($edit['subcategory_name'] ?? ''); ?>" required placeholder="e.g. Gaming Laptops">
            </div>

            <div class="field-group">
                <label class="field-label">Description</label>
                <textarea class="field-input" name="description" rows="3" placeholder="Briefly describe this subcategory..."><?php echo htmlspecialchars($edit['description'] ?? ''); ?></textarea>
            </div>

            <div class="modal-btn-row">
                <button type="button" class="btn-ink" style="flex: 1; justify-content: center;" onclick="closeModal('subcategoryModal')">Cancel</button>
                <button class="btn-red" style="flex: 1; justify-content: center;" type="submit"><?php echo $edit ? 'Save Changes' : 'Create Subcategory'; ?></button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($edit || !empty($errors)): ?>
    openModal('subcategoryModal');
    <?php endif; ?>
});
</script>

<?php include 'includes/avazonia_footer.php'; ?>



