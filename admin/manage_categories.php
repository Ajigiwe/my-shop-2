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
                $stmt = $pdo->prepare('INSERT INTO categories (category_name, description) VALUES (?, ?)');
                $stmt->execute([$name, $description]);
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
                $stmt = $pdo->prepare('UPDATE categories SET category_name = ?, description = ? WHERE category_id = ?');
                $stmt->execute([$name, $description, $id]);
                $success = 'Category updated successfully';
            }
        } elseif ($action === 'delete') {
            // Delete category (cascade effects depend on DB constraints)
            $id = (int)($_POST['category_id'] ?? 0);
            if ($id <= 0) $errors[] = 'Invalid category';
            if (empty($errors)) {
                $stmt = $pdo->prepare('DELETE FROM categories WHERE category_id = ?');
                $stmt->execute([$id]);
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
?>

<?php include '../includes/header.php'; ?>

<div class="container py-4">

    <!-- Back to Dashboard Button -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="dashboard.php" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
            </a>
        </div>
        <div>
            <h2 class="mb-0">Manage Categories</h2>
        </div>
        <div>
            <!-- Spacer for centering -->
        </div>
    </div>
        <div>
            <h2 class="mb-0">Manage Categories</h2>
        </div>
        <div>
            <!-- Spacer for centering -->
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $e): ?>
                    <li><?php echo htmlspecialchars($e); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><?php echo $edit ? 'Edit Category' : 'Add New Category'; ?></h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <?php if ($edit): ?>
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="category_id" value="<?php echo $edit['category_id']; ?>">
                        <?php else: ?>
                            <input type="hidden" name="action" value="create">
                        <?php endif; ?>
                        <div class="mb-3">
                            <label class="form-label">Category Name</label>
                            <input type="text" class="form-control" name="category_name" value="<?php echo htmlspecialchars($edit['category_name'] ?? ''); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3"><?php echo htmlspecialchars($edit['description'] ?? ''); ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-save me-2"></i><?php echo $edit ? 'Update' : 'Create'; ?> Category
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">All Categories</h5>
                    <span class="badge bg-primary"><?php echo count($categories); ?></span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Products</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $c): ?>
                                    <tr>
                                        <td><?php echo $c['category_id']; ?></td>
                                        <td><?php echo htmlspecialchars($c['category_name']); ?></td>
                                        <td class="small text-muted" style="max-width: 300px;"><?php echo htmlspecialchars($c['description']); ?></td>
                                        <td><span class="badge bg-info"><?php echo (int)$c['product_count']; ?></span></td>
                                        <td>
                                            <a class="btn btn-sm btn-outline-primary" href="manage_categories.php?action=edit&id=<?php echo $c['category_id']; ?>">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form method="POST" action="" class="d-inline" onsubmit="return confirm('Delete this category? Products in this category will also be affected.');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="category_id" value="<?php echo $c['category_id']; ?>">
                                                <button class="btn btn-sm btn-outline-danger" type="submit">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                            <a class="btn btn-sm btn-outline-secondary" href="../category.php?id=<?php echo $c['category_id']; ?>" target="_blank">
                                                <i class="fas fa-eye"></i>
                                            </a>
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

</body>
</html>
