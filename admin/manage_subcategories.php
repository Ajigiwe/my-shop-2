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

<?php include '../includes/header.php'; ?>

<div class="container py-4">
  

    <?php if ($success): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $e): ?>
                    <li><?php echo htmlspecialchars($e); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><?php echo $edit ? 'Edit Subcategory' : 'Add New Subcategory'; ?></h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <?php if ($edit): ?>
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="subcategory_id" value="<?php echo $edit['subcategory_id']; ?>">
                        <?php else: ?>
                            <input type="hidden" name="action" value="create">
                        <?php endif; ?>
                        <div class="mb-3">
                            <label class="form-label">Parent Category</label>
                            <select class="form-select" name="category_id" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $c): ?>
                                    <option value="<?php echo $c['category_id']; ?>" <?php echo ($edit && $edit['category_id']==$c['category_id'])?'selected':''; ?>>
                                        <?php echo htmlspecialchars($c['category_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Subcategory Name</label>
                            <input type="text" class="form-control" name="subcategory_name" value="<?php echo htmlspecialchars($edit['subcategory_name'] ?? ''); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3"><?php echo htmlspecialchars($edit['description'] ?? ''); ?></textarea>
                        </div>
                        <button class="btn btn-primary w-100" type="submit"><i class="fas fa-save me-2"></i><?php echo $edit ? 'Update' : 'Create'; ?> Subcategory</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">All Subcategories</h5>
                    <span class="badge bg-primary"><?php echo count($subcategories); ?></span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Subcategory</th>
                                    <th>Category</th>
                                    <th>Description</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($subcategories as $s): ?>
                                    <tr>
                                        <td><?php echo $s['subcategory_id']; ?></td>
                                        <td><?php echo htmlspecialchars($s['subcategory_name']); ?></td>
                                        <td><span class="badge bg-info"><?php echo htmlspecialchars($s['category_name']); ?></span></td>
                                        <td class="small text-muted" style="max-width: 300px;"><?php echo htmlspecialchars($s['description']); ?></td>
                                        <td>
                                            <a class="btn btn-sm btn-outline-primary" href="manage_subcategories.php?action=edit&id=<?php echo $s['subcategory_id']; ?>"><i class="fas fa-edit"></i></a>
                                            <form method="POST" action="" class="d-inline" onsubmit="return confirm('Delete this subcategory?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="subcategory_id" value="<?php echo $s['subcategory_id']; ?>">
                                                <button class="btn btn-sm btn-outline-danger" type="submit"><i class="fas fa-trash"></i></button>
                                            </form>
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



