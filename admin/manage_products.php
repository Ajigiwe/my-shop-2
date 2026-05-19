<?php
/**
 * Admin: Manage Products
 * - Admin-only CRUD for products
 * - Handles multiple image uploads and optional subcategory linkage
 * - Lists products with basic details and quick actions
 */
require_once '../includes/db.php';
session_start();
require_once '../includes/admin_guard.php';
require_once 'includes/product_images.php';

// Initialize ProductImages handler
$productImages = new ProductImages($pdo);

$page_title = 'Manage Products';
$errors = [];
$success = $_GET['success'] ?? '';

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
            $features = sanitizeInput($_POST['features'] ?? '');
            $price = (float)($_POST['price'] ?? 0);
            $original_price = isset($_POST['original_price']) && $_POST['original_price'] !== '' ? (float)$_POST['original_price'] : null;
            $stock_quantity = (int)($_POST['stock_quantity'] ?? 0);
            
            // Basic validation
            if ($category_id <= 0) $errors[] = 'Category is required';
            if (!$name) $errors[] = 'Product name is required';
            if ($price <= 0) $errors[] = 'Price must be greater than 0';
            if ($stock_quantity < 0) $errors[] = 'Stock cannot be negative';
            
            // Check if an image is uploaded for new products
            if ($action === 'create' && empty($_FILES['image']['name'])) {
                $errors[] = 'A product image is required';
            }

            if (empty($errors)) {
                try {
                    $pdo->beginTransaction();
                    
                    // Insert product
                    if ($subcategory_id) {
                        $stmt = $pdo->prepare('INSERT INTO products (category_id, subcategory_id, name, description, features, price, original_price, stock_quantity) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                        $stmt->execute([$category_id, $subcategory_id, $name, $description, $features, $price, $original_price, $stock_quantity]);
                    } else {
                        $stmt = $pdo->prepare('INSERT INTO products (category_id, name, description, features, price, original_price, stock_quantity) VALUES (?, ?, ?, ?, ?, ?, ?)');
                        $stmt->execute([$category_id, $name, $description, $features, $price, $original_price, $stock_quantity]);
                    }
                    
                    $product_id = $pdo->lastInsertId();
                    
                    // Handle multiple image uploads
                    if (!empty($_FILES['images']['name'][0])) {
                        $productImages->uploadImages($product_id, $_FILES['images']);
                    }
                    
                    $pdo->commit();
                    $success = 'Product created successfully';
                    
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $errors[] = 'Error creating product: ' . $e->getMessage();
                    error_log('Product creation error: ' . $e->getMessage());
                }
            }
        } elseif ($action === 'update') {
            // Update existing product
            $product_id = (int)($_POST['product_id'] ?? 0);
            $category_id = (int)($_POST['category_id'] ?? 0);
            $subcategory_id = isset($_POST['subcategory_id']) && $_POST['subcategory_id'] !== '' ? (int)$_POST['subcategory_id'] : null;
            $name = sanitizeInput($_POST['name'] ?? '');
            $description = sanitizeInput($_POST['description'] ?? '');
            $features = sanitizeInput($_POST['features'] ?? '');
            $price = (float)($_POST['price'] ?? 0);
            $original_price = isset($_POST['original_price']) && $_POST['original_price'] !== '' ? (float)$_POST['original_price'] : null;
            $stock_quantity = (int)($_POST['stock_quantity'] ?? 0);

            if ($product_id <= 0) $errors[] = 'Invalid product';
            if ($category_id <= 0) $errors[] = 'Category is required';
            if (!$name) $errors[] = 'Product name is required';
            if ($price <= 0) $errors[] = 'Price must be greater than 0';
            if ($stock_quantity < 0) $errors[] = 'Stock cannot be negative';

            if (empty($errors)) {
                try {
                    $pdo->beginTransaction();
                    
                    // Update product details
                    $stmt = $pdo->prepare('UPDATE products SET category_id = ?, subcategory_id = ?, name = ?, description = ?, features = ?, price = ?, original_price = ?, stock_quantity = ? WHERE product_id = ?');
                    $stmt->execute([$category_id, $subcategory_id, $name, $description, $features, $price, $original_price, $stock_quantity, $product_id]);
                    
                    // Handle multiple image uploads if new ones are selected
                    if (!empty($_FILES['images']['name'][0])) {
                        $productImages->uploadImages($product_id, $_FILES['images']);
                    }
                    
                    $pdo->commit();
                    $success = 'Product updated successfully';
                    
                    // Refresh the edit data
                    $stmt = $pdo->prepare('SELECT * FROM products WHERE product_id = ?');
                    $stmt->execute([$product_id]);
                    $edit = $stmt->fetch();
                    
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $errors[] = 'Error updating product: ' . $e->getMessage();
                    error_log('Product update error: ' . $e->getMessage());
                }
            }
        } elseif ($action === 'delete') {
            // Delete product
            $product_id = (int)($_POST['product_id'] ?? 0);
            if ($product_id <= 0) $errors[] = 'Invalid product';
            if (empty($errors)) {
                try {
                    $pdo->beginTransaction();
                    
                    // Get the product image before deletion
                    $stmt = $pdo->prepare('SELECT image FROM products WHERE product_id = ?');
                    $stmt->execute([$product_id]);
                    $image = $stmt->fetchColumn();
                    
                    // Delete the product
                    $stmt = $pdo->prepare('DELETE FROM products WHERE product_id = ?');
                    $stmt->execute([$product_id]);
                    
                    // Delete the image file if it exists
                    if (!empty($image)) {
                        $filePath = realpath(__DIR__ . '/../assets/images/') . DIRECTORY_SEPARATOR . $image;
                        if (file_exists($filePath)) {
                            @unlink($filePath);
                        }
                    }
                    
                    $pdo->commit();
                    $success = 'Product deleted successfully';
                    
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $errors[] = 'Error deleting product: ' . $e->getMessage();
                    error_log('Product deletion error: ' . $e->getMessage());
                }
            }
        } elseif ($action === 'delete_image') {
            $image_id = (int)($_POST['image_id'] ?? 0);
            $product_id = (int)($_POST['product_id'] ?? 0);
            if ($image_id > 0 && $product_id > 0) {
                if ($productImages->deleteImage($image_id, $product_id)) {
                    $success = 'Image deleted successfully';
                    header("Location: manage_products.php?action=edit&id=$product_id&success=" . urlencode($success));
                    exit();
                } else {
                    $errors[] = 'Failed to delete image';
                }
            }
        } elseif ($action === 'set_main_image') {
            $image_id = (int)($_POST['image_id'] ?? 0);
            $product_id = (int)($_POST['product_id'] ?? 0);
            if ($image_id > 0 && $product_id > 0) {
                if ($productImages->setPrimaryById($image_id, $product_id)) {
                    $success = 'Main image updated successfully';
                    header("Location: manage_products.php?action=edit&id=$product_id&success=" . urlencode($success));
                    exit();
                } else {
                    $errors[] = 'Failed to update main image';
                }
            }
        }
    }
} catch (PDOException $e) {
    error_log('Products CRUD error: ' . $e->getMessage());
    $errors[] = 'Database error occurred: ' . $e->getMessage();
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

<?php
$page_title = 'Product Inventory';
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
                <h5 class="admin-card-title mb-0">Product Inventory <span class="badge bg-light text-dark ms-2 rounded-pill"><?php echo count($products); ?></span></h5>
                <button type="button" class="btn-premium py-1 px-3 small" data-bs-toggle="modal" data-bs-target="#productModal">
                    <i class="fas fa-plus me-2"></i>Add Product
                </button>
            </div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Product Info</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $p): ?>
                        <tr>
                            <td style="width: 50px;">
                                <img src="../assets/images/<?php echo htmlspecialchars($p['image'] ?? 'placeholder.jpg'); ?>" width="36" height="36" class="rounded-2 shadow-sm object-fit-cover" alt="img">
                            </td>
                            <td>
                                <div class="fw-black text-[13px]"><?php echo htmlspecialchars($p['name']); ?></div>
                                <div class="small text-muted fw-bold uppercase tracking-widest text-[9px] mt-0.5">ID: #<?php echo $p['product_id']; ?></div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border rounded-pill px-2 py-1 small"><?php echo htmlspecialchars($p['category_name']); ?></span>
                            </td>
                            <td class="fw-black">
                                <?php echo formatCurrency($p['price']); ?>
                                <?php if (!empty($p['original_price'])): ?>
                                    <div class="text-muted text-decoration-line-through text-[11px] fw-normal"><?php echo formatCurrency($p['original_price']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge rounded-pill px-2 py-1 bg-<?php echo $p['stock_quantity']>10?'success':($p['stock_quantity']>0?'warning':'danger'); ?>-subtle text-<?php echo $p['stock_quantity']>10?'success':($p['stock_quantity']>0?'warning':'danger'); ?> fw-bold small">
                                    <?php echo (int)$p['stock_quantity']; ?> In Stock
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-1">
                                    <a class="btn-premium-outline px-2 py-1 text-decoration-none text-[12px]" href="manage_products.php?action=edit&id=<?php echo $p['product_id']; ?>">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" action="" class="d-inline" onsubmit="return confirmAction(event, 'Delete this product?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="product_id" value="<?php echo $p['product_id']; ?>">
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

<!-- Product Modal -->
<div class="modal fade" id="productModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header border-0 bg-dark text-white p-4">
                <h5 class="modal-title fw-bold">
                    <i class="fas <?php echo $edit ? 'fa-edit' : 'fa-box-open'; ?> me-2"></i>
                    <?php echo $edit ? 'Edit Product Details' : 'Add New Inventory Item'; ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form method="POST" action="" enctype="multipart/form-data">
                    <?php if ($edit): ?>
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="product_id" value="<?php echo $edit['product_id']; ?>">
                    <?php else: ?>
                        <input type="hidden" name="action" value="create">
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="stat-label small mb-1 uppercase tracking-wider fw-bold">Category</label>
                            <select class="form-select rounded-3 fw-bold" name="category_id" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $c): ?>
                                    <option value="<?php echo $c['category_id']; ?>" <?php echo ($edit && $edit['category_id']==$c['category_id'])?'selected':''; ?>>
                                        <?php echo htmlspecialchars($c['category_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="stat-label small mb-1 uppercase tracking-wider fw-bold">Product Name</label>
                            <input type="text" class="form-control rounded-3" name="name" value="<?php echo htmlspecialchars($edit['name'] ?? ''); ?>" required placeholder="Enter product title">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="stat-label small mb-1 uppercase tracking-wider fw-bold">Description</label>
                        <textarea class="form-control rounded-3" name="description" rows="3" placeholder="Detailed product description..."><?php echo htmlspecialchars($edit['description'] ?? ''); ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="stat-label small mb-1 uppercase tracking-wider fw-bold">Key Features (One per line)</label>
                        <textarea class="form-control rounded-3" name="features" rows="4" placeholder="List key features line by line..."><?php echo htmlspecialchars($edit['features'] ?? ''); ?></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="stat-label small mb-1 uppercase tracking-wider fw-bold">Sale Price (GH₵)</label>
                            <input type="number" step="0.01" min="0" class="form-control rounded-3 fw-black text-primary" name="price" value="<?php echo htmlspecialchars($edit['price'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="stat-label small mb-1 uppercase tracking-wider fw-bold">Old Price (Optional)</label>
                            <input type="number" step="0.01" min="0" class="form-control rounded-3" name="original_price" value="<?php echo htmlspecialchars($edit['original_price'] ?? ''); ?>" placeholder="Price before discount">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="stat-label small mb-1 uppercase tracking-wider fw-bold">Stock Quantity</label>
                            <input type="number" min="0" class="form-control rounded-3" name="stock_quantity" value="<?php echo htmlspecialchars($edit['stock_quantity'] ?? ''); ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="stat-label small mb-1 uppercase tracking-wider fw-bold">Product Visuals (Select Multiple)</label>
                        <input type="file" class="form-control rounded-3" name="images[]" accept="image/*" multiple <?php echo !$edit ? 'required' : ''; ?>>
                        <?php if ($edit): 
                            $currentImages = $productImages->getProductImages($edit['product_id']);
                            if (!empty($currentImages)): ?>
                            <div class="mt-3">
                                <label class="stat-label small mb-2 uppercase tracking-wider fw-bold d-block text-muted">Current Gallery</label>
                                <div class="d-flex flex-wrap gap-2">
                                    <?php foreach ($currentImages as $img): ?>
                                        <div class="position-relative group">
                                            <img src="../assets/images/<?php echo htmlspecialchars($img['image_path']); ?>" 
                                                 class="rounded-3 shadow-sm <?php echo $img['is_primary'] ? 'border-primary border-2' : ''; ?>" 
                                                 style="width: 80px; height: 80px; object-fit: cover;">
                                            <?php if ($img['is_primary']): ?>
                                                <span class="badge bg-primary position-absolute top-0 start-0 m-1 rounded-pill" style="font-size: 8px;">Main</span>
                                            <?php endif; ?>
                                            
                                            <!-- Delete Image Button -->
                                            <button type="button" 
                                                    onclick="deleteProductImage(<?php echo $img['image_id']; ?>, <?php echo $edit['product_id']; ?>)"
                                                    class="btn btn-danger btn-sm rounded-circle p-0 d-flex align-items-center justify-center shadow-sm position-absolute top-0 end-0 m-1" 
                                                    style="width: 22px; height: 22px; font-size: 12px; z-index: 20;"
                                                    title="Delete Image">
                                                <i class="fas fa-times"></i>
                                            </button>

                                            <?php if (!$img['is_primary']): ?>
                                                <!-- Set as Main Button -->
                                                <button type="button" 
                                                        onclick="setPrimaryImage(<?php echo $img['image_id']; ?>, <?php echo $edit['product_id']; ?>)"
                                                        class="btn btn-primary btn-sm rounded-pill p-0 px-2 shadow-sm position-absolute bottom-0 start-0 m-1 opacity-0 group-hover:opacity-100 transition-opacity" 
                                                        style="font-size: 8px; z-index: 20;">
                                                    Set Main
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; endif; ?>
                    </div>

                    <div class="mt-4">
                        <button class="btn-premium w-100 py-3 rounded-3 shadow-sm" type="submit">
                            <i class="fas fa-save me-2"></i><?php echo $edit ? 'Save Product' : 'Deploy to Catalog'; ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Hidden form for image deletion -->
<form id="deleteImageForm" method="POST" action="">
    <input type="hidden" name="action" value="delete_image">
    <input type="hidden" name="image_id" id="delete_image_id">
    <input type="hidden" name="product_id" id="delete_product_id">
</form>

<!-- Hidden form for setting main image -->
<form id="setMainImageForm" method="POST" action="">
    <input type="hidden" name="action" value="set_main_image">
    <input type="hidden" name="image_id" id="main_image_id">
    <input type="hidden" name="product_id" id="main_product_id">
</form>

<script>
function deleteProductImage(imageId, productId) {
    if (confirm('Delete this image?')) {
        document.getElementById('delete_image_id').value = imageId;
        document.getElementById('delete_product_id').value = productId;
        document.getElementById('deleteImageForm').submit();
    }
}

function setPrimaryImage(imageId, productId) {
    document.getElementById('main_image_id').value = imageId;
    document.getElementById('main_product_id').value = productId;
    document.getElementById('setMainImageForm').submit();
}

document.addEventListener('DOMContentLoaded', function() {
    <?php if ($edit || !empty($errors)): ?>
    var myModal = new bootstrap.Modal(document.getElementById('productModal'));
    myModal.show();
    <?php endif; ?>
});
</script>

<?php include 'includes/footer-new.php'; ?>
