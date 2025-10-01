<?php
/**
 * Admin: Manage Products
 * - Admin-only CRUD for products
 * - Handles optional image upload and optional subcategory linkage
 * - Lists products with basic details and quick actions
 */
require_once '../includes/db.php';
session_start();
require_once '../includes/admin_guard.php';

$page_title = 'Manage Products';
$errors = [];
$success = '';

// Fetch categories for select
$categories = [];
try {
    $stmt = $pdo->query('SELECT category_id, category_name FROM categories ORDER BY category_name');
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Fetch categories for products error: ' . $e->getMessage());
}

// Fetch subcategories for select (optional)
$subcategories = [];
try {
    $stmt = $pdo->query('SELECT subcategory_id, subcategory_name, category_id FROM subcategories ORDER BY subcategory_name');
    $subcategories = $stmt->fetchAll();
} catch (PDOException $e) {
    // Table might not exist if migration not applied; ignore
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

/**
 * Handle image upload for product cover
 * - Accepts jpg/jpeg/png/gif/webp
 * - Returns new filename on success, or $existing if no upload/invalid
 */
function handleUpload($fieldName, $existing = '') {
    $targetDir = realpath(__DIR__ . '/../assets/images');
    if (!is_dir($targetDir)) {
        @mkdir($targetDir, 0777, true);
    }
    if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
        return $existing; // keep existing if no new file
    }
    $fileTmp = $_FILES[$fieldName]['tmp_name'];
    $origName = basename($_FILES[$fieldName]['name']);
    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','gif','webp'];
    if (!in_array($ext, $allowed)) {
        return $existing; // ignore invalid types
    }
    $newName = 'prod_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = $targetDir . DIRECTORY_SEPARATOR . $newName;
    if (move_uploaded_file($fileTmp, $dest)) {
        return $newName;
    }
    return $existing;
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($action === 'create') {
            // Create product payload
            $category_id = (int)($_POST['category_id'] ?? 0);
            $subcategory_id = isset($_POST['subcategory_id']) && $_POST['subcategory_id'] !== '' ? (int)$_POST['subcategory_id'] : null;
            $name = sanitizeInput($_POST['name'] ?? '');
            $description = sanitizeInput($_POST['description'] ?? '');
            $price = (float)($_POST['price'] ?? 0);
            $stock_quantity = (int)($_POST['stock_quantity'] ?? 0);
            $image = handleUpload('image');

            if ($category_id <= 0) $errors[] = 'Category is required';
            if (!$name) $errors[] = 'Product name is required';
            if ($price <= 0) $errors[] = 'Price must be greater than 0';
            if ($stock_quantity < 0) $errors[] = 'Stock cannot be negative';

            if (empty($errors)) {
                // Insert with or without subcategory depending on provided value
                if ($subcategory_id) {
                    $stmt = $pdo->prepare('INSERT INTO products (category_id, subcategory_id, name, description, price, stock_quantity, image) VALUES (?, ?, ?, ?, ?, ?, ?)');
                    $stmt->execute([$category_id, $subcategory_id, $name, $description, $price, $stock_quantity, $image]);
                } else {
                    $stmt = $pdo->prepare('INSERT INTO products (category_id, name, description, price, stock_quantity, image) VALUES (?, ?, ?, ?, ?, ?)');
                    $stmt->execute([$category_id, $name, $description, $price, $stock_quantity, $image]);
                }
                $success = 'Product created successfully';
            }
        } elseif ($action === 'update') {
            // Update existing product
            $product_id = (int)($_POST['product_id'] ?? 0);
            $category_id = (int)($_POST['category_id'] ?? 0);
            $subcategory_id = isset($_POST['subcategory_id']) && $_POST['subcategory_id'] !== '' ? (int)$_POST['subcategory_id'] : null;
            $name = sanitizeInput($_POST['name'] ?? '');
            $description = sanitizeInput($_POST['description'] ?? '');
            $price = (float)($_POST['price'] ?? 0);
            $stock_quantity = (int)($_POST['stock_quantity'] ?? 0);
            $existing_image = sanitizeInput($_POST['existing_image'] ?? '');
            $image = handleUpload('image', $existing_image);

            if ($product_id <= 0) $errors[] = 'Invalid product';
            if ($category_id <= 0) $errors[] = 'Category is required';
            if (!$name) $errors[] = 'Product name is required';
            if ($price <= 0) $errors[] = 'Price must be greater than 0';
            if ($stock_quantity < 0) $errors[] = 'Stock cannot be negative';

            if (empty($errors)) {
                $stmt = $pdo->prepare('UPDATE products SET category_id = ?, subcategory_id = ?, name = ?, description = ?, price = ?, stock_quantity = ?, image = ? WHERE product_id = ?');
                $stmt->execute([$category_id, $subcategory_id, $name, $description, $price, $stock_quantity, $image, $product_id]);
                $success = 'Product updated successfully';
            }
        } elseif ($action === 'delete') {
            // Delete product
            $product_id = (int)($_POST['product_id'] ?? 0);
            if ($product_id <= 0) $errors[] = 'Invalid product';
            if (empty($errors)) {
                $stmt = $pdo->prepare('DELETE FROM products WHERE product_id = ?');
                $stmt->execute([$product_id]);
                $success = 'Product deleted';
            }
        }
    }
} catch (PDOException $e) {
    error_log('Products CRUD error: ' . $e->getMessage());
    $errors[] = 'Database error occurred';
}

// Editing product
$edit = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare('SELECT * FROM products WHERE product_id = ?');
        $stmt->execute([$id]);
        $edit = $stmt->fetch();
    } catch (PDOException $e) {
        error_log('Fetch product error: ' . $e->getMessage());
    }
}

// Fetch products
$products = [];
try {
    // List products with their category for the admin table
    $stmt = $pdo->query('SELECT p.*, c.category_name FROM products p JOIN categories c ON c.category_id = p.category_id ORDER BY p.created_at DESC');
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Fetch products error: ' . $e->getMessage());
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
            <h2 class="mb-0">Manage Products</h2>
        </div>
        <div>
            <!-- Spacer for centering -->
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><?php echo $edit ? 'Edit Product' : 'Add New Product'; ?></h5>
                </div>
                <div class="card-body">
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
                        <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?></div>
                    <?php endif; ?>

                    <form method="POST" action="" enctype="multipart/form-data">
                        <?php if ($edit): ?>
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="product_id" value="<?php echo $edit['product_id']; ?>">
                            <input type="hidden" name="existing_image" value="<?php echo htmlspecialchars($edit['image'] ?? ''); ?>">
                        <?php else: ?>
                            <input type="hidden" name="action" value="create">
                        <?php endif; ?>

                        <div class="mb-3">
                            <label class="form-label">Category</label>
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
                            <label class="form-label">Product Name</label>
                            <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($edit['name'] ?? ''); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="4"><?php echo htmlspecialchars($edit['description'] ?? ''); ?></textarea>
                        </div>
                        <div class="row g-2">
                            <div class="col-6 mb-3">
                                <label class="form-label">Price</label>
                                <input type="number" step="0.01" min="0" class="form-control" name="price" value="<?php echo htmlspecialchars($edit['price'] ?? ''); ?>" required>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">Stock Quantity</label>
                                <input type="number" min="0" class="form-control" name="stock_quantity" value="<?php echo htmlspecialchars($edit['stock_quantity'] ?? ''); ?>" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Image</label>
                            <input type="file" class="form-control" name="image" accept="image/*">
                            <?php if ($edit && !empty($edit['image'])): ?>
                                <div class="mt-2">
                                    <img src="../assets/images/<?php echo htmlspecialchars($edit['image']); ?>" alt="Current image" class="rounded" width="120">
                                </div>
                            <?php endif; ?>
                        </div>
                        <button class="btn btn-primary w-100" type="submit"><i class="fas fa-save me-2"></i><?php echo $edit ? 'Update' : 'Create'; ?> Product</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">All Products</h5>
                    <span class="badge bg-primary"><?php echo count($products); ?></span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Image</th>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $p): ?>
                                    <tr>
                                        <td><?php echo $p['product_id']; ?></td>
                                        <td>
                                            <img src="../assets/images/<?php echo htmlspecialchars($p['image'] ?? 'placeholder.jpg'); ?>" width="50" height="50" class="rounded" alt="img">
                                        </td>
                                        <td><?php echo htmlspecialchars($p['name']); ?></td>
                                        <td><span class="badge bg-info"><?php echo htmlspecialchars($p['category_name']); ?></span></td>
                                        <td><?php echo formatCurrency($p['price']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $p['stock_quantity']>10?'success':($p['stock_quantity']>0?'warning':'danger'); ?>">
                                                <?php echo (int)$p['stock_quantity']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a class="btn btn-sm btn-outline-primary" href="manage_products.php?action=edit&id=<?php echo $p['product_id']; ?>"><i class="fas fa-edit"></i></a>
                                            <form method="POST" action="" class="d-inline" onsubmit="return confirm('Delete this product?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="product_id" value="<?php echo $p['product_id']; ?>">
                                                <button class="btn btn-sm btn-outline-danger" type="submit"><i class="fas fa-trash"></i></button>
                                            </form>
                                            <a class="btn btn-sm btn-outline-secondary" href="../product.php?id=<?php echo $p['product_id']; ?>" target="_blank"><i class="fas fa-eye"></i></a>
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
