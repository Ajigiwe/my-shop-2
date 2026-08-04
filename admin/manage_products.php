<?php
/**
 * Admin: Manage Products
 * - Admin-only product list with search, filters, sort, pagination
 * - Bulk actions (delete, status, featured, category move)
 * - Links to full-page editor (product_editor.php)
 */
require_once '../includes/db.php';
session_start();
require_once '../includes/admin_guard.php';
require_once 'includes/product_images.php';

$productImages = new ProductImages($pdo);
$page_title = 'Manage Products';
$errors = [];

// ------------------------------------------------------------------
// Fetch categories for filters / bulk category move
// ------------------------------------------------------------------
$categories = [];
try {
    $stmt = $pdo->query('SELECT category_id, category_name FROM categories ORDER BY category_name');
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Fetch categories for products error: ' . $e->getMessage());
}

// ------------------------------------------------------------------
// Handle POST actions (single + bulk)
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission. Please refresh and try again.';
    } else {

    if ($action === 'delete') {
        $product_id = (int)($_POST['product_id'] ?? 0);
        if ($product_id <= 0) {
            $errors[] = 'Invalid product';
        } else {
            try {
                $pdo->beginTransaction();
                $images = $productImages->getProductImages($product_id);
                $stmt = $pdo->prepare('DELETE FROM products WHERE product_id = ?');
                $stmt->execute([$product_id]);
                foreach ($images as $img) {
                    $filePath = realpath(__DIR__ . '/../assets/images/') . DIRECTORY_SEPARATOR . basename($img['image_path']);
                    if (file_exists($filePath)) @unlink($filePath);
                }
                $pdo->commit();
                header('Location: manage_products.php?success=' . urlencode('Product deleted successfully'));
                exit();
            } catch (Exception $e) {
                $pdo->rollBack();
                error_log('Product deletion error: ' . $e->getMessage());
                $errors[] = 'Error deleting product: ' . $e->getMessage();
            }
        }
    }

    if ($action === 'bulk_delete') {
        $ids = array_filter(array_map('intval', $_POST['product_ids'] ?? []));
        if (!empty($ids)) {
            try {
                $pdo->beginTransaction();
                foreach ($ids as $id) {
                    $images = $productImages->getProductImages($id);
                    foreach ($images as $img) {
                        $filePath = realpath(__DIR__ . '/../assets/images/') . DIRECTORY_SEPARATOR . basename($img['image_path']);
                        if (file_exists($filePath)) @unlink($filePath);
                    }
                }
                $in = implode(',', array_fill(0, count($ids), '?'));
                $stmt = $pdo->prepare("DELETE FROM products WHERE product_id IN ($in)");
                $stmt->execute($ids);
                $pdo->commit();
                header('Location: manage_products.php?success=' . urlencode(count($ids) . ' product(s) deleted successfully'));
                exit();
            } catch (Exception $e) {
                $pdo->rollBack();
                error_log('Bulk product deletion error: ' . $e->getMessage());
                $errors[] = 'Error deleting products: ' . $e->getMessage();
            }
        }
    }

    if ($action === 'bulk_status') {
        $ids = array_filter(array_map('intval', $_POST['product_ids'] ?? []));
        $status = sanitizeInput($_POST['bulk_status_value'] ?? '');
        if (!empty($ids) && in_array($status, ['draft', 'published'])) {
            $in = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("UPDATE products SET status = ? WHERE product_id IN ($in)");
            $stmt->execute(array_merge([$status], $ids));
            header('Location: manage_products.php?success=' . urlencode(count($ids) . ' product(s) set to ' . $status));
            exit();
        }
    }

    if ($action === 'bulk_featured') {
        $ids = array_filter(array_map('intval', $_POST['product_ids'] ?? []));
        $featured = sanitizeInput($_POST['bulk_featured_value'] ?? '');
        if (!empty($ids) && in_array($featured, ['yes', 'no'])) {
            $val = $featured === 'yes' ? 1 : 0;
            $in = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("UPDATE products SET is_featured = ? WHERE product_id IN ($in)");
            $stmt->execute(array_merge([$val], $ids));
            header('Location: manage_products.php?success=' . urlencode(count($ids) . ' product(s) updated'));
            exit();
        }
    }

    if ($action === 'bulk_category') {
        $ids = array_filter(array_map('intval', $_POST['product_ids'] ?? []));
        $category_id = (int)($_POST['bulk_category_value'] ?? 0);
        if (!empty($ids) && $category_id > 0) {
            $in = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("UPDATE products SET category_id = ? WHERE product_id IN ($in)");
            $stmt->execute(array_merge([$category_id], $ids));
            header('Location: manage_products.php?success=' . urlencode(count($ids) . ' product(s) moved to new category'));
            exit();
        }
    }

    if ($action === 'bulk_price_change') {
        $ids = array_filter(array_map('intval', $_POST['product_ids'] ?? []));
        $price_type = sanitizeInput($_POST['price_change_type'] ?? 'fixed');
        $price_value = (float)($_POST['price_change_value'] ?? 0);
        $round_to_99 = isset($_POST['round_to_99']) ? 1 : 0;
        if (!empty($ids) && $price_value >= 0) {
            try {
                $pdo->beginTransaction();
                foreach ($ids as $id) {
                    $stmt = $pdo->prepare("SELECT price, original_price FROM products WHERE product_id = ?");
                    $stmt->execute([$id]);
                    $product = $stmt->fetch();
                    if (!$product) continue;

                    $new_price = $product['price'];
                    $new_original = $product['original_price'];

                    switch ($price_type) {
                        case 'fixed':
                            $new_price = $price_value;
                            break;
                        case 'increase_pct':
                            $new_price = round($new_price * (1 + $price_value / 100), 2);
                            break;
                        case 'decrease_pct':
                            $new_price = round($new_price * (1 - $price_value / 100), 2);
                            break;
                    }

                    if ($round_to_99 && $new_price > 0) {
                        $new_price = floor($new_price) + 0.99;
                    }

                    $stmt = $pdo->prepare("UPDATE products SET price = ?, original_price = ? WHERE product_id = ?");
                    $stmt->execute([$new_price, $new_original, $id]);
                }
                $pdo->commit();
                header('Location: manage_products.php?success=' . urlencode(count($ids) . ' product(s) price updated'));
                exit();
            } catch (Exception $e) {
                $pdo->rollBack();
                error_log('Bulk price change error: ' . $e->getMessage());
                $errors[] = 'Error updating prices: ' . $e->getMessage();
            }
        }
    }

    if ($action === 'bulk_duplicate') {
        $ids = array_filter(array_map('intval', $_POST['product_ids'] ?? []));
        if (!empty($ids)) {
            try {
                $pdo->beginTransaction();
                $duplicated = 0;
                foreach ($ids as $id) {
                    $stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ?");
                    $stmt->execute([$id]);
                    $product = $stmt->fetch();
                    if (!$product) continue;

                    // Generate new SKU
                    $new_sku = $product['sku'] ? $product['sku'] . '-COPY-' . time() . '-' . rand(100, 999) : null;
                    
                    // Insert duplicate with draft status
                    $stmt = $pdo->prepare("
                        INSERT INTO products (category_id, subcategory_id, name, description, features, price, original_price, 
                                             stock_quantity, image, has_multiple_images, main_image_id, 
                                             sku, status, is_featured, low_stock_threshold, meta_title, meta_description, slug)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft', 0, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $product['category_id'],
                        $product['subcategory_id'],
                        $product['name'] . ' (Copy)',
                        $product['description'],
                        $product['features'],
                        $product['price'],
                        $product['original_price'],
                        $product['stock_quantity'],
                        $product['image'],
                        $product['has_multiple_images'],
                        $product['main_image_id'],
                        $new_sku,
                        $product['low_stock_threshold'],
                        $product['meta_title'],
                        $product['meta_description'],
                        $product['slug'] ? $product['slug'] . '-copy-' . time() : null
                    ]);
                    $new_id = $pdo->lastInsertId();

                    // Duplicate images
                    $images = $productImages->getProductImages($id);
                    foreach ($images as $img) {
                        $stmt = $pdo->prepare("
                            INSERT INTO product_images (product_id, image_path, is_primary, display_order)
                            VALUES (?, ?, ?, ?)
                        ");
                        $stmt->execute([$new_id, $img['image_path'], $img['is_primary'], $img['display_order']]);
                    }

                    $duplicated++;
                }
                $pdo->commit();
                header('Location: manage_products.php?success=' . urlencode($duplicated . ' product(s) duplicated as draft'));
                exit();
            } catch (Exception $e) {
                $pdo->rollBack();
                error_log('Bulk duplicate error: ' . $e->getMessage());
                $errors[] = 'Error duplicating products: ' . $e->getMessage();
            }
        }
    }

    if ($action === 'bulk_restock') {
        $ids = array_filter(array_map('intval', $_POST['product_ids'] ?? []));
        $quantity = (int)($_POST['restock_quantity'] ?? 0);
        $notes = sanitizeInput($_POST['restock_notes'] ?? '');
        if (!empty($ids) && $quantity > 0) {
            try {
                $pdo->beginTransaction();
                foreach ($ids as $id) {
                    $stmt = $pdo->prepare("SELECT stock_quantity FROM products WHERE product_id = ?");
                    $stmt->execute([$id]);
                    $product = $stmt->fetch();
                    if (!$product) continue;
                    $qty_before = $product['stock_quantity'];
                    $qty_after = $qty_before + $quantity;
                    $stmt = $pdo->prepare("UPDATE products SET stock_quantity = ? WHERE product_id = ?");
                    $stmt->execute([$qty_after, $id]);
                    $stmt = $pdo->prepare("INSERT INTO inventory_movements (product_id, variation_id, type, quantity, quantity_before, quantity_after, notes) VALUES (?, NULL, 'receive', ?, ?, ?, ?)");
                    $stmt->execute([$id, $quantity, $qty_before, $qty_after, $notes]);
                }
                $pdo->commit();
                header('Location: manage_products.php?success=' . urlencode(count($ids) . ' product(s) restocked (+' . $quantity . ')'));
                exit();
            } catch (Exception $e) {
                $pdo->rollBack();
                error_log('Bulk restock error: ' . $e->getMessage());
                $errors[] = 'Error restocking products: ' . $e->getMessage();
            }
        }
    }

    if ($action === 'bulk_alert') {
        $ids = array_filter(array_map('intval', $_POST['product_ids'] ?? []));
        if (!empty($ids)) {
            try {
                $pdo->beginTransaction();
                $sent = 0;
                foreach ($ids as $id) {
                    $stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ? AND alert_enabled = 1");
                    $stmt->execute([$id]);
                    $product = $stmt->fetch();
                    if (!$product) continue;

                    $threshold = (int)($product['low_stock_threshold'] ?? 5);
                    $stock = (int)$product['stock_quantity'];
                    if ($stock > $threshold) continue;

                    // Check if already notified recently
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM stock_alert_log WHERE product_id = ? AND notified = 1 AND notified_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)");
                    $stmt->execute([$id]);
                    if ((int)$stmt->fetchColumn() > 0) continue;

                    // Log the alert
                    $stmt = $pdo->prepare("INSERT INTO stock_alert_log (product_id, threshold, current_stock, notified) VALUES (?, ?, ?, 1)");
                    $stmt->execute([$id, $threshold, $stock]);

                    // Send email
                    $alertEmail = $product['alert_email'] ?? 'aso@admin.gh';
                    $subject = "[Low Stock Alert] {$product['name']} — {$stock} remaining (threshold: {$threshold})";
                    $message = "Hi,\n\nThe product \"{$product['name']}\" (SKU: {$product['sku']}) has dropped to {$stock} units, which is at or below the low-stock threshold of {$threshold}.\n\nPlease restock soon.\n\n— ASO Online Market Admin";
                    $headers = "From: noreply@" . str_replace(['http://', 'https://'], '', SITE_URL) . "\r\n";
                    $headers .= "Reply-To: aso@admin.gh\r\n";
                    @mail($alertEmail, $subject, $message, $headers);
                    $sent++;
                }
                $pdo->commit();
                header('Location: manage_products.php?success=' . urlencode(count($ids) . ' product(s) checked, ' . $sent . ' alert(s) sent'));
                exit();
            } catch (Exception $e) {
                $pdo->rollBack();
                error_log('Bulk alert error: ' . $e->getMessage());
                $errors[] = 'Error sending alerts: ' . $e->getMessage();
            }
        }
    }

    }
}

// ------------------------------------------------------------------
// Read filters (GET)
// ------------------------------------------------------------------
$success = $_GET['success'] ?? '';
$q = sanitizeInput($_GET['q'] ?? '');
$category_filter = (int)($_GET['category'] ?? 0);
$status_filter = sanitizeInput($_GET['status'] ?? '');
$stock_filter = sanitizeInput($_GET['stock'] ?? '');
$featured_filter = sanitizeInput($_GET['featured'] ?? '');
$sort = sanitizeInput($_GET['sort'] ?? 'newest');
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = (int)($_GET['per_page'] ?? 20);
if (!in_array($per_page, [10, 20, 50, 100])) $per_page = 20;
$offset = ($page - 1) * $per_page;

// WHERE clause
$where = ['1=1'];
$params = [];

if ($q !== '') {
    $where[] = '(p.name LIKE ? OR p.sku LIKE ? OR p.product_id = ?)';
    $params[] = "%$q%";
    $params[] = "%$q%";
    $params[] = (int)$q;
}
if ($category_filter > 0) {
    $where[] = 'p.category_id = ?';
    $params[] = $category_filter;
}
if ($status_filter === 'published' || $status_filter === 'draft') {
    $where[] = 'p.status = ?';
    $params[] = $status_filter;
}
if ($stock_filter === 'instock') {
    $where[] = 'p.stock_quantity > 0';
} elseif ($stock_filter === 'outofstock') {
    $where[] = 'p.stock_quantity <= 0';
} elseif ($stock_filter === 'lowstock') {
    $where[] = 'p.stock_quantity > 0 AND p.stock_quantity <= COALESCE(p.low_stock_threshold, 5)';
}
if ($featured_filter === 'yes') {
    $where[] = 'p.is_featured = 1';
} elseif ($featured_filter === 'no') {
    $where[] = 'p.is_featured = 0';
}

$where_sql = 'WHERE ' . implode(' AND ', $where);

// ORDER BY
$order_sql = 'p.created_at DESC';
switch ($sort) {
    case 'price_asc':  $order_sql = 'p.price ASC'; break;
    case 'price_desc': $order_sql = 'p.price DESC'; break;
    case 'name_asc':   $order_sql = 'p.name ASC'; break;
    case 'name_desc':  $order_sql = 'p.name DESC'; break;
    case 'stock_asc':  $order_sql = 'p.stock_quantity ASC'; break;
    case 'stock_desc': $order_sql = 'p.stock_quantity DESC'; break;
}

// Total count
$total_products = 0;
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM products p $where_sql");
    $stmt->execute($params);
    $total_products = (int)$stmt->fetchColumn();
} catch (PDOException $e) {
    error_log('Count products error: ' . $e->getMessage());
}
$total_pages = max(1, (int)ceil($total_products / $per_page));
if ($page > $total_pages) $page = $total_pages;

// Fetch products
$products = [];
try {
    $stmt = $pdo->prepare("SELECT p.*, c.category_name, 
                            (SELECT COUNT(*) FROM product_variations WHERE product_id = p.product_id) AS variation_count,
                            (SELECT price FROM product_variations WHERE product_id = p.product_id AND is_default = 1 LIMIT 1) AS variation_price,
                            (SELECT sku FROM product_variations WHERE product_id = p.product_id AND is_default = 1 LIMIT 1) AS variation_sku
                            FROM products p 
                            JOIN categories c ON c.category_id = p.category_id 
                            $where_sql 
                            ORDER BY $order_sql 
                            LIMIT $per_page OFFSET $offset");
    $stmt->execute($params);
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Fetch products error: ' . $e->getMessage());
}

// Helper to rebuild the URL while preserving filters
function listUrl($overrides = []) {
    $params = $_GET;
    foreach ($overrides as $k => $v) {
        if ($v === '' || $v === null) unset($params[$k]);
        else $params[$k] = $v;
    }
    return '?' . http_build_query($params);
}

function lowStockThreshold($p) {
    $threshold = $p['low_stock_threshold'] ?? null;
    return $threshold !== null && $threshold !== '' ? (int)$threshold : 5;
}

include 'includes/avazonia_header.php';
?>

<div class="admin-header">
    <h1>Product Inventory</h1>
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
        <div class="panel-title">Product Inventory <span style="opacity: 0.4;">(<?php echo $total_products; ?>)</span></div>
        <div style="display: flex; gap: 12px; flex-wrap: wrap;">
            <a href="csv_export.php<?php echo $q !== '' || $category_filter > 0 || $status_filter || $stock_filter || $featured_filter ? '?' . http_build_query(array_filter(['q'=>$q,'category'=>$category_filter?:null,'status'=>$status_filter?:null,'stock'=>$stock_filter?:null,'featured'=>$featured_filter?:null])) : ''; ?>" class="btn-ink" style="height: 44px; background: transparent; color: var(--ink); border: 1px solid var(--ink);">Export</a>
            <a href="csv_import.php" class="btn-ink" style="height: 44px; background: transparent; color: var(--ink); border: 1px solid var(--ink);">Import</a>
            <a href="product_editor.php" class="btn-red">Add Product</a>
        </div>
    </div>
    <div style="padding: 24px; border-bottom: 1px solid var(--light-gray);">

            <!-- Filters Toolbar -->
            <form method="GET" action="" class="filter-bar">
                <div class="filter-group">
                    <span class="flabel">Search</span>
                    <input type="text" name="q" placeholder="Search name, SKU, or ID..." value="<?php echo htmlspecialchars($q); ?>" style="width: 240px;">
                </div>
                <div class="filter-group">
                    <span class="flabel">Category</span>
                    <select name="category">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?php echo $c['category_id']; ?>" <?php echo $category_filter == $c['category_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['category_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <span class="flabel">Status</span>
                    <select name="status">
                        <option value="">All Status</option>
                        <option value="published" <?php echo $status_filter === 'published' ? 'selected' : ''; ?>>Published</option>
                        <option value="draft" <?php echo $status_filter === 'draft' ? 'selected' : ''; ?>>Draft</option>
                    </select>
                </div>
                <div class="filter-group">
                    <span class="flabel">Stock</span>
                    <select name="stock">
                        <option value="">All Stock</option>
                        <option value="instock" <?php echo $stock_filter === 'instock' ? 'selected' : ''; ?>>In Stock</option>
                        <option value="lowstock" <?php echo $stock_filter === 'lowstock' ? 'selected' : ''; ?>>Low Stock</option>
                        <option value="outofstock" <?php echo $stock_filter === 'outofstock' ? 'selected' : ''; ?>>Out of Stock</option>
                    </select>
                </div>
                <div class="filter-group">
                    <span class="flabel">Featured</span>
                    <select name="featured">
                        <option value="">Featured: Any</option>
                        <option value="yes" <?php echo $featured_filter === 'yes' ? 'selected' : ''; ?>>Featured Only</option>
                        <option value="no" <?php echo $featured_filter === 'no' ? 'selected' : ''; ?>>Not Featured</option>
                    </select>
                </div>
                <div class="filter-group">
                    <span class="flabel">Sort</span>
                    <select name="sort">
                        <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                        <option value="price_asc" <?php echo $sort === 'price_asc' ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="price_desc" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>Price: High to Low</option>
                        <option value="name_asc" <?php echo $sort === 'name_asc' ? 'selected' : ''; ?>>Name: A to Z</option>
                        <option value="name_desc" <?php echo $sort === 'name_desc' ? 'selected' : ''; ?>>Name: Z to A</option>
                        <option value="stock_asc" <?php echo $sort === 'stock_asc' ? 'selected' : ''; ?>>Stock: Low to High</option>
                        <option value="stock_desc" <?php echo $sort === 'stock_desc' ? 'selected' : ''; ?>>Stock: High to Low</option>
                    </select>
                </div>
                <div style="display: flex; gap: 8px;">
                    <button class="btn-ink" style="height: 44px;" type="submit">Apply</button>
                    <a href="manage_products.php" class="btn-ink" style="height: 44px; background: transparent; color: var(--ink); border: 1px solid var(--ink);">Reset</a>
                </div>
            </form>
    </div>

            <!-- Bulk Actions Bar -->
            <form method="POST" action="" id="bulkForm">
                <?php echo csrfField(); ?>
                <div id="bulkBar" class="d-none align-items-center gap-2" style="display: none; align-items: center; gap: 12px; padding: 16px 24px; border-bottom: 1px solid var(--light-gray); background: var(--off);">
                    <span style="font-weight: 800; font-size: 12px; text-transform: uppercase;"><span id="bulkCount">0</span> selected</span>
                    <select name="bulk_action" id="bulkActionSelect" class="field-input" style="width: auto; height: 40px; padding: 0 12px;">
                        <option value="bulk_status">Set Status</option>
                        <option value="bulk_featured">Set Featured</option>
                        <option value="bulk_category">Move to Category</option>
                        <option value="bulk_price_change">Change Price</option>
                        <option value="bulk_duplicate">Duplicate</option>
                        <option value="bulk_restock">Restock</option>
                        <option value="bulk_alert">Send Low-Stock Alert</option>
                        <option value="bulk_delete">Delete</option>
                    </select>
                    <div id="bulkStatusWrap" class="d-none">
                        <select name="bulk_status_value" class="field-input" style="width: auto; height: 40px; padding: 0 12px;">
                            <option value="published">Published</option>
                            <option value="draft">Draft</option>
                        </select>
                    </div>
                    <div id="bulkFeaturedWrap" class="d-none">
                        <select name="bulk_featured_value" class="field-input" style="width: auto; height: 40px; padding: 0 12px;">
                            <option value="yes">Featured</option>
                            <option value="no">Not Featured</option>
                        </select>
                    </div>
                    <div id="bulkCategoryWrap" class="d-none">
                        <select name="bulk_category_value" class="field-input" style="width: auto; height: 40px; padding: 0 12px;">
                            <option value="">Select category...</option>
                            <?php foreach ($categories as $c): ?>
                                <option value="<?php echo $c['category_id']; ?>"><?php echo htmlspecialchars($c['category_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div id="bulkPriceChangeWrap" class="d-none">
                        <button type="button" class="btn-ink" style="height: 40px; padding: 0 16px;" onclick="openModal('priceChangeModal')">Configure</button>
                    </div>
                    <div id="bulkRestockWrap" class="d-none">
                        <button type="button" class="btn-ink" style="height: 40px; padding: 0 16px;" onclick="openModal('restockModal')">Configure</button>
                    </div>
                    <div id="bulkAlertWrap" class="d-none">
                        <button type="button" class="btn-ink" style="height: 40px; padding: 0 16px;" onclick="openModal('alertModal')">Configure</button>
                    </div>
                    <input type="hidden" name="action" id="bulkActionName">
                    <button class="btn-red" style="height: 40px; padding: 0 16px;" type="submit" onclick="return submitBulkAction(event)">Apply</button>
                </div>
            </form>

            <!-- Price Change Modal -->
            <div class="modal-overlay" id="priceChangeModal">
                <div class="modal-content">
                    <button type="button" class="modal-close" onclick="closeModal('priceChangeModal')">×</button>
                    <div class="modal-title">Price Change Options</div>
                    <form id="priceChangeConfigForm">
                        <div class="field-group">
                            <label class="field-label">Change Type</label>
                            <select name="price_change_type" id="priceChangeType" class="field-input">
                                <option value="fixed">Set Fixed Price</option>
                                <option value="increase_pct">Increase by %</option>
                                <option value="decrease_pct">Decrease by %</option>
                            </select>
                        </div>
                        <div class="field-group">
                            <label class="field-label">Value</label>
                            <div style="display: flex; gap: 8px; align-items: center;">
                                <input type="number" step="0.01" min="0" name="price_change_value" id="priceChangeValue" class="field-input" placeholder="e.g., 29.99 or 10" required>
                                <span id="priceChangeUnit" style="font-family: var(--f-mono); font-size: 12px;">GH₵</span>
                            </div>
                        </div>
                        <div class="field-group" style="display: flex; align-items: center; gap: 8px;">
                            <input type="checkbox" name="round_to_99" id="roundTo99" value="1" style="accent-color: var(--red); width: 16px; height: 16px;">
                            <label class="field-label" for="roundTo99" style="margin: 0;">Round to .99 (e.g., 29.99)</label>
                        </div>
                        <div class="modal-btn-row">
                            <button type="button" class="btn-ink" style="flex: 1; justify-content: center;" onclick="closeModal('priceChangeModal')">Cancel</button>
                            <button type="submit" class="btn-red" style="flex: 1; justify-content: center;">Apply Settings</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Restock Modal -->
            <div class="modal-overlay" id="restockModal">
                <div class="modal-content">
                    <button type="button" class="modal-close" onclick="closeModal('restockModal')">×</button>
                    <div class="modal-title">Restock Options</div>
                    <form id="restockConfigForm">
                        <div class="field-group">
                            <label class="field-label">Quantity to Add</label>
                            <input type="number" min="1" name="restock_quantity" id="restockQuantity" class="field-input" placeholder="e.g., 50" required>
                        </div>
                        <div class="field-group">
                            <label class="field-label">Notes</label>
                            <input type="text" name="restock_notes" id="restockNotes" class="field-input" placeholder="e.g., PO #1234, supplier name">
                        </div>
                        <div class="modal-btn-row">
                            <button type="button" class="btn-ink" style="flex: 1; justify-content: center;" onclick="closeModal('restockModal')">Cancel</button>
                            <button type="submit" class="btn-red" style="flex: 1; justify-content: center;">Apply Settings</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Alert Modal -->
            <div class="modal-overlay" id="alertModal">
                <div class="modal-content">
                    <button type="button" class="modal-close" onclick="closeModal('alertModal')">×</button>
                    <div class="modal-title">Low-Stock Alert Options</div>
                    <p class="modal-desc">This will check selected products and send low-stock alert emails to the configured alert recipients (or the admin email if none is set).</p>
                    <p class="modal-desc">Only products that are below their threshold and haven't been notified in the last 24 hours will receive alerts.</p>
                    <div class="modal-btn-row">
                        <button type="button" class="btn-ink" style="flex: 1; justify-content: center;" onclick="closeModal('alertModal')">Cancel</button>
                        <button type="button" class="btn-red" style="flex: 1; justify-content: center;" onclick="document.getElementById('bulkActionName').value='bulk_alert'; document.getElementById('bulkForm').submit();">Send Alerts</button>
                    </div>
                </div>
            </div>

            <!-- Quick Edit Modal -->
            <div class="modal-overlay" id="quickEditModal">
                <div class="modal-content wide">
                    <button type="button" class="modal-close" onclick="closeModal('quickEditModal')">×</button>
                    <div class="modal-title">Quick Edit Product</div>
                    <form id="quickEditForm">
                        <input type="hidden" name="product_id" id="quickEditProductId">
                        <div id="quickEditAlert" class="alert-box alert-error d-none" style="margin-bottom: 16px;"></div>
                        <div class="field-group">
                            <label class="field-label">Product Name</label>
                            <input type="text" id="quickEditProductName" class="field-input" readonly style="background: var(--off);">
                        </div>
                        <div class="field-grid" style="margin-bottom: 24px;">
                            <div class="field-group" style="margin-bottom: 0;">
                                <label class="field-label">Price (GH₵)</label>
                                <input type="number" step="0.01" min="0" name="price" id="quickEditPrice" class="field-input" required>
                            </div>
                            <div class="field-group" style="margin-bottom: 0;">
                                <label class="field-label">Original Price (GH₵)</label>
                                <input type="number" step="0.01" min="0" name="original_price" id="quickEditOriginalPrice" class="field-input" placeholder="Optional">
                            </div>
                        </div>
                        <div class="field-grid" style="margin-bottom: 24px;">
                            <div class="field-group" style="margin-bottom: 0;">
                                <label class="field-label">Stock Quantity</label>
                                <input type="number" min="0" name="stock_quantity" id="quickEditStock" class="field-input" required>
                            </div>
                            <div class="field-group" style="margin-bottom: 0;">
                                <label class="field-label">Status</label>
                                <select name="status" id="quickEditStatus" class="field-input">
                                    <option value="published">Published</option>
                                    <option value="draft">Draft</option>
                                </select>
                            </div>
                        </div>
                        <div class="field-group">
                            <label class="field-label">SKU</label>
                            <input type="text" name="sku" id="quickEditSku" class="field-input" placeholder="e.g. SKU-1001">
                        </div>
                        <div class="modal-btn-row">
                            <button type="button" class="btn-ink" style="flex: 1; justify-content: center;" onclick="closeModal('quickEditModal')">Cancel</button>
                            <button type="submit" class="btn-red" style="flex: 1; justify-content: center;" id="quickEditSaveBtn">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>



                <div class="table-container" style="border: none; margin-bottom: 0; border-radius: 0;">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th style="width: 30px;"><input type="checkbox" id="selectAll" style="accent-color: var(--red); width: 14px; height: 14px;"></th>
                                <th>Image</th>
                                <th>Product</th>
                                <th>Category</th>
                                <th>SKU</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Status</th>
                                <th style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($products)): ?>
                                <tr><td colspan="9" style="text-align: center; padding: 48px; color: var(--mid-gray);">No products found matching your filters.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($products as $p):
                                $threshold = lowStockThreshold($p);
                            ?>
                            <tr>
                                <td><input type="checkbox" class="product-check" name="product_ids[]" value="<?php echo $p['product_id']; ?>" style="accent-color: var(--red); width: 14px; height: 14px;"></td>
                                <td style="width: 50px;">
                                    <img src="../assets/images/<?php echo htmlspecialchars($p['image'] ?? 'placeholder.jpg'); ?>" width="36" height="36" style="object-fit: cover; border: 1px solid var(--light-gray);" alt="img">
                                </td>
                                <td>
                                    <div style="font-weight: 800;">
                                        <?php if (!empty($p['is_featured'])): ?><span style="color: var(--red); margin-right: 4px;">★</span><?php endif; ?>
                                        <?php echo htmlspecialchars($p['name']); ?>
                                    </div>
                                    <div style="font-size: 10px; opacity: 0.5; font-family: var(--f-mono); margin-top: 2px;">ID: #<?php echo $p['product_id']; ?></div>
                                </td>
                                <td>
                                    <span class="status-badge" style="background: var(--off); color: var(--ink);"><?php echo htmlspecialchars($p['category_name']); ?></span>
                                </td>
                                <td>
                                    <?php if (!empty($p['sku'])): ?>
                                        <span style="font-family: var(--f-mono); font-size: 11px;"><?php echo htmlspecialchars($p['sku']); ?></span>
                                    <?php else: ?>
                                        <span style="opacity: 0.4;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td style="font-weight: 800;">
                                    <?php if (!empty($p['variation_count'])): ?>
                                        <span style="color: var(--red);"><?php echo formatCurrency($p['variation_price'] ?? $p['price']); ?></span>
                                        <span style="opacity: 0.5; font-size: 11px;">from <?php echo formatCurrency($p['price']); ?></span>
                                        <span class="status-badge" style="background: var(--off); color: var(--ink);"><?php echo (int)$p['variation_count']; ?> vars</span>
                                    <?php else: ?>
                                        <?php echo formatCurrency($p['price']); ?>
                                        <?php if (!empty($p['original_price'])): ?>
                                            <div style="text-decoration: line-through; opacity: 0.4; font-size: 11px; font-weight: 400;"><?php echo formatCurrency($p['original_price']); ?></div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php $stock = (int)$p['stock_quantity']; ?>
                                    <span class="status-badge <?php echo $stock <= 0 ? 'status-cancelled' : ($stock <= $threshold ? 'status-pending' : 'status-paid'); ?>">
                                        <?php echo $stock; ?> In Stock
                                    </span>
                                    <?php if ($stock > 0 && $stock <= $threshold): ?>
                                        <span style="display: block; font-size: 9px; opacity: 0.6; margin-top: 4px;">low (threshold <?php echo $threshold; ?>)</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php $status = $p['status'] ?? 'published'; ?>
                                    <span class="status-badge <?php echo $status === 'published' ? 'status-paid' : 'status-pending'; ?>"><?php echo htmlspecialchars($status); ?></span>
                                </td>
                                <td style="text-align: right;">
                                     <div style="display: flex; justify-content: flex-end; gap: 8px;">
                                         <button type="button" class="action-btn quick-edit-btn"
                                                 data-id="<?php echo $p['product_id']; ?>"
                                                 data-name="<?php echo htmlspecialchars($p['name']); ?>"
                                                 data-price="<?php echo htmlspecialchars($p['price']); ?>"
                                                 data-original="<?php echo htmlspecialchars($p['original_price'] ?? ''); ?>"
                                                 data-stock="<?php echo (int)$p['stock_quantity']; ?>"
                                                 data-sku="<?php echo htmlspecialchars($p['sku'] ?? ''); ?>"
                                                 data-status="<?php echo htmlspecialchars($p['status'] ?? 'published'); ?>"
                                                 title="Quick Edit">Quick</button>
                                         <form method="POST" action="" class="d-inline">
                                             <?php echo csrfField(); ?>
                                             <input type="hidden" name="action" value="bulk_duplicate">
                                             <input type="hidden" name="product_ids[]" value="<?php echo $p['product_id']; ?>">
                                             <button class="action-btn" type="submit" title="Duplicate product">Copy</button>
                                         </form>
                                         <a class="action-btn" href="product_editor.php?id=<?php echo $p['product_id']; ?>" title="Full Edit">Edit</a>
                                          <form method="POST" action="" class="d-inline" onsubmit="return confirmAction(event, 'Delete this product?');">
                                              <?php echo csrfField(); ?>
                                              <input type="hidden" name="action" value="delete">
                                             <input type="hidden" name="product_id" value="<?php echo $p['product_id']; ?>">
                                             <button class="action-btn danger" type="submit" title="Delete">Del</button>
                                         </form>
                                     </div>
                                 </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination-bar">
                    <div class="page-info">Showing page <?php echo $page; ?> of <?php echo $total_pages; ?> (<?php echo $total_products; ?> products)</div>
                    <div style="display: flex; align-items: center; gap: 16px;">
                        <select name="per_page" class="field-input" style="width: auto; height: 40px; padding: 0 12px;" onchange="location.href='<?php echo htmlspecialchars(listUrl(['page'=>1,'per_page'=>''])); ?>&per_page='+this.value">
                            <option value="10" <?php echo $per_page==10?'selected':''; ?>>10 / page</option>
                            <option value="20" <?php echo $per_page==20?'selected':''; ?>>20 / page</option>
                            <option value="50" <?php echo $per_page==50?'selected':''; ?>>50 / page</option>
                            <option value="100" <?php echo $per_page==100?'selected':''; ?>>100 / page</option>
                        </select>
                        <div class="pagination-links">
                            <?php if ($page > 1): ?>
                                <a href="<?php echo htmlspecialchars(listUrl(['page' => $page-1])); ?>">&laquo;</a>
                            <?php endif; ?>
                            <?php for ($i = max(1, $page-3); $i <= min($total_pages, $page+3); $i++): ?>
                                <?php if ($i === $page): ?>
                                    <span class="current"><?php echo $i; ?></span>
                                <?php else: ?>
                                    <a href="<?php echo htmlspecialchars(listUrl(['page' => $i])); ?>"><?php echo $i; ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>
                            <?php if ($page < $total_pages): ?>
                                <a href="<?php echo htmlspecialchars(listUrl(['page' => $page+1])); ?>">&raquo;</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="pagination-bar">
                    <span class="page-info"><?php echo $total_products; ?> product(s)</span>
                    <select name="per_page" class="field-input" style="width: auto; height: 40px; padding: 0 12px;" onchange="location.href='<?php echo htmlspecialchars(listUrl(['page'=>1,'per_page'=>''])); ?>&per_page='+this.value">
                        <option value="10" <?php echo $per_page==10?'selected':''; ?>>10 / page</option>
                        <option value="20" <?php echo $per_page==20?'selected':''; ?>>20 / page</option>
                        <option value="50" <?php echo $per_page==50?'selected':''; ?>>50 / page</option>
                        <option value="100" <?php echo $per_page==100?'selected':''; ?>>100 / page</option>
                    </select>
                </div>
            <?php endif; ?>
        </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('selectAll');
    const bulkBar = document.getElementById('bulkBar');
    const bulkCount = document.getElementById('bulkCount');
    const productChecks = document.querySelectorAll('.product-check');

    function updateBulkBar() {
        const checked = document.querySelectorAll('.product-check:checked').length;
        bulkCount.textContent = checked;
        if (checked > 0) {
            bulkBar.classList.remove('d-none');
            bulkBar.classList.add('d-flex');
        } else {
            bulkBar.classList.add('d-none');
            bulkBar.classList.remove('d-flex');
        }
        selectAll.checked = checked === productChecks.length && productChecks.length > 0;
    }

    if (selectAll) {
        selectAll.addEventListener('change', function() {
            productChecks.forEach(cb => cb.checked = this.checked);
            updateBulkBar();
        });
    }
    productChecks.forEach(cb => cb.addEventListener('change', updateBulkBar));

    // Show contextual value select based on chosen bulk action
    const bulkActionSelect = document.getElementById('bulkActionSelect');
    if (bulkActionSelect) {
        bulkActionSelect.addEventListener('change', function() {
            document.getElementById('bulkStatusWrap').classList.toggle('d-none', this.value !== 'bulk_status');
            document.getElementById('bulkFeaturedWrap').classList.toggle('d-none', this.value !== 'bulk_featured');
            document.getElementById('bulkCategoryWrap').classList.toggle('d-none', this.value !== 'bulk_category');
            document.getElementById('bulkPriceChangeWrap').classList.toggle('d-none', this.value !== 'bulk_price_change');
            document.getElementById('bulkRestockWrap').classList.toggle('d-none', this.value !== 'bulk_restock');
            document.getElementById('bulkAlertWrap').classList.toggle('d-none', this.value !== 'bulk_alert');
        });
    }

    // Price change modal form handling
    const priceChangeForm = document.getElementById('priceChangeConfigForm');
    if (priceChangeForm) {
        priceChangeForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const type = document.getElementById('priceChangeType').value;
            const value = document.getElementById('priceChangeValue').value;
            const round = document.getElementById('roundTo99').checked ? 1 : 0;

            let typeInput = document.querySelector('#bulkForm input[name="price_change_type"]');
            if (!typeInput) {
                typeInput = document.createElement('input');
                typeInput.type = 'hidden';
                typeInput.name = 'price_change_type';
                document.getElementById('bulkForm').appendChild(typeInput);
            }
            typeInput.value = type;

            let valueInput = document.querySelector('#bulkForm input[name="price_change_value"]');
            if (!valueInput) {
                valueInput = document.createElement('input');
                valueInput.type = 'hidden';
                valueInput.name = 'price_change_value';
                document.getElementById('bulkForm').appendChild(valueInput);
            }
            valueInput.value = value;

            let roundInput = document.querySelector('#bulkForm input[name="round_to_99"]');
            if (!roundInput) {
                roundInput = document.createElement('input');
                roundInput.type = 'hidden';
                roundInput.name = 'round_to_99';
                document.getElementById('bulkForm').appendChild(roundInput);
            }
            roundInput.value = round;

            const unitEl = document.getElementById('priceChangeUnit');
            unitEl.textContent = type === 'fixed' ? 'GH₵' : '%';

            closeModal('priceChangeModal');
        });

        document.getElementById('priceChangeType').addEventListener('change', function() {
            const unitEl = document.getElementById('priceChangeUnit');
            unitEl.textContent = this.value === 'fixed' ? 'GH₵' : '%';
        });
    }

    // Restock modal form handling
    const restockForm = document.getElementById('restockConfigForm');
    if (restockForm) {
        restockForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const quantity = document.getElementById('restockQuantity').value;
            const notes = document.getElementById('restockNotes').value;

            let qtyInput = document.querySelector('#bulkForm input[name="restock_quantity"]');
            if (!qtyInput) {
                qtyInput = document.createElement('input');
                qtyInput.type = 'hidden';
                qtyInput.name = 'restock_quantity';
                document.getElementById('bulkForm').appendChild(qtyInput);
            }
            qtyInput.value = quantity;

            let notesInput = document.querySelector('#bulkForm input[name="restock_notes"]');
            if (!notesInput) {
                notesInput = document.createElement('input');
                notesInput.type = 'hidden';
                notesInput.name = 'restock_notes';
                document.getElementById('bulkForm').appendChild(notesInput);
            }
            notesInput.value = notes;
            closeModal('restockModal');
        });
    }

    // Quick Edit Modal Handling
    const quickEditButtons = document.querySelectorAll('.quick-edit-btn');
    const quickEditForm = document.getElementById('quickEditForm');
    const quickEditAlert = document.getElementById('quickEditAlert');

    quickEditButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('quickEditProductId').value = this.dataset.id;
            document.getElementById('quickEditProductName').value = this.dataset.name;
            document.getElementById('quickEditPrice').value = this.dataset.price;
            document.getElementById('quickEditOriginalPrice').value = this.dataset.original;
            document.getElementById('quickEditStock').value = this.dataset.stock;
            document.getElementById('quickEditSku').value = this.dataset.sku;
            document.getElementById('quickEditStatus').value = this.dataset.status;
            quickEditAlert.classList.add('d-none');
            openModal('quickEditModal');
        });
    });

    if (quickEditForm) {
        quickEditForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const submitBtn = document.getElementById('quickEditSaveBtn');
            const originalBtnHtml = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.textContent = 'Saving...';
            quickEditAlert.classList.add('d-none');

            const formData = new FormData(this);

            fetch('ajax/quick_edit_product.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnHtml;
                if (data.success) {
                    closeModal('quickEditModal');
                    location.reload();
                } else {
                    quickEditAlert.textContent = data.message || 'Error updating product';
                    quickEditAlert.classList.remove('d-none');
                }
            })
            .catch(err => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnHtml;
                quickEditAlert.textContent = 'Server connection error: ' + err.message;
                quickEditAlert.classList.remove('d-none');
            });
        });
    }
});

function submitBulkAction(event) {
    const checked = [...document.querySelectorAll('.product-check:checked')];
    if (checked.length === 0) {
        event.preventDefault();
        alert('Select at least one product.');
        return false;
    }
    const actionSelect = document.getElementById('bulkActionSelect');
    const action = actionSelect.value;
    let value = '';
    if (action === 'bulk_status') value = document.querySelector('[name="bulk_status_value"]').value;
    if (action === 'bulk_featured') value = document.querySelector('[name="bulk_featured_value"]').value;
    if (action === 'bulk_category') value = document.querySelector('[name="bulk_category_value"]').value;

    if (action === 'bulk_category' && !value) {
        event.preventDefault();
        alert('Select a category for the move.');
        return false;
    }

    if (action === 'bulk_price_change') {
        const type = document.querySelector('#bulkForm input[name="price_change_type"]')?.value;
        const val = document.querySelector('#bulkForm input[name="price_change_value"]')?.value;
        if (!type || val === '' || val === undefined) {
            event.preventDefault();
            alert('Configure price change options first (click Configure).');
            return false;
        }
    }

    if (action === 'bulk_restock') {
        const qty = document.querySelector('#bulkForm input[name="restock_quantity"]')?.value;
        if (!qty || qty === '' || parseInt(qty) <= 0) {
            event.preventDefault();
            alert('Configure restock quantity first (click Configure).');
            return false;
        }
    }

    if (action === 'bulk_alert') {
        // No additional config needed - just confirm
    }

    // Inject selected product ids into the bulk form
    const form = document.getElementById('bulkForm');
    form.querySelectorAll('input[name="product_ids[]"]').forEach(el => el.remove());
    checked.forEach(cb => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'product_ids[]';
        input.value = cb.value;
        form.appendChild(input);
    });

    document.getElementById('bulkActionName').value = action;

    const labels = {
        bulk_status: 'change status of',
        bulk_featured: 'update featured flag for',
        bulk_category: 'move',
        bulk_price_change: 'update price for',
        bulk_duplicate: 'duplicate',
        bulk_restock: 'restock',
        bulk_alert: 'send low-stock alerts for',
        bulk_delete: 'delete'
    };
    const ok = confirm('Are you sure you want to ' + labels[action] + ' ' + checked.length + ' product(s)?');
    if (!ok) { event.preventDefault(); return false; }
    return true;
}
</script>

<?php include 'includes/avazonia_footer.php'; ?>
