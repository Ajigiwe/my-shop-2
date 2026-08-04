<?php
/**
 * Admin: Product Editor (WooCommerce-style)
 * - Full-page add/edit form with sections:
 *   General, Pricing, Inventory, Publish, Tags, Gallery
 */
require_once '../includes/db.php';
session_start();
require_once '../includes/admin_guard.php';
require_once 'includes/product_images.php';

$productImages = new ProductImages($pdo);
$page_title = 'Add Product';
$errors = [];
$success = '';

// Existing product being edited?
$edit = null;
$edit_id = (int)($_GET['id'] ?? 0);
if ($edit_id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM products WHERE product_id = ?');
    $stmt->execute([$edit_id]);
    $edit = $stmt->fetch();
    if ($edit) {
        $page_title = 'Edit Product: ' . $edit['name'];
    } else {
        header('Location: manage_products.php');
        exit();
    }
}

// Fetch categories
$categories = [];
try {
    $stmt = $pdo->query('SELECT category_id, category_name FROM categories ORDER BY category_name');
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Fetch categories error: ' . $e->getMessage());
}

// Fetch subcategories (for dependent dropdown)
$subcategories = [];
try {
    $stmt = $pdo->query('SELECT subcategory_id, subcategory_name, category_id FROM subcategories ORDER BY subcategory_name');
    $subcategories = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Fetch subcategories error: ' . $e->getMessage());
}

// Fetch all tags
$all_tags = [];
try {
    $stmt = $pdo->query('SELECT * FROM product_tags ORDER BY tag_name');
    $all_tags = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Fetch tags error: ' . $e->getMessage());
}

// Tags currently attached to the product being edited
$product_tags = [];
if ($edit) {
    try {
        $stmt = $pdo->prepare('SELECT t.tag_id, t.tag_name FROM product_tags t JOIN product_tag_relations r ON t.tag_id = r.tag_id WHERE r.product_id = ? ORDER BY t.tag_name');
        $stmt->execute([$edit_id]);
        $product_tags = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('Fetch product tags error: ' . $e->getMessage());
    }
}

// Fetch all product attributes (for editor)
$all_attributes = [];
try {
    $stmt = $pdo->query('SELECT * FROM product_attributes ORDER BY position, name');
    $all_attributes = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Fetch attributes error: ' . $e->getMessage());
}

// Fetch attribute terms for each attribute
$attribute_terms = [];
foreach ($all_attributes as $attr) {
    try {
        $stmt = $pdo->prepare('SELECT * FROM product_attribute_terms WHERE attribute_id = ? ORDER BY position, name');
        $stmt->execute([$attr['attribute_id']]);
        $attribute_terms[$attr['attribute_id']] = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('Fetch attribute terms error: ' . $e->getMessage());
        $attribute_terms[$attr['attribute_id']] = [];
    }
}

// Fetch existing attribute relations for this product
$product_attribute_relations = [];
if ($edit) {
    try {
        $stmt = $pdo->prepare('SELECT * FROM product_attribute_relations WHERE product_id = ?');
        $stmt->execute([$edit_id]);
        $product_attribute_relations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Fetch product attribute relations error: ' . $e->getMessage());
    }
}

// Fetch existing variations for this product
$existing_variations = [];
if ($edit) {
    try {
        $stmt = $pdo->prepare('SELECT * FROM product_variations WHERE product_id = ? ORDER BY position');
        $stmt->execute([$edit_id]);
        $existing_variations = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('Fetch variations error: ' . $e->getMessage());
    }
}

// Fetch variation images for each variation
$variation_images = [];
foreach ($existing_variations as $var) {
    try {
        $stmt = $pdo->prepare('SELECT * FROM product_variation_images WHERE variation_id = ? ORDER BY display_order');
        $stmt->execute([$var['variation_id']]);
        $variation_images[$var['variation_id']] = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('Fetch variation images error: ' . $e->getMessage());
        $variation_images[$var['variation_id']] = [];
    }
}

// Fetch variation term relations for each variation
$variation_term_relations = [];
foreach ($existing_variations as $var) {
    try {
        $stmt = $pdo->prepare('SELECT * FROM product_variation_term_relations WHERE variation_id = ?');
        $stmt->execute([$var['variation_id']]);
        $variation_term_relations[$var['variation_id']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Fetch variation term relations error: ' . $e->getMessage());
        $variation_term_relations[$var['variation_id']] = [];
    }
}

// Fetch inventory movements for this product
$inventory_movements = [];
if ($edit) {
    try {
        $stmt = $pdo->prepare("
            SELECT im.*, 
                   v.sku AS variation_sku,
                   CONCAT(v.sku, ' - ', v.price) AS variation_label
            FROM inventory_movements im
            LEFT JOIN product_variations v ON v.variation_id = im.variation_id
            WHERE im.product_id = ?
            ORDER BY im.created_at DESC
            LIMIT 50
        ");
        $stmt->execute([$edit_id]);
        $inventory_movements = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('Fetch inventory movements error: ' . $e->getMessage());
    }
}

/**
 * Validate SKU uniqueness (allows empty/NULL)
 */
function skuExists($pdo, $sku, $excludeId = 0) {
    if ($sku === '') return false;
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM products WHERE sku = ? AND product_id != ?');
    $stmt->execute([$sku, $excludeId]);
    return (int)$stmt->fetchColumn() > 0;
}

/**
 * Replace product tags in product_tag_relations
 */
function saveProductTags($pdo, $productId, $tagIds) {
    $stmt = $pdo->prepare('DELETE FROM product_tag_relations WHERE product_id = ?');
    $stmt->execute([$productId]);
    $tagIds = array_unique(array_filter(array_map('intval', $tagIds)));
    if (!empty($tagIds)) {
        $stmt = $pdo->prepare('INSERT IGNORE INTO product_tag_relations (product_id, tag_id) VALUES (?, ?)');
        foreach ($tagIds as $tid) {
            $stmt->execute([$productId, $tid]);
        }
    }
}

// ------------------------------------------------------------------
// Handle POST
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission. Please refresh and try again.';
    } else {

    if ($action === 'create' || $action === 'update') {
        $name = sanitizeInput($_POST['name'] ?? '');
        $sku = sanitizeInput($_POST['sku'] ?? '');
        $category_id = (int)($_POST['category_id'] ?? 0);
        $subcategory_id = isset($_POST['subcategory_id']) && $_POST['subcategory_id'] !== '' ? (int)$_POST['subcategory_id'] : null;
        $description = sanitizeInput($_POST['description'] ?? '');
        $features = sanitizeInput($_POST['features'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $original_price = isset($_POST['original_price']) && $_POST['original_price'] !== '' ? (float)$_POST['original_price'] : null;
        $stock_quantity = (int)($_POST['stock_quantity'] ?? 0);
        $low_stock_threshold = isset($_POST['low_stock_threshold']) && $_POST['low_stock_threshold'] !== '' ? (int)$_POST['low_stock_threshold'] : null;
        $status = sanitizeInput($_POST['status'] ?? 'published');
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $tag_ids = $_POST['tags'] ?? [];
        // SEO fields
        $meta_title = sanitizeInput($_POST['meta_title'] ?? '');
        $meta_description = sanitizeInput($_POST['meta_description'] ?? '');
        $slug = sanitizeInput($_POST['slug'] ?? '');
        // Alert settings
        $alert_email = sanitizeInput($_POST['alert_email'] ?? '');
        $alert_enabled = isset($_POST['alert_enabled']) ? 1 : 1;

        if (!in_array($status, ['draft', 'published'])) $status = 'published';
        if ($category_id <= 0) $errors[] = 'Category is required';
        if (!$name) $errors[] = 'Product name is required';
        if ($price <= 0) $errors[] = 'Price must be greater than 0';
        if ($stock_quantity < 0) $errors[] = 'Stock cannot be negative';
        if ($sku !== '' && skuExists($pdo, $sku, $action === 'update' ? (int)($_POST['product_id'] ?? 0) : 0)) {
            $errors[] = "SKU '$sku' is already in use by another product";
        }
        if ($action === 'create' && empty($_FILES['images']['name'][0])) {
            $errors[] = 'At least one product image is required';
        }

        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                if ($action === 'create') {
                    $stmt = $pdo->prepare('INSERT INTO products (category_id, subcategory_id, name, sku, description, features, price, original_price, stock_quantity, low_stock_threshold, status, is_featured, meta_title, meta_description, slug, alert_email, alert_enabled) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                    $stmt->execute([$category_id, $subcategory_id, $name, $sku !== '' ? $sku : null, $description, $features, $price, $original_price, $stock_quantity, $low_stock_threshold, $status, $is_featured, $meta_title !== '' ? $meta_title : null, $meta_description !== '' ? $meta_description : null, $slug !== '' ? $slug : null, $alert_email !== '' ? $alert_email : null, $alert_enabled]);
                    $product_id = (int)$pdo->lastInsertId();
                    $msg = 'Product created successfully';
                } else {
                    $product_id = (int)($_POST['product_id'] ?? 0);
                    $stmt = $pdo->prepare('UPDATE products SET category_id = ?, subcategory_id = ?, name = ?, sku = ?, description = ?, features = ?, price = ?, original_price = ?, stock_quantity = ?, low_stock_threshold = ?, status = ?, is_featured = ?, meta_title = ?, meta_description = ?, slug = ?, alert_email = ?, alert_enabled = ? WHERE product_id = ?');
                    $stmt->execute([$category_id, $subcategory_id, $name, $sku !== '' ? $sku : null, $description, $features, $price, $original_price, $stock_quantity, $low_stock_threshold, $status, $is_featured, $meta_title !== '' ? $meta_title : null, $meta_description !== '' ? $meta_description : null, $slug !== '' ? $slug : null, $alert_email !== '' ? $alert_email : null, $alert_enabled, $product_id]);
                    $msg = 'Product updated successfully';
                }

                // Save tags
                saveProductTags($pdo, $product_id, $tag_ids);

                // Upload new images
                if (!empty($_FILES['images']['name'][0])) {
                    $productImages->uploadImages($product_id, $_FILES['images']);
                }

                // Reorder gallery if requested
                if (!empty($_POST['image_order']) && is_array($_POST['image_order'])) {
                    $productImages->setDisplayOrder($product_id, array_map('intval', $_POST['image_order']));
                }

                $pdo->commit();
                header('Location: product_editor.php?id=' . $product_id . '&success=' . urlencode($msg));
                exit();
            } catch (Exception $e) {
                $pdo->rollBack();
                error_log('Product save error: ' . $e->getMessage());
                $errors[] = 'Error saving product: ' . $e->getMessage();
            }
        }
    }

    if ($action === 'delete_image') {
        $image_id = (int)($_POST['image_id'] ?? 0);
        $product_id = (int)($_POST['product_id'] ?? 0);
        if ($image_id > 0 && $product_id > 0) {
            if ($productImages->deleteImage($image_id, $product_id)) {
                header('Location: product_editor.php?id=' . $product_id . '&success=' . urlencode('Image deleted successfully'));
                exit();
            } else {
                $errors[] = 'Failed to delete image';
            }
        }
    }

    if ($action === 'set_main_image') {
        $image_id = (int)($_POST['image_id'] ?? 0);
        $product_id = (int)($_POST['product_id'] ?? 0);
        if ($image_id > 0 && $product_id > 0) {
            if ($productImages->setPrimaryById($image_id, $product_id)) {
                header('Location: product_editor.php?id=' . $product_id . '&success=' . urlencode('Main image updated'));
                exit();
            } else {
                $errors[] = 'Failed to update main image';
            }
        }
    }

    if ($action === 'create_tag') {
        $tag_name = sanitizeInput($_POST['new_tag_name'] ?? '');
        if ($tag_name === '') {
            $errors[] = 'Tag name is required';
        } else {
            try {
                $stmt = $pdo->prepare('INSERT IGNORE INTO product_tags (tag_name) VALUES (?)');
                $stmt->execute([$tag_name]);
                header('Location: product_editor.php' . ($edit ? '?id=' . $edit_id : '') . '&success=' . urlencode('Tag created: ' . $tag_name));
                exit();
            } catch (PDOException $e) {
                error_log('Create tag error: ' . $e->getMessage());
                $errors[] = 'Error creating tag';
            }
        }
    }

    // --- Attribute CRUD ---
    if ($action === 'create_attribute') {
        $attr_name = sanitizeInput($_POST['attribute_name'] ?? '');
        $attr_slug = sanitizeInput($_POST['attribute_slug'] ?? '');
        $attr_type = sanitizeInput($_POST['attribute_type'] ?? 'select');
        if ($attr_name === '') {
            $errors[] = 'Attribute name is required';
        } else {
            try {
                $slug = $attr_slug !== '' ? $attr_slug : strtolower(preg_replace('/[^a-z0-9]+/', '-', $attr_name));
                $stmt = $pdo->prepare('INSERT INTO product_attributes (name, slug, type, position) VALUES (?, ?, ?, 0)');
                $stmt->execute([$attr_name, $slug, $attr_type]);
                header('Location: product_editor.php' . ($edit ? '?id=' . $edit_id : '') . '&success=' . urlencode('Attribute created: ' . $attr_name));
                exit();
            } catch (PDOException $e) {
                error_log('Create attribute error: ' . $e->getMessage());
                $errors[] = 'Error creating attribute';
            }
        }
    }

    if ($action === 'delete_attribute') {
        $attribute_id = (int)($_POST['attribute_id'] ?? 0);
        if ($attribute_id > 0) {
            try {
                $stmt = $pdo->prepare('DELETE FROM product_attributes WHERE attribute_id = ?');
                $stmt->execute([$attribute_id]);
                header('Location: product_editor.php' . ($edit ? '?id=' . $edit_id : '') . '&success=' . urlencode('Attribute deleted'));
                exit();
            } catch (PDOException $e) {
                error_log('Delete attribute error: ' . $e->getMessage());
                $errors[] = 'Error deleting attribute';
            }
        }
    }

    if ($action === 'create_attribute_term') {
        $attribute_id = (int)($_POST['attribute_id'] ?? 0);
        $term_name = sanitizeInput($_POST['term_name'] ?? '');
        $term_slug = sanitizeInput($_POST['term_slug'] ?? '');
        $color_hex = sanitizeInput($_POST['color_hex'] ?? '');
        if ($attribute_id <= 0 || $term_name === '') {
            $errors[] = 'Attribute and term name are required';
        } else {
            try {
                $slug = $term_slug !== '' ? $term_slug : strtolower(preg_replace('/[^a-z0-9]+/', '-', $term_name));
                $stmt = $pdo->prepare('INSERT INTO product_attribute_terms (attribute_id, name, slug, color_hex, position) VALUES (?, ?, ?, ?, 0)');
                $stmt->execute([$attribute_id, $term_name, $slug, $color_hex !== '' ? $color_hex : null]);
                header('Location: product_editor.php' . ($edit ? '?id=' . $edit_id : '') . '&success=' . urlencode('Term created: ' . $term_name));
                exit();
            } catch (PDOException $e) {
                error_log('Create attribute term error: ' . $e->getMessage());
                $errors[] = 'Error creating term';
            }
        }
    }

    if ($action === 'delete_attribute_term') {
        $term_id = (int)($_POST['term_id'] ?? 0);
        if ($term_id > 0) {
            try {
                $stmt = $pdo->prepare('DELETE FROM product_attribute_terms WHERE term_id = ?');
                $stmt->execute([$term_id]);
                header('Location: product_editor.php' . ($edit ? '?id=' . $edit_id : '') . '&success=' . urlencode('Term deleted'));
                exit();
            } catch (PDOException $e) {
                error_log('Delete attribute term error: ' . $e->getMessage());
                $errors[] = 'Error deleting term';
            }
        }
    }

    // --- Variation CRUD ---
    if ($action === 'create_variation') {
        if (!$edit) {
            $errors[] = 'Save the product first before adding variations.';
        } else {
            $sku = sanitizeInput($_POST['variation_sku'] ?? '');
            $price = (float)($_POST['variation_price'] ?? 0);
            $original_price = isset($_POST['variation_original_price']) && $_POST['variation_original_price'] !== '' ? (float)$_POST['variation_original_price'] : null;
            $stock_quantity = (int)($_POST['variation_stock_quantity'] ?? 0);
            $low_stock_threshold = isset($_POST['variation_low_stock_threshold']) && $_POST['variation_low_stock_threshold'] !== '' ? (int)$_POST['variation_low_stock_threshold'] : null;
            $term_ids = $_POST['variation_terms'] ?? [];

            if ($price <= 0) {
                $errors[] = 'Variation price must be greater than 0';
            } else {
                try {
                    $pdo->beginTransaction();
                    $stmt = $pdo->prepare('INSERT INTO product_variations (product_id, sku, price, original_price, stock_quantity, low_stock_threshold, position) VALUES (?, ?, ?, ?, ?, ?, 0)');
                    $stmt->execute([$edit_id, $sku !== '' ? $sku : null, $price, $original_price, $stock_quantity, $low_stock_threshold]);
                    $variation_id = (int)$pdo->lastInsertId();

                    // Save term relations
                    foreach ($term_ids as $tid) {
                        $tid = (int)$tid;
                        if ($tid <= 0) continue;
                        $stmt = $pdo->prepare('SELECT attribute_id FROM product_attribute_terms WHERE term_id = ?');
                        $stmt->execute([$tid]);
                        $attr = $stmt->fetch();
                        if ($attr) {
                            $stmt = $pdo->prepare('INSERT IGNORE INTO product_variation_term_relations (variation_id, attribute_id, term_id) VALUES (?, ?, ?)');
                            $stmt->execute([$variation_id, $attr['attribute_id'], $tid]);
                        }
                    }

                    $pdo->commit();
                    header('Location: product_editor.php?id=' . $edit_id . '&success=' . urlencode('Variation created'));
                    exit();
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    error_log('Create variation error: ' . $e->getMessage());
                    $errors[] = 'Error creating variation: ' . $e->getMessage();
                }
            }
        }
    }

    if ($action === 'update_variation') {
        $variation_id = (int)($_POST['variation_id'] ?? 0);
        if ($variation_id <= 0) {
            $errors[] = 'Invalid variation';
        } else {
            $sku = sanitizeInput($_POST['variation_sku'] ?? '');
            $price = (float)($_POST['variation_price'] ?? 0);
            $original_price = isset($_POST['variation_original_price']) && $_POST['variation_original_price'] !== '' ? (float)$_POST['variation_original_price'] : null;
            $stock_quantity = (int)($_POST['variation_stock_quantity'] ?? 0);
            $low_stock_threshold = isset($_POST['variation_low_stock_threshold']) && $_POST['variation_low_stock_threshold'] !== '' ? (int)$_POST['variation_low_stock_threshold'] : null;

            try {
                $stmt = $pdo->prepare('UPDATE product_variations SET sku = ?, price = ?, original_price = ?, stock_quantity = ?, low_stock_threshold = ? WHERE variation_id = ?');
                $stmt->execute([$sku !== '' ? $sku : null, $price, $original_price, $stock_quantity, $low_stock_threshold, $variation_id]);
                header('Location: product_editor.php?id=' . $edit_id . '&success=' . urlencode('Variation updated'));
                exit();
            } catch (PDOException $e) {
                error_log('Update variation error: ' . $e->getMessage());
                $errors[] = 'Error updating variation';
            }
        }
    }

    if ($action === 'delete_variation') {
        $variation_id = (int)($_POST['variation_id'] ?? 0);
        if ($variation_id > 0) {
            try {
                $stmt = $pdo->prepare('DELETE FROM product_variations WHERE variation_id = ?');
                $stmt->execute([$variation_id]);
                header('Location: product_editor.php?id=' . $edit_id . '&success=' . urlencode('Variation deleted'));
                exit();
            } catch (PDOException $e) {
                error_log('Delete variation error: ' . $e->getMessage());
                $errors[] = 'Error deleting variation';
            }
        }
    }

    if ($action === 'set_default_variation') {
        $variation_id = (int)($_POST['variation_id'] ?? 0);
        if ($edit_id > 0) {
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare('UPDATE product_variations SET is_default = 0 WHERE product_id = ?');
                $stmt->execute([$edit_id]);
                if ($variation_id > 0) {
                    $stmt = $pdo->prepare('UPDATE product_variations SET is_default = 1 WHERE variation_id = ? AND product_id = ?');
                    $stmt->execute([$variation_id, $edit_id]);
                }
                $pdo->commit();
                header('Location: product_editor.php?id=' . $edit_id . '&success=' . urlencode('Default variation updated'));
                exit();
            } catch (PDOException $e) {
                $pdo->rollBack();
                error_log('Set default variation error: ' . $e->getMessage());
                $errors[] = 'Error updating default variation';
            }
        }
    }

    // --- Inventory Movements ---
    if ($action === 'receive_stock') {
        if (!$edit) {
            $errors[] = 'Save the product first before managing inventory.';
        } else {
            $variation_id = isset($_POST['variation_id']) && $_POST['variation_id'] !== '' ? (int)$_POST['variation_id'] : null;
            $quantity = (int)($_POST['receive_quantity'] ?? 0);
            $notes = sanitizeInput($_POST['receive_notes'] ?? '');
            if ($quantity <= 0) {
                $errors[] = 'Quantity must be greater than 0';
            } else {
                try {
                    $pdo->beginTransaction();
                    if ($variation_id) {
                        $stmt = $pdo->prepare('SELECT stock_quantity FROM product_variations WHERE variation_id = ? AND product_id = ?');
                        $stmt->execute([$variation_id, $edit_id]);
                        $var = $stmt->fetch();
                        if ($var) {
                            $qty_before = $var['stock_quantity'];
                            $qty_after = $qty_before + $quantity;
                            $stmt = $pdo->prepare('UPDATE product_variations SET stock_quantity = ? WHERE variation_id = ?');
                            $stmt->execute([$qty_after, $variation_id]);
                            $stmt = $pdo->prepare('INSERT INTO inventory_movements (product_id, variation_id, type, quantity, quantity_before, quantity_after, notes) VALUES (?, ?, "receive", ?, ?, ?, ?)');
                            $stmt->execute([$edit_id, $variation_id, $quantity, $qty_before, $qty_after, $notes]);
                        }
                    } else {
                        $stmt = $pdo->prepare('SELECT stock_quantity FROM products WHERE product_id = ?');
                        $stmt->execute([$edit_id]);
                        $product = $stmt->fetch();
                        if ($product) {
                            $qty_before = $product['stock_quantity'];
                            $qty_after = $qty_before + $quantity;
                            $stmt = $pdo->prepare('UPDATE products SET stock_quantity = ? WHERE product_id = ?');
                            $stmt->execute([$qty_after, $edit_id]);
                            $stmt = $pdo->prepare('INSERT INTO inventory_movements (product_id, variation_id, type, quantity, quantity_before, quantity_after, notes) VALUES (?, NULL, "receive", ?, ?, ?, ?)');
                            $stmt->execute([$edit_id, $quantity, $qty_before, $qty_after, $notes]);
                        }
                    }
                    $pdo->commit();
                    header('Location: product_editor.php?id=' . $edit_id . '&success=' . urlencode('Stock received: ' . $quantity));
                    exit();
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    error_log('Receive stock error: ' . $e->getMessage());
                    $errors[] = 'Error receiving stock';
                }
            }
        }
    }

    if ($action === 'adjust_stock') {
        if (!$edit) {
            $errors[] = 'Save the product first before managing inventory.';
        } else {
            $variation_id = isset($_POST['variation_id']) && $_POST['variation_id'] !== '' ? (int)$_POST['variation_id'] : null;
            $quantity = (int)($_POST['adjust_quantity'] ?? 0);
            $notes = sanitizeInput($_POST['adjust_notes'] ?? '');
            if ($quantity == 0) {
                $errors[] = 'Quantity cannot be zero';
            } else {
                try {
                    $pdo->beginTransaction();
                    if ($variation_id) {
                        $stmt = $pdo->prepare('SELECT stock_quantity FROM product_variations WHERE variation_id = ? AND product_id = ?');
                        $stmt->execute([$variation_id, $edit_id]);
                        $var = $stmt->fetch();
                        if ($var) {
                            $qty_before = $var['stock_quantity'];
                            $qty_after = max(0, $qty_before + $quantity);
                            $stmt = $pdo->prepare('UPDATE product_variations SET stock_quantity = ? WHERE variation_id = ?');
                            $stmt->execute([$qty_after, $variation_id]);
                            $stmt = $pdo->prepare('INSERT INTO inventory_movements (product_id, variation_id, type, quantity, quantity_before, quantity_after, notes) VALUES (?, ?, "adjustment", ?, ?, ?, ?)');
                            $stmt->execute([$edit_id, $variation_id, $quantity, $qty_before, $qty_after, $notes]);
                        }
                    } else {
                        $stmt = $pdo->prepare('SELECT stock_quantity FROM products WHERE product_id = ?');
                        $stmt->execute([$edit_id]);
                        $product = $stmt->fetch();
                        if ($product) {
                            $qty_before = $product['stock_quantity'];
                            $qty_after = max(0, $qty_before + $quantity);
                            $stmt = $pdo->prepare('UPDATE products SET stock_quantity = ? WHERE product_id = ?');
                            $stmt->execute([$qty_after, $edit_id]);
                            $stmt = $pdo->prepare('INSERT INTO inventory_movements (product_id, variation_id, type, quantity, quantity_before, quantity_after, notes) VALUES (?, NULL, "adjustment", ?, ?, ?, ?)');
                            $stmt->execute([$edit_id, $quantity, $qty_before, $qty_after, $notes]);
                        }
                    }
                    $pdo->commit();
                    header('Location: product_editor.php?id=' . $edit_id . '&success=' . urlencode('Stock adjusted'));
                    exit();
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    error_log('Adjust stock error: ' . $e->getMessage());
                    $errors[] = 'Error adjusting stock';
                }
            }
        }
    }

    }
}

// After a successful save via redirect, refresh $edit
if ($edit_id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM products WHERE product_id = ?');
    $stmt->execute([$edit_id]);
    $edit = $stmt->fetch();
    $product_tags = [];
    try {
        $stmt = $pdo->prepare('SELECT t.tag_id, t.tag_name FROM product_tags t JOIN product_tag_relations r ON t.tag_id = r.tag_id WHERE r.product_id = ? ORDER BY t.tag_name');
        $stmt->execute([$edit_id]);
        $product_tags = $stmt->fetchAll();
    } catch (PDOException $e) {}
}

$success = $_GET['success'] ?? $success;
$selected_tag_ids = array_column($product_tags, 'tag_id');
$current_images = $edit ? $productImages->getProductImages($edit_id) : [];

include 'includes/avazonia_header.php';
?>

<style>
.editor-grid { display: grid; grid-template-columns: 1.6fr 1fr; gap: 32px; align-items: start; }
@media (max-width: 1000px) { .editor-grid { grid-template-columns: 1fr; } }
.panel-body { padding: 24px; }
.panel .panel-title { text-transform: none; font-size: 16px; letter-spacing: 0.01em; }
.tag-pill {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 8px 14px; border: 1px solid var(--light-gray); border-radius: 99px;
    font-size: 12px; font-weight: 700; cursor: pointer; transition: all 0.2s; user-select: none;
}
.tag-pill:hover { border-color: var(--ink); }
.tag-pill.checked { background: var(--ink); color: #fff; border-color: var(--ink); }
.tag-pill input { display: none; }
.term-pill {
    display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 99px;
    font-size: 11px; font-weight: 700; background: var(--off); color: var(--ink);
}
.variant-box { border: 1px solid var(--light-gray); padding: 16px; margin-bottom: 12px; background: #fff; }
.details-toggle { font-weight: 800; font-size: 11px; text-transform: uppercase; letter-spacing: 0.08em; color: var(--red); cursor: pointer; }
details .details-toggle { list-style: none; }
details .details-toggle::-webkit-details-marker { display: none; }
.editor-sub { font-size: 11px; color: var(--mid-gray); margin-top: 6px; }
.btn-dark-admin {
    background: var(--ink); color: #fff; border: none; padding: 10px 16px;
    font-weight: 800; font-size: 11px; text-transform: uppercase; letter-spacing: 0.08em;
    cursor: pointer; border-radius: 4px; font-family: var(--f-semi);
}
.btn-dark-admin:hover { background: #222; }
.file-input {
    width: 100%; padding: 14px; border: 1px dashed var(--light-gray); border-radius: 4px;
    box-sizing: border-box; font-size: 13px; background: #fff; font-family: inherit;
}
.movement-table { width: 100%; border-collapse: collapse; }
.movement-table th {
    text-align: left; padding: 10px; font-family: var(--f-mono); font-size: 9px;
    text-transform: uppercase; letter-spacing: 0.08em; color: var(--mid-gray);
    border-bottom: 1px solid var(--light-gray);
}
.movement-table td { padding: 10px; font-size: 12px; border-bottom: 1px solid var(--light-gray); }
.movement-badge { padding: 3px 8px; border-radius: 4px; font-family: var(--f-mono); font-size: 9px; font-weight: 800; text-transform: uppercase; }
.mv-receive { background: #e6f7ec; color: #00a854; }
.mv-adjustment { background: #fff7e6; color: #fa8c16; }
.gallery-thumb { position: relative; width: 90px; height: 90px; border: 1px solid var(--light-gray); cursor: pointer; box-sizing: border-box; }
.gallery-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
.gallery-thumb .main-tag { position: absolute; top: 4px; left: 4px; font-size: 8px; background: var(--red); color: #fff; padding: 2px 6px; border-radius: 99px; font-weight: 800; }
.gallery-thumb .del-btn { position: absolute; top: 4px; right: 4px; width: 22px; height: 22px; border: none; background: var(--red); color: #fff; font-size: 12px; line-height: 1; cursor: pointer; border-radius: 50%; }
.gallery-thumb .set-main { position: absolute; bottom: 4px; left: 4px; font-size: 8px; background: var(--ink); color: #fff; border: none; padding: 2px 8px; border-radius: 99px; font-weight: 800; cursor: pointer; }
.gallery-thumb.primary-thumb { border: 2px solid var(--red); }
.check-row { display: flex; align-items: center; gap: 10px; }
.check-row input[type="checkbox"] { width: 16px; height: 16px; accent-color: var(--red); }
</style>

<div class="admin-header">
    <h1><?php echo $edit ? 'Edit Product' : 'Add New Product'; ?></h1>
    <div style="display: flex; gap: 12px; flex-wrap: wrap;">
        <a href="manage_products.php" class="btn-ink" style="height: 44px; background: transparent; color: var(--ink); border: 1px solid var(--ink);">← Back to List</a>
        <button class="btn-red" type="submit" form="productForm"><?php echo $edit ? 'Save Changes' : 'Create Product'; ?></button>
    </div>
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

<form method="POST" action="" enctype="multipart/form-data" id="productForm">
    <?php echo csrfField(); ?>
    <?php if ($edit): ?>
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="product_id" value="<?php echo $edit_id; ?>">
    <?php else: ?>
        <input type="hidden" name="action" value="create">
    <?php endif; ?>

    <div class="editor-grid">
        <!-- LEFT: General + Pricing + Inventory -->
        <div>
            <div class="panel">
                <div class="panel-header"><div class="panel-title">General</div></div>
                <div class="panel-body">
                    <div class="field-grid">
                        <div class="field-group">
                            <label class="field-label">Product Name <span style="color: var(--red);">*</span></label>
                            <input type="text" class="field-input" name="name" value="<?php echo htmlspecialchars($edit['name'] ?? $_POST['name'] ?? ''); ?>" required placeholder="e.g. Samsung S25 Ultra">
                        </div>
                        <div class="field-group">
                            <label class="field-label">SKU</label>
                            <input type="text" class="field-input" name="sku" value="<?php echo htmlspecialchars($edit['sku'] ?? $_POST['sku'] ?? ''); ?>" placeholder="e.g. S25U-256">
                        </div>
                    </div>
                    <div class="field-grid">
                        <div class="field-group">
                            <label class="field-label">Category <span style="color: var(--red);">*</span></label>
                            <select class="field-input" name="category_id" id="categorySelect" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $c): ?>
                                    <option value="<?php echo $c['category_id']; ?>" <?php echo ($edit && $edit['category_id']==$c['category_id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['category_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field-group">
                            <label class="field-label">Subcategory (Optional)</label>
                            <select class="field-input" name="subcategory_id" id="subcategorySelect">
                                <option value="">Select Subcategory</option>
                                <?php foreach ($subcategories as $sc): ?>
                                    <option value="<?php echo $sc['subcategory_id']; ?>" data-category="<?php echo $sc['category_id']; ?>" <?php echo ($edit && $edit['subcategory_id']==$sc['subcategory_id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($sc['subcategory_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="field-group">
                        <label class="field-label">Description</label>
                        <textarea class="field-input" name="description" rows="4" placeholder="Detailed product description..."><?php echo htmlspecialchars($edit['description'] ?? $_POST['description'] ?? ''); ?></textarea>
                    </div>
                    <div class="field-group" style="margin-bottom: 0;">
                        <label class="field-label">Key Features (One per line)</label>
                        <textarea class="field-input" name="features" rows="5" placeholder="List key features line by line..."><?php echo htmlspecialchars($edit['features'] ?? $_POST['features'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>

            <div class="panel">
                <div class="panel-header"><div class="panel-title">Pricing</div></div>
                <div class="panel-body">
                    <div class="field-grid">
                        <div class="field-group">
                            <label class="field-label">Sale Price (GH₵) <span style="color: var(--red);">*</span></label>
                            <input type="number" step="0.01" min="0" class="field-input" name="price" value="<?php echo htmlspecialchars($edit['price'] ?? $_POST['price'] ?? ''); ?>" required>
                        </div>
                        <div class="field-group">
                            <label class="field-label">Old Price (Optional)</label>
                            <input type="number" step="0.01" min="0" class="field-input" name="original_price" value="<?php echo htmlspecialchars($edit['original_price'] ?? $_POST['original_price'] ?? ''); ?>" placeholder="Price before discount">
                        </div>
                    </div>
                </div>
            </div>

            <div class="panel">
                <div class="panel-header"><div class="panel-title">Inventory</div></div>
                <div class="panel-body">
                    <div class="field-grid">
                        <div class="field-group">
                            <label class="field-label">Stock Quantity</label>
                            <input type="number" min="0" class="field-input" name="stock_quantity" value="<?php echo htmlspecialchars($edit['stock_quantity'] ?? $_POST['stock_quantity'] ?? 0); ?>">
                        </div>
                        <div class="field-group">
                            <label class="field-label">Low Stock Threshold</label>
                            <input type="number" min="0" class="field-input" name="low_stock_threshold" value="<?php echo htmlspecialchars($edit['low_stock_threshold'] ?? $_POST['low_stock_threshold'] ?? ''); ?>" placeholder="Defaults to 5">
                            <span class="field-sub">Products at or below this quantity are flagged as low stock.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT: Publish + Tags + Attributes + Variations + Stock Movements -->
        <div>
            <div class="panel">
                <div class="panel-header"><div class="panel-title">Publish</div></div>
                <div class="panel-body">
                    <div class="field-group">
                        <label class="field-label">Status</label>
                        <select class="field-input" name="status">
                            <option value="published" <?php echo ($edit['status'] ?? 'published') === 'published' ? 'selected' : ''; ?>>Published</option>
                            <option value="draft" <?php echo ($edit['status'] ?? '') === 'draft' ? 'selected' : ''; ?>>Draft</option>
                        </select>
                        <span class="field-sub">Draft products are hidden from the storefront.</span>
                    </div>
                    <div class="check-row">
                        <input type="checkbox" name="is_featured" id="isFeatured" value="1" <?php echo !empty($edit['is_featured']) ? 'checked' : ''; ?>>
                        <label class="field-label" for="isFeatured" style="margin: 0;"><span style="color: var(--red);">★</span> Featured Product</label>
                    </div>
                </div>
            </div>

            <div class="panel">
                <div class="panel-header"><div class="panel-title">Tags</div></div>
                <div class="panel-body">
                    <?php if (empty($all_tags)): ?>
                        <p class="editor-sub" style="margin: 0 0 16px;">No tags yet.</p>
                    <?php else: ?>
                        <div class="d-flex flex-wrap gap-2" style="margin-bottom: 16px;">
                            <?php foreach ($all_tags as $tag): ?>
                                <label class="tag-pill <?php echo in_array($tag['tag_id'], $selected_tag_ids) ? 'checked' : ''; ?>">
                                    <input type="checkbox" name="tags[]" value="<?php echo $tag['tag_id']; ?>" <?php echo in_array($tag['tag_id'], $selected_tag_ids) ? 'checked' : ''; ?>>
                                    <?php echo htmlspecialchars($tag['tag_name']); ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <hr style="border: none; border-top: 1px solid var(--light-gray); margin: 20px 0;">
                    <label class="field-label">Create New Tag</label>
                    <div class="d-flex gap-2">
                        <input type="text" class="field-input" id="newTagName" placeholder="Tag name" style="height: 40px;">
                        <button class="btn-dark-admin" type="button" onclick="submitNewTag()" style="height: 40px;">+</button>
                    </div>
                </div>
            </div>

            <div class="panel">
                <div class="panel-header">
                    <div class="panel-title">Attributes</div>
                    <?php if (!empty($all_attributes)): ?>
                        <span class="status-badge" style="background: var(--off); color: var(--ink);"><?php echo count($all_attributes); ?> attribute(s)</span>
                    <?php endif; ?>
                </div>
                <div class="panel-body">
                    <?php if (empty($all_attributes)): ?>
                        <p class="editor-sub" style="margin: 0 0 16px;">No attributes defined yet.</p>
                    <?php else: ?>
                        <?php foreach ($all_attributes as $attr): ?>
                            <div style="padding: 12px 0; border-bottom: 1px solid var(--light-gray);">
                                <div class="d-flex justify-content-between align-items-center" style="margin-bottom: 8px;">
                                    <span style="font-weight: 800; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em;"><?php echo htmlspecialchars($attr['name']); ?></span>
                                    <span class="status-badge" style="background: var(--off); color: var(--ink);"><?php echo isset($attribute_terms[$attr['attribute_id']]) ? count($attribute_terms[$attr['attribute_id']]) : 0; ?> terms</span>
                                </div>
                                <div class="d-flex flex-wrap gap-2">
                                    <?php foreach ($attribute_terms[$attr['attribute_id']] ?? [] as $term): ?>
                                        <span class="term-pill">
                                            <?php if (!empty($term['color_hex'])): ?>
                                                <span style="width: 8px; height: 8px; border-radius: 50%; display: inline-block; background: <?php echo htmlspecialchars($term['color_hex']); ?>;"></span>
                                            <?php endif; ?>
                                            <?php echo htmlspecialchars($term['name']); ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <hr style="border: none; border-top: 1px solid var(--light-gray); margin: 20px 0;">
                    <details style="margin-bottom: 12px;">
                        <summary class="details-toggle">Add Attribute</summary>
                        <form method="POST" action="" style="margin-top: 12px;">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="action" value="create_attribute">
                            <div class="field-group" style="margin-bottom: 10px;">
                                <input type="text" name="attribute_name" class="field-input" placeholder="Attribute name (e.g. Color)" required>
                            </div>
                            <div class="field-group" style="margin-bottom: 10px;">
                                <input type="text" name="attribute_slug" class="field-input" placeholder="Slug (auto-generated if empty)">
                            </div>
                            <div class="field-group" style="margin-bottom: 10px;">
                                <select name="attribute_type" class="field-input">
                                    <option value="select">Select (dropdown)</option>
                                    <option value="color">Color (swatch)</option>
                                    <option value="size">Size</option>
                                    <option value="text">Text</option>
                                </select>
                            </div>
                            <button class="btn-dark-admin w-100" type="submit">Create Attribute</button>
                        </form>
                    </details>
                    <?php if (!empty($all_attributes)): ?>
                        <details>
                            <summary class="details-toggle">Add Term to Attribute</summary>
                            <form method="POST" action="" style="margin-top: 12px;">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="action" value="create_attribute_term">
                                <div class="field-group" style="margin-bottom: 10px;">
                                    <select name="attribute_id" class="field-input" required>
                                        <option value="">Select attribute...</option>
                                        <?php foreach ($all_attributes as $attr): ?>
                                            <option value="<?php echo $attr['attribute_id']; ?>"><?php echo htmlspecialchars($attr['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="field-group" style="margin-bottom: 10px;">
                                    <input type="text" name="term_name" class="field-input" placeholder="Term name (e.g. Red)" required>
                                </div>
                                <div class="field-group" style="margin-bottom: 10px;">
                                    <input type="text" name="term_slug" class="field-input" placeholder="Slug (auto-generated if empty)">
                                </div>
                                <div class="field-group" style="margin-bottom: 10px;">
                                    <input type="text" name="color_hex" class="field-input" placeholder="#RRGGBB (for color type)" maxlength="7">
                                </div>
                                <button class="btn-dark-admin w-100" type="submit">Create Term</button>
                            </form>
                        </details>
                    <?php endif; ?>
                </div>
            </div>

            <div class="panel">
                <div class="panel-header">
                    <div class="panel-title">Variations</div>
                    <?php if (!empty($existing_variations)): ?>
                        <span class="status-badge" style="background: var(--off); color: var(--ink);"><?php echo count($existing_variations); ?> variation(s)</span>
                    <?php endif; ?>
                </div>
                <div class="panel-body">
                    <?php if (empty($existing_variations)): ?>
                        <p class="editor-sub" style="margin: 0 0 16px;">No variations yet. Create one below.</p>
                    <?php else: ?>
                        <?php foreach ($existing_variations as $var): ?>
                            <div class="variant-box">
                                <div class="d-flex justify-content-between align-items-start" style="margin-bottom: 8px;">
                                    <div>
                                        <span style="font-weight: 900; font-family: var(--f-mono);"><?php echo htmlspecialchars($var['sku'] ?? 'N/A'); ?></span>
                                        <span style="color: var(--red); font-weight: 900; margin-left: 8px;"><?php echo formatCurrency($var['price']); ?></span>
                                        <?php if ($var['is_default']): ?>
                                            <span class="status-badge status-paid" style="margin-left: 6px;">Default</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <?php if (!$var['is_default']): ?>
                                            <form method="POST" action="" class="d-inline" onsubmit="return confirm('Set as default variation?');">
                                                <?php echo csrfField(); ?>
                                                <input type="hidden" name="action" value="set_default_variation">
                                                <input type="hidden" name="variation_id" value="<?php echo $var['variation_id']; ?>">
                                                <button class="action-btn" title="Set as default" type="submit">★</button>
                                            </form>
                                        <?php endif; ?>
                                         <form method="POST" action="" class="d-inline" onsubmit="return confirm('Delete this variation?');">
                                             <?php echo csrfField(); ?>
                                             <input type="hidden" name="action" value="delete_variation">
                                            <input type="hidden" name="variation_id" value="<?php echo $var['variation_id']; ?>">
                                            <button class="action-btn danger" title="Delete" type="submit">×</button>
                                        </form>
                                    </div>
                                </div>
                                <div class="editor-sub">
                                    Stock: <strong><?php echo (int)$var['stock_quantity']; ?></strong>
                                    <?php if ($var['original_price']): ?>
                                        &nbsp;|&nbsp; Was: <span style="text-decoration: line-through;"><?php echo formatCurrency($var['original_price']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <hr style="border: none; border-top: 1px solid var(--light-gray); margin: 20px 0;">
                    <details>
                        <summary class="details-toggle">Add Variation</summary>
                        <form method="POST" action="" style="margin-top: 12px;">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="action" value="create_variation">
                            <div class="field-group" style="margin-bottom: 10px;">
                                <label class="field-label">SKU</label>
                                <input type="text" name="variation_sku" class="field-input" placeholder="e.g. S25U-BLK-M">
                            </div>
                            <div class="field-grid" style="margin-bottom: 10px;">
                                <div class="field-group" style="margin-bottom: 0;">
                                    <label class="field-label">Price (GH₵) *</label>
                                    <input type="number" step="0.01" min="0" name="variation_price" class="field-input" required>
                                </div>
                                <div class="field-group" style="margin-bottom: 0;">
                                    <label class="field-label">Old Price</label>
                                    <input type="number" step="0.01" min="0" name="variation_original_price" class="field-input">
                                </div>
                            </div>
                            <div class="field-grid" style="margin-bottom: 10px;">
                                <div class="field-group" style="margin-bottom: 0;">
                                    <label class="field-label">Stock</label>
                                    <input type="number" min="0" name="variation_stock_quantity" class="field-input" value="0">
                                </div>
                                <div class="field-group" style="margin-bottom: 0;">
                                    <label class="field-label">Low Stock Threshold</label>
                                    <input type="number" min="0" name="variation_low_stock_threshold" class="field-input" placeholder="5">
                                </div>
                            </div>
                            <?php if (!empty($all_attributes)): ?>
                                <label class="field-label">Select Terms</label>
                                <?php foreach ($all_attributes as $attr): ?>
                                    <div style="margin-bottom: 10px;">
                                        <div class="editor-sub" style="font-weight: 800; margin-bottom: 4px;"><?php echo htmlspecialchars($attr['name']); ?></div>
                                        <?php foreach ($attribute_terms[$attr['attribute_id']] ?? [] as $term): ?>
                                            <label class="check-row" style="margin-bottom: 4px; cursor: pointer;">
                                                <input type="checkbox" name="variation_terms[]" value="<?php echo $term['term_id']; ?>">
                                                <span style="font-size: 12px;">
                                                    <?php if (!empty($term['color_hex'])): ?>
                                                        <span style="width: 10px; height: 10px; border-radius: 50%; display: inline-block; margin-right: 6px; background: <?php echo htmlspecialchars($term['color_hex']); ?>;"></span>
                                                    <?php endif; ?>
                                                    <?php echo htmlspecialchars($term['name']); ?>
                                                </span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="editor-sub">No attributes defined yet. Create attributes first.</p>
                            <?php endif; ?>
                            <button class="btn-dark-admin w-100" type="submit" style="margin-top: 10px;">Create Variation</button>
                        </form>
                    </details>
                </div>
            </div>

            <div class="panel">
                <div class="panel-header">
                    <div class="panel-title">Stock Movements</div>
                    <?php if ($edit): ?>
                        <span class="status-badge" style="background: var(--off); color: var(--ink);"><?php echo (int)($edit['stock_quantity'] ?? 0); ?> in stock</span>
                    <?php endif; ?>
                </div>
                <div class="panel-body">
                    <?php if ($edit): ?>
                        <details style="margin-bottom: 12px;">
                            <summary class="details-toggle">Receive Stock</summary>
                            <form method="POST" action="" style="margin-top: 12px;">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="action" value="receive_stock">
                                <div class="field-group" style="margin-bottom: 10px;">
                                    <label class="field-label">Quantity</label>
                                    <input type="number" min="1" name="receive_quantity" class="field-input" placeholder="e.g. 50" required>
                                </div>
                                <div class="field-group" style="margin-bottom: 10px;">
                                    <label class="field-label">Notes</label>
                                    <input type="text" name="receive_notes" class="field-input" placeholder="e.g. PO #1234, supplier name">
                                </div>
                                <button class="btn-dark-admin w-100" type="submit">↓ Receive</button>
                            </form>
                        </details>
                        <details style="margin-bottom: 16px;">
                            <summary class="details-toggle">Adjust Stock</summary>
                            <form method="POST" action="" style="margin-top: 12px;">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="action" value="adjust_stock">
                                <div class="field-group" style="margin-bottom: 10px;">
                                    <label class="field-label">Quantity (+/-)</label>
                                    <input type="number" name="adjust_quantity" class="field-input" placeholder="e.g. -5 or +10" required>
                                </div>
                                <div class="field-group" style="margin-bottom: 10px;">
                                    <label class="field-label">Reason</label>
                                    <input type="text" name="adjust_notes" class="field-input" placeholder="e.g. Damaged, count correction">
                                </div>
                                <button class="btn-dark-admin w-100" type="submit">✎ Adjust</button>
                            </form>
                        </details>
                        <hr style="border: none; border-top: 1px solid var(--light-gray); margin: 16px 0;">
                        <label class="field-label">Movement History</label>
                        <?php if (empty($inventory_movements)): ?>
                            <p class="editor-sub">No movements recorded yet.</p>
                        <?php else: ?>
                            <div style="max-height: 300px; overflow-y: auto;">
                                <table class="movement-table">
                                    <thead>
                                        <tr>
                                            <th>Type</th>
                                            <th>Qty</th>
                                            <th>Before</th>
                                            <th>After</th>
                                            <th>Notes</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($inventory_movements as $mv): ?>
                                            <tr>
                                                <td><span class="movement-badge <?php echo $mv['type'] === 'receive' ? 'mv-receive' : 'mv-adjustment'; ?>"><?php echo htmlspecialchars($mv['type']); ?></span></td>
                                                <td style="font-weight: 800;"><?php echo $mv['quantity'] > 0 ? '+' : ''; ?><?php echo $mv['quantity']; ?></td>
                                                <td><?php echo $mv['quantity_before']; ?></td>
                                                <td style="font-weight: 800;"><?php echo $mv['quantity_after']; ?></td>
                                                <td style="color: var(--mid-gray);"><?php echo htmlspecialchars($mv['notes'] ?? '-'); ?></td>
                                                <td style="color: var(--mid-gray);"><?php echo date('M d, H:i', strtotime($mv['created_at'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="editor-sub" style="margin: 0;">Save the product first to manage inventory.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Stock Alerts (full width) -->
    <div class="panel">
        <div class="panel-header"><div class="panel-title">Stock Alerts</div></div>
        <div class="panel-body">
            <?php if ($edit): ?>
                <div class="check-row" style="margin-bottom: 16px;">
                    <input type="checkbox" name="alert_enabled" id="alertEnabled" value="1" <?php echo !empty($edit['alert_enabled']) ? 'checked' : ''; ?>>
                    <label class="field-label" for="alertEnabled" style="margin: 0;">🔔 Enable Low-Stock Alerts</label>
                </div>
                <div class="field-group">
                    <label class="field-label">Alert Email</label>
                    <input type="email" class="field-input" name="alert_email" value="<?php echo htmlspecialchars($edit['alert_email'] ?? $_POST['alert_email'] ?? ''); ?>" placeholder="e.g. manager@shop.com">
                    <span class="field-sub">Leave empty to use the admin email. Receives an alert when stock drops to or below the threshold.</span>
                </div>
                <div class="editor-sub">
                    Current stock: <strong><?php echo (int)($edit['stock_quantity'] ?? 0); ?></strong>
                    &nbsp;|&nbsp; Threshold: <strong><?php echo (int)($edit['low_stock_threshold'] ?? 5); ?></strong>
                    <?php if ((int)($edit['stock_quantity'] ?? 0) <= (int)($edit['low_stock_threshold'] ?? 5)): ?>
                        &nbsp;|&nbsp; <span style="color: var(--red); font-weight: 800;">⚠ Below threshold</span>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <p class="editor-sub" style="margin: 0;">Save the product first to configure alerts.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- SEO (full width) -->
    <div class="panel">
        <div class="panel-header"><div class="panel-title">SEO</div></div>
        <div class="panel-body">
            <div class="field-group">
                <label class="field-label">Meta Title</label>
                <input type="text" class="field-input" name="meta_title" value="<?php echo htmlspecialchars($edit['meta_title'] ?? $_POST['meta_title'] ?? ''); ?>" placeholder="Max 60 chars for Google" maxlength="255">
                <span class="field-sub">Leave empty to use product name. Shown in browser tab & search results.</span>
            </div>
            <div class="field-group">
                <label class="field-label">Meta Description</label>
                <textarea class="field-input" name="meta_description" rows="3" placeholder="Max 160 chars for Google" maxlength="320"><?php echo htmlspecialchars($edit['meta_description'] ?? $_POST['meta_description'] ?? ''); ?></textarea>
                <span class="field-sub">Leave empty to auto-generate from product description.</span>
            </div>
            <div class="field-group" style="margin-bottom: 0;">
                <label class="field-label">URL Slug</label>
                <input type="text" class="field-input" name="slug" value="<?php echo htmlspecialchars($edit['slug'] ?? $_POST['slug'] ?? ''); ?>" placeholder="auto-generated from name">
                <span class="field-sub">Unique URL-friendly identifier (e.g., samsung-s25-ultra). Auto-generated if left empty.</span>
            </div>
        </div>
    </div>

    <!-- Product Gallery (full width) -->
    <div class="panel">
        <div class="panel-header"><div class="panel-title">Product Gallery</div></div>
        <div class="panel-body">
            <div class="field-group">
                <label class="field-label">Upload Images (Select Multiple) <?php echo !$edit ? '<span style="color: var(--red);">*</span>' : ''; ?></label>
                <input type="file" class="file-input" name="images[]" accept="image/*" multiple <?php echo !$edit ? 'required' : ''; ?>>
                <span class="field-sub">JPG, PNG, GIF, WEBP up to 5MB each.</span>
            </div>

            <?php if ($edit): ?>
                <label class="field-label" style="margin-top: 24px;">Current Gallery (use arrows to reorder)</label>
                <div class="d-flex flex-wrap gap-2" id="galleryGrid">
                    <?php foreach ($current_images as $img): ?>
                        <div class="gallery-thumb <?php echo $img['is_primary'] ? 'primary-thumb' : ''; ?>" data-image-id="<?php echo $img['image_id']; ?>">
                            <input type="hidden" name="image_order[]" value="<?php echo $img['image_id']; ?>">
                            <img src="../assets/images/<?php echo htmlspecialchars($img['image_path']); ?>" alt="img">
                            <?php if ($img['is_primary']): ?>
                                <span class="main-tag">Main</span>
                            <?php endif; ?>
                            <button type="button" onclick="deleteProductImage(<?php echo $img['image_id']; ?>, <?php echo $edit_id; ?>)" class="del-btn" title="Delete Image">×</button>
                            <?php if (!$img['is_primary']): ?>
                                <button type="button" onclick="setPrimaryImage(<?php echo $img['image_id']; ?>, <?php echo $edit_id; ?>)" class="set-main" title="Set as main image">Set Main</button>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if (count($current_images) > 1): ?>
                    <div class="d-flex flex-wrap align-items-center gap-2" style="margin-top: 16px;">
                        <button type="button" class="action-btn" onclick="moveSelectedUp()">↑ Move Selected Up</button>
                        <button type="button" class="action-btn" onclick="moveSelectedDown()">↓ Move Selected Down</button>
                        <span class="editor-sub" style="margin: 0;">Select an image by clicking it, then use these buttons.</span>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <div style="display: flex; justify-content: flex-end; gap: 12px; margin-bottom: 40px;">
        <a href="manage_products.php" class="btn-ink" style="height: 44px; background: transparent; color: var(--ink); border: 1px solid var(--ink);">← Cancel</a>
        <button class="btn-red" type="submit"><?php echo $edit ? 'Save Changes' : 'Create Product'; ?></button>
    </div>
</form>

<!-- Hidden forms for image ops -->
<form id="deleteImageForm" method="POST" action="">
    <?php echo csrfField(); ?>
    <input type="hidden" name="action" value="delete_image">
    <input type="hidden" name="image_id" id="delete_image_id">
    <input type="hidden" name="product_id" id="delete_product_id">
</form>
<form id="setMainImageForm" method="POST" action="">
    <?php echo csrfField(); ?>
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

function submitNewTag() {
    const input = document.getElementById('newTagName');
    if (!input.value.trim()) { alert('Enter a tag name.'); return; }
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '';
    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'action';
    actionInput.value = 'create_tag';
    form.appendChild(actionInput);
    const tagInput = document.createElement('input');
    tagInput.type = 'hidden';
    tagInput.name = 'new_tag_name';
    tagInput.value = input.value.trim();
    form.appendChild(tagInput);
    <?php if ($edit): ?>
    const idInput = document.createElement('input');
    idInput.type = 'hidden';
    idInput.name = 'edit_id';
    idInput.value = <?php echo $edit_id; ?>;
    form.appendChild(idInput);
    <?php endif; ?>
    document.body.appendChild(form);
    form.submit();
}

// Tag pill toggle styling
document.querySelectorAll('.tag-pill').forEach(function(pill) {
    const checkbox = pill.querySelector('input[type=checkbox]');
    if (!checkbox) return;
    checkbox.addEventListener('change', function() {
        pill.classList.toggle('checked', this.checked);
    });
});

// Gallery reorder
let selectedImage = null;
function getGalleryItems() {
    return Array.from(document.querySelectorAll('#galleryGrid > div'));
}
function reorderHiddenInputs() {
    getGalleryItems().forEach(function(item, index) {
        const input = item.querySelector('input[name="image_order[]"]');
        if (input) input.value = item.dataset.imageId;
    });
}
function moveSelectedUp() {
    const items = getGalleryItems();
    const idx = items.indexOf(selectedImage);
    if (idx > 0) {
        selectedImage.parentNode.insertBefore(selectedImage, items[idx - 1]);
        reorderHiddenInputs();
    }
}
function moveSelectedDown() {
    const items = getGalleryItems();
    const idx = items.indexOf(selectedImage);
    if (idx > -1 && idx < items.length - 1) {
        selectedImage.parentNode.insertBefore(items[idx + 1], selectedImage);
        reorderHiddenInputs();
    }
}
document.addEventListener('DOMContentLoaded', function() {
    reorderHiddenInputs();
    const gallery = document.getElementById('galleryGrid');
    if (gallery) {
        gallery.addEventListener('click', function(e) {
            const item = e.target.closest('#galleryGrid > div');
            if (item) {
                getGalleryItems().forEach(i => {
                    i.style.outline = '';
                    i.style.outlineOffset = '';
                    i.style.borderRadius = '';
                });
                selectedImage = item;
                item.style.outline = '2px solid var(--ink)';
                item.style.outlineOffset = '2px';
                item.style.borderRadius = '4px';
            }
        });
    }

    // Dependent subcategory dropdown
    const catSelect = document.getElementById('categorySelect');
    const subSelect = document.getElementById('subcategorySelect');
    if (catSelect && subSelect) {
        function filterSubcategories() {
            const catId = catSelect.value;
            const currentVal = subSelect.value;
            let anyVisible = false;
            Array.from(subSelect.options).forEach(function(opt) {
                if (!opt.value) { opt.hidden = false; return; }
                opt.hidden = String(opt.dataset.category) !== catId;
                if (!opt.hidden) anyVisible = true;
            });
            if (!anyVisible || !catId) {
                subSelect.value = '';
                subSelect.disabled = !catId;
            } else if (currentVal) {
                const opt = subSelect.querySelector('option[value="' + currentVal + '"]');
                if (opt && !opt.hidden) subSelect.value = currentVal;
                else subSelect.value = '';
            }
        }
        catSelect.addEventListener('change', filterSubcategories);
        filterSubcategories();
    }
});
</script>

<?php include 'includes/avazonia_footer.php'; ?>
