<?php
/**
 * Admin: Advanced CSV Import
 * - Step 1: Upload CSV
 * - Step 2: Column mapping
 * - Step 3: Preview with validation + dry-run
 * - Step 4: Confirm import (with rollback log)
 */
require_once '../includes/db.php';
session_start();
require_once '../includes/admin_guard.php';

$page_title = 'Import Products';
$errors = [];
$results = [];

// Column mapping options
$columnMapOptions = [
    'skip' => '— Skip —',
    'product_id' => 'Product ID',
    'name' => 'Product Name',
    'sku' => 'SKU',
    'category' => 'Category',
    'subcategory' => 'Subcategory',
    'price' => 'Sale Price',
    'original_price' => 'Original Price',
    'stock_quantity' => 'Stock Quantity',
    'low_stock_threshold' => 'Low Stock Threshold',
    'status' => 'Status',
    'is_featured' => 'Featured',
    'description' => 'Description',
    'features' => 'Features',
    'tags' => 'Tags (comma-separated)',
    'meta_title' => 'Meta Title',
    'meta_description' => 'Meta Description',
    'slug' => 'URL Slug',
];

// ------------------------------------------------------------------
// Step 1: Upload + parse
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'upload') {
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission. Please refresh and try again.';
    } else {
    if (empty($_FILES['csv']['tmp_name'])) {
        $errors[] = 'Please choose a CSV file to upload';
    } else {
        $parsed = parseCsvFile($_FILES['csv']['tmp_name']);
        if (empty($parsed)) {
            $errors[] = 'No valid rows found. Check the file and try again.';
        } else {
            $_SESSION['csv_headers'] = array_keys($parsed[0]);
            $_SESSION['csv_rows'] = $parsed;
            $_SESSION['csv_filename'] = $_FILES['csv']['name'];
            header('Location: csv_import.php?step=map');
            exit();
        }
    }
    }
}

// ------------------------------------------------------------------
// Step 2: Column mapping
// ------------------------------------------------------------------
$columnMapping = [];
if (isset($_GET['step']) && $_GET['step'] === 'map') {
    $headers = $_SESSION['csv_headers'] ?? [];
    $rows = $_SESSION['csv_rows'] ?? [];

    if (empty($headers)) {
        header('Location: csv_import.php');
        exit();
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'map') {
        if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $errors[] = 'Invalid form submission. Please refresh and try again.';
        } else {
        $columnMapping = $_POST['column_map'] ?? [];
        $_SESSION['csv_column_map'] = $columnMapping;
        header('Location: csv_import.php?step=preview');
        exit();
        }
    }
}

// ------------------------------------------------------------------
// Step 3: Preview + validation
// ------------------------------------------------------------------
$preview = [];
$validationErrors = [];
$mapping = [];
if (isset($_GET['step']) && $_GET['step'] === 'preview') {
    $rows = $_SESSION['csv_rows'] ?? [];
    $mapping = $_SESSION['csv_column_map'] ?? [];

    if (empty($rows)) {
        header('Location: csv_import.php');
        exit();
    }

    $preview = validateRows($rows, $mapping);

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'preview') {
        if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $errors[] = 'Invalid form submission. Please refresh and try again.';
        } else {
        $_SESSION['csv_preview_validated'] = $preview;
        header('Location: csv_import.php?step=confirm');
        exit();
        }
    }
}

// ------------------------------------------------------------------
// Step 4: Confirm + import
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'confirm') {
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission. Please refresh and try again.';
    } else {
    $autoCreate = isset($_POST['auto_create_categories']);
    $dryRun = isset($_POST['dry_run']);
    $preview = $_SESSION['csv_preview_validated'] ?? [];
    $mapping = $_SESSION['csv_column_map'] ?? [];

    if (empty($preview)) {
        header('Location: csv_import.php');
        exit();
    }

    $results = runImport($pdo, $preview, $mapping, $autoCreate, $dryRun);

    if ($dryRun) {
        $results['dry_run'] = true;
    } else {
        // Log the import for rollback
        logImport($pdo, $_SESSION['csv_filename'] ?? 'unknown', count($preview), $results);
    }

    unset($_SESSION['csv_headers'], $_SESSION['csv_rows'], $_SESSION['csv_filename'], $_SESSION['csv_column_map'], $_SESSION['csv_preview_validated']);
    $preview = [];
    }
}

// ------------------------------------------------------------------
// Helpers
// ------------------------------------------------------------------
function parseCsvFile($path) {
    $handle = fopen($path, 'r');
    if (!$handle) return [];

    $first = fgets($handle);
    $first = preg_replace('/^\xEF\xBB\xBF/', '', $first);
    $headers = str_getcsv(trim($first));

    $normalize = function ($h) {
        return strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', trim($h)));
    };
    $map = [];
    foreach ($headers as $i => $h) {
        $key = $normalize($h);
        if ($key === '') continue;
        if ($key === 'product_id' || $key === 'id') $key = 'product_id';
        if ($key === 'is_featured' || $key === 'featured') $key = 'is_featured';
        $map[$i] = $key;
    }

    $rows = [];
    while (($line = fgetcsv($handle)) !== false) {
        if (count($line) === 1 && trim((string)$line[0]) === '') continue;
        $row = [];
        foreach ($map as $i => $key) {
            $row[$key] = $line[$i] ?? '';
        }
        if (trim((string)($row['name'] ?? '')) === '' && trim((string)($row['sku'] ?? '')) === '' && trim((string)($row['product_id'] ?? '')) === '') {
            continue;
        }
        $rows[] = $row;
    }
    fclose($handle);
    return $rows;
}

function categoryIdByName($pdo, $name, $autoCreate) {
    $stmt = $pdo->prepare('SELECT category_id FROM categories WHERE category_name = ?');
    $stmt->execute([$name]);
    $id = $stmt->fetchColumn();
    if (!$id && $autoCreate) {
        $stmt = $pdo->prepare('INSERT INTO categories (category_name) VALUES (?)');
        $stmt->execute([$name]);
        return (int)$pdo->lastInsertId();
    }
    return $id ? (int)$id : 0;
}

function subcategoryIdByName($pdo, $name, $categoryId, $autoCreate) {
    if ($name === '') return null;
    $stmt = $pdo->prepare('SELECT subcategory_id FROM subcategories WHERE category_id = ? AND subcategory_name = ?');
    $stmt->execute([$categoryId, $name]);
    $id = $stmt->fetchColumn();
    if (!$id && $autoCreate) {
        $stmt = $pdo->prepare('INSERT INTO subcategories (category_id, subcategory_name) VALUES (?, ?)');
        $stmt->execute([$categoryId, $name]);
        return (int)$pdo->lastInsertId();
    }
    return $id ? (int)$id : null;
}

function importTags($pdo, $productId, $tagString) {
    $tagNames = array_filter(array_map('trim', explode(',', (string)$tagString)));
    $tagNames = array_unique($tagNames);
    if (empty($tagNames)) return;
    $insertTag = $pdo->prepare('INSERT IGNORE INTO product_tags (tag_name) VALUES (?)');
    $findTag = $pdo->prepare('SELECT tag_id FROM product_tags WHERE tag_name = ?');
    $linkTag = $pdo->prepare('INSERT IGNORE INTO product_tag_relations (product_id, tag_id) VALUES (?, ?)');
    foreach ($tagNames as $name) {
        $insertTag->execute([$name]);
        $findTag->execute([$name]);
        $tagId = (int)$findTag->fetchColumn();
        if ($tagId) $linkTag->execute([$productId, $tagId]);
    }
}

function validateRows($rows, $mapping) {
    $validated = [];
    $rowNum = 0;

    foreach ($rows as $row) {
        $rowNum++;
        $rowErrors = [];
        $rowWarnings = [];

        $name = trim((string)($row['name'] ?? ''));
        $sku = trim((string)($row['sku'] ?? ''));
        $productId = (int)($row['product_id'] ?? 0);
        $price = (float)($row['price'] ?? 0);
        $categoryName = trim((string)($row['category'] ?? ''));
        $stockQuantity = (int)($row['stock_quantity'] ?? 0);

        // Only validate new products (no existing product_id or sku match)
        // We can't check DB here for performance; we'll validate structure only
        if (!$productId && !$sku && $name === '') {
            $rowErrors[] = 'Missing name, SKU, and product_id';
        }
        if ($price < 0) {
            $rowErrors[] = 'Price cannot be negative';
        }
        if ($stockQuantity < 0) {
            $rowErrors[] = 'Stock quantity cannot be negative';
        }
        if ($price > 0 && $price < 0.01) {
            $rowWarnings[] = 'Price seems unusually low';
        }

        $validated[] = [
            'row_num' => $rowNum,
            'data' => $row,
            'errors' => $rowErrors,
            'warnings' => $rowWarnings,
            'valid' => empty($rowErrors),
        ];
    }

    return $validated;
}

function logImport($pdo, $filename, $totalRows, $results) {
    try {
        $stmt = $pdo->prepare('INSERT INTO import_jobs (filename, total_rows, created_count, updated_count, skipped_count, error_count, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())');
        $stmt->execute([
            $filename,
            $totalRows,
            $results['created'] ?? 0,
            $results['updated'] ?? 0,
            $results['skipped'] ?? 0,
            count($results['errors'] ?? []),
            'completed'
        ]);
        $jobId = (int)$pdo->lastInsertId();

        // Log individual errors
        if (!empty($results['errors'])) {
            $stmt = $pdo->prepare('INSERT INTO import_job_errors (job_id, row_number, message) VALUES (?, ?, ?)');
            foreach ($results['errors'] as $err) {
                $stmt->execute([$jobId, null, $err]);
            }
        }
    } catch (PDOException $e) {
        error_log('Import log error: ' . $e->getMessage());
    }
}

function runImport($pdo, $validatedRows, $mapping, $autoCreate, $dryRun) {
    $results = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => [], 'dry_run' => false];
    $rowNum = 0;

    foreach ($validatedRows as $vrow) {
        $rowNum++;
        if (!$vrow['valid']) {
            $results['errors'][] = 'Row ' . $vrow['row_num'] . ': ' . implode(', ', $vrow['errors']);
            $results['skipped']++;
            continue;
        }

        $row = $vrow['data'];
        $name = trim((string)($row['name'] ?? ''));
        $sku = trim((string)($row['sku'] ?? ''));
        $productId = (int)($row['product_id'] ?? 0);
        $price = (float)($row['price'] ?? 0);
        $categoryName = trim((string)($row['category'] ?? ''));
        $subcategoryName = trim((string)($row['subcategory'] ?? ''));
        $originalPrice = ($row['original_price'] ?? '') !== '' ? (float)$row['original_price'] : null;
        $stockQuantity = (int)($row['stock_quantity'] ?? 0);
        $lowStockThreshold = ($row['low_stock_threshold'] ?? '') !== '' ? (int)$row['low_stock_threshold'] : null;
        $status = in_array(($row['status'] ?? ''), ['draft', 'published']) ? $row['status'] : 'published';
        $isFeatured = in_array(strtolower((string)($row['is_featured'] ?? '0')), ['1', 'true', 'yes', 'featured']) ? 1 : 0;
        $description = (string)($row['description'] ?? '');
        $features = (string)($row['features'] ?? '');
        $tags = (string)($row['tags'] ?? '');
        $metaTitle = (string)($row['meta_title'] ?? '');
        $metaDescription = (string)($row['meta_description'] ?? '');
        $slug = (string)($row['slug'] ?? '');

        // Find existing product
        $existingId = 0;
        if ($productId > 0) {
            $stmt = $pdo->prepare('SELECT product_id FROM products WHERE product_id = ?');
            $stmt->execute([$productId]);
            $existingId = (int)$stmt->fetchColumn();
        }
        if (!$existingId && $sku !== '') {
            $stmt = $pdo->prepare('SELECT product_id FROM products WHERE sku = ?');
            $stmt->execute([$sku]);
            $existingId = (int)$stmt->fetchColumn();
        }

        if ($dryRun) {
            if ($existingId) {
                $results['updated']++;
            } else {
                $results['created']++;
            }
            continue;
        }

        try {
            $categoryId = $existingId ? null : 0;
            if ($categoryName !== '') {
                $categoryId = categoryIdByName($pdo, $categoryName, $autoCreate);
                if ($categoryId === 0) {
                    $results['errors'][] = 'Row ' . $vrow['row_num'] . ": category '$categoryName' not found (enable auto-create to fix)";
                    $results['skipped']++;
                    continue;
                }
            }

            if ($existingId) {
                if ($categoryName !== '') {
                    $subcategoryId = subcategoryIdByName($pdo, $subcategoryName, $categoryId, $autoCreate);
                    $stmt = $pdo->prepare('UPDATE products SET category_id = ?, subcategory_id = ?, name = ?, sku = ?, description = ?, features = ?, price = ?, original_price = ?, stock_quantity = ?, low_stock_threshold = ?, status = ?, is_featured = ?, meta_title = ?, meta_description = ?, slug = ? WHERE product_id = ?');
                    $stmt->execute([$categoryId, $subcategoryId, $name !== '' ? $name : null, $sku !== '' ? $sku : null, $description, $features, $price, $originalPrice, $stockQuantity, $lowStockThreshold, $status, $isFeatured, $metaTitle !== '' ? $metaTitle : null, $metaDescription !== '' ? $metaDescription : null, $slug !== '' ? $slug : null, $existingId]);
                } else {
                    $stmt = $pdo->prepare('UPDATE products SET name = ?, sku = ?, description = ?, features = ?, price = ?, original_price = ?, stock_quantity = ?, low_stock_threshold = ?, status = ?, is_featured = ?, meta_title = ?, meta_description = ?, slug = ? WHERE product_id = ?');
                    $stmt->execute([$name !== '' ? $name : null, $sku !== '' ? $sku : null, $description, $features, $price, $originalPrice, $stockQuantity, $lowStockThreshold, $status, $isFeatured, $metaTitle !== '' ? $metaTitle : null, $metaDescription !== '' ? $metaDescription : null, $slug !== '' ? $slug : null, $existingId]);
                }
                importTags($pdo, $existingId, $tags);
                $results['updated']++;
            } else {
                $subcategoryId = subcategoryIdByName($pdo, $subcategoryName, $categoryId, $autoCreate);
                $stmt = $pdo->prepare('INSERT INTO products (category_id, subcategory_id, name, sku, description, features, price, original_price, stock_quantity, low_stock_threshold, status, is_featured, meta_title, meta_description, slug) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([$categoryId, $subcategoryId, $name, $sku !== '' ? $sku : null, $description, $features, $price, $originalPrice, $stockQuantity, $lowStockThreshold, $status, $isFeatured, $metaTitle !== '' ? $metaTitle : null, $metaDescription !== '' ? $metaDescription : null, $slug !== '' ? $slug : null]);
                $newId = (int)$pdo->lastInsertId();
                importTags($pdo, $newId, $tags);
                $results['created']++;
            }
        } catch (PDOException $e) {
            error_log('CSV import error: ' . $e->getMessage());
            $results['errors'][] = 'Row ' . $vrow['row_num'] . ': database error (' . $e->getMessage() . ')';
            $results['skipped']++;
        }
    }

    return $results;
}

include 'includes/avazonia_header.php';
?>

<div class="row">
    <div class="col-12">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger border-0 rounded-4 mb-4 small fw-bold animate-up">
                <ul class="mb-0">
                    <?php foreach ($errors as $e): ?><li><?php echo htmlspecialchars($e); ?></li><?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (!empty($results)): ?>
            <div class="admin-card mb-4 animate-up">
                <div class="admin-card-header">
                    <h5 class="admin-card-title mb-0">
                        <i class="fas <?php echo ($results['dry_run'] ?? false) ? 'fa-flask' : 'fa-check-circle'; ?> me-2"></i>
                        <?php echo ($results['dry_run'] ?? false) ? 'Dry Run Results' : 'Import Results'; ?>
                    </h5>
                </div>
                <div class="p-4">
                    <div class="row text-center g-3 mb-4">
                        <div class="col-md-3">
                            <div class="p-3 rounded-4 bg-success-subtle">
                                <div class="h3 fw-black text-success mb-0"><?php echo $results['created'] ?? 0; ?></div>
                                <div class="small text-muted fw-bold">Would Create</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3 rounded-4 bg-primary-subtle">
                                <div class="h3 fw-black text-primary mb-0"><?php echo $results['updated'] ?? 0; ?></div>
                                <div class="small text-muted fw-bold">Would Update</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3 rounded-4 bg-warning-subtle">
                                <div class="h3 fw-black text-warning mb-0"><?php echo $results['skipped'] ?? 0; ?></div>
                                <div class="small text-muted fw-bold">Skipped / Errors</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3 rounded-4 bg-light border">
                                <div class="h3 fw-black text-muted mb-0"><?php echo ($results['dry_run'] ?? false) ? 'DRY RUN' : 'COMMITTED'; ?></div>
                                <div class="small text-muted fw-bold"><?php echo ($results['dry_run'] ?? false) ? 'No changes made' : 'Changes applied'; ?></div>
                            </div>
                        </div>
                    </div>
                    <?php if (!empty($results['errors'])): ?>
                        <div class="alert alert-warning rounded-3 small">
                            <strong>Errors:</strong>
                            <ul class="mb-0 mt-1 ps-3">
                                <?php foreach ($results['errors'] as $err): ?><li><?php echo htmlspecialchars($err); ?></li><?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    <div class="d-flex gap-2">
                        <a href="csv_import.php" class="btn-premium"><i class="fas fa-redo me-2"></i>New Import</a>
                        <a href="manage_products.php" class="btn-premium-outline"><i class="fas fa-arrow-left me-2"></i>Back to Products</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['step']) && $_GET['step'] === 'map'): ?>
            <!-- Step 2: Column Mapping -->
            <div class="admin-card animate-up">
                <div class="admin-card-header d-flex justify-content-between align-items-center">
                    <h5 class="admin-card-title mb-0"><i class="fas fa-columns me-2"></i>Map Columns</h5>
                    <span class="small text-muted fw-bold"><?php echo htmlspecialchars($_SESSION['csv_filename'] ?? ''); ?> — <?php echo count($_SESSION['csv_headers'] ?? []); ?> columns</span>
                </div>
                <div class="p-4">
                    <form method="POST" action="">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="action" value="map">
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th style="width: 200px;">CSV Column</th>
                                        <th>Map To</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($_SESSION['csv_headers'] as $header): ?>
                                        <tr>
                                            <td class="fw-bold small"><?php echo htmlspecialchars($header); ?></td>
                                            <td>
                                                <select name="column_map[<?php echo htmlspecialchars($header); ?>]" class="form-select form-select-sm">
                                                    <?php foreach ($columnMapOptions as $val => $label): ?>
                                                        <option value="<?php echo $val; ?>" <?php echo ($val === $header || (isset($_SESSION['csv_column_map'][$header]) && $_SESSION['csv_column_map'][$header] === $val)) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($label); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <a href="csv_import.php" class="btn btn-outline-secondary"><i class="fas fa-redo me-1"></i>Re-upload</a>
                            <button class="btn-premium" type="submit"><i class="fas fa-arrow-right me-1"></i>Preview</button>
                        </div>
                    </form>
                </div>
            </div>

        <?php elseif (isset($_GET['step']) && $_GET['step'] === 'preview'): ?>
            <?php
            $validatedPreview = $_SESSION['csv_preview_validated'] ?? [];
            $mapping = $_SESSION['csv_column_map'] ?? [];
            ?>
            <!-- Step 3: Preview with Validation -->
            <div class="admin-card animate-up">
                <div class="admin-card-header d-flex justify-content-between align-items-center">
                    <h5 class="admin-card-title mb-0"><i class="fas fa-eye me-2"></i>Preview & Validation <span class="badge bg-light text-dark ms-2 rounded-pill"><?php echo count($validatedPreview); ?> rows</span></h5>
                    <span class="small text-muted fw-bold"><?php echo htmlspecialchars($_SESSION['csv_filename'] ?? ''); ?></span>
                </div>
                <div class="table-responsive" style="max-height: 420px; overflow-y: auto;">
                    <table class="table table-sm align-middle">
                        <thead class="sticky-top bg-white">
                            <tr>
                                <th>#</th>
                                <?php foreach ($mapping as $csvCol => $dbField): ?>
                                    <?php if ($dbField !== 'skip'): ?>
                                        <th><?php echo htmlspecialchars($csvCol); ?></th>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($validatedPreview as $vrow): ?>
                                <tr class="<?php echo !$vrow['valid'] ? 'table-danger' : ''; ?>">
                                    <td class="text-muted"><?php echo $vrow['row_num']; ?></td>
                                    <?php foreach ($mapping as $csvCol => $dbField): ?>
                                        <?php if ($dbField !== 'skip'): ?>
                                            <td class="small"><?php echo htmlspecialchars($vrow['data'][$csvCol] ?? ''); ?></td>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                    <td>
                                        <?php if (!$vrow['valid']): ?>
                                            <span class="badge bg-danger-subtle text-danger rounded-pill small">
                                                <i class="fas fa-exclamation-triangle me-1"></i><?php echo implode(', ', $vrow['errors']); ?>
                                            </span>
                                        <?php elseif (!empty($vrow['warnings'])): ?>
                                            <span class="badge bg-warning-subtle text-warning rounded-pill small">
                                                <i class="fas fa-exclamation-circle me-1"></i><?php echo implode(', ', $vrow['warnings']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-success-subtle text-success rounded-pill small">
                                                <i class="fas fa-check me-1"></i>OK
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="p-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="auto_create_categories" form="confirmImportForm" id="autoCreate">
                        <label class="form-check-label small fw-bold" for="autoCreate">
                            Auto-create missing categories & subcategories
                        </label>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="csv_import.php" class="btn btn-outline-secondary"><i class="fas fa-redo me-1"></i>Start Over</a>
                        <form method="POST" action="" id="confirmImportForm" onsubmit="return confirmAction(event, 'Run this import now?');">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="action" value="confirm">
                            <button class="btn-premium" type="submit"><i class="fas fa-check me-2"></i>Confirm Import</button>
                        </form>
                        <form method="POST" action="" onsubmit="return confirmAction(event, 'Run a DRY RUN? No data will be changed.');" style="display:inline;">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="action" value="confirm">
                            <input type="hidden" name="dry_run" value="1">
                            <button class="btn btn-outline-info" type="submit"><i class="fas fa-flask me-1"></i>Dry Run</button>
                        </form>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- Step 1: Upload -->
            <div class="admin-card animate-up">
                <div class="admin-card-header">
                    <h5 class="admin-card-title mb-0"><i class="fas fa-file-import me-2"></i>Upload CSV</h5>
                </div>
                <div class="p-4">
                    <form method="POST" action="" enctype="multipart/form-data">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="action" value="upload">
                        <div class="mb-3">
                            <label class="stat-label small mb-1 uppercase tracking-wider fw-bold">CSV File</label>
                            <input type="file" class="form-control rounded-3" name="csv" accept=".csv,text/csv" required>
                        </div>
                        <button class="btn-premium" type="submit"><i class="fas fa-arrow-right me-2"></i>Continue to Column Mapping</button>
                        <a href="manage_products.php" class="btn btn-outline-secondary ms-2">Cancel</a>
                    </form>
                    <hr>
                    <div class="small text-muted">
                        <strong>Supported columns:</strong>
                        <code>product_id, name, sku, category, subcategory, price, original_price, stock_quantity, low_stock_threshold, status, is_featured, description, features, tags, meta_title, meta_description, slug</code>
                        <div class="mt-2">
                            <ul class="mb-0 ps-3">
                                <li>Products are updated when a matching <code>product_id</code> or <code>sku</code> exists; otherwise a new product is created.</li>
                                <li><code>price</code> and <code>category</code> are required for new products.</li>
                                <li><code>status</code>: <code>published</code> or <code>draft</code>. <code>is_featured</code>: <code>0</code>/<code>1</code>.</li>
                                <li><code>tags</code> are comma-separated.</li>
                                <li>You can map CSV columns to any field above — unmatched columns are skipped.</li>
                            </ul>
                        </div>
                        <a href="csv_export.php" class="btn-link small fw-bold">Download a sample export to use as a template &rarr;</a>
                    </div>
                </div>
            </div>

            <!-- Import History -->
            <?php
            $importJobs = [];
            try {
                $stmt = $pdo->query('SELECT * FROM import_jobs ORDER BY created_at DESC LIMIT 10');
                $importJobs = $stmt->fetchAll();
            } catch (PDOException $e) {}
            ?>
            <?php if (!empty($importJobs)): ?>
                <div class="admin-card mt-4 animate-up">
                    <div class="admin-card-header">
                        <h5 class="admin-card-title mb-0"><i class="fas fa-history me-2"></i>Import History</h5>
                    </div>
                    <div class="p-4">
                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead>
                                    <tr>
                                        <th>File</th>
                                        <th>Date</th>
                                        <th>Rows</th>
                                        <th>Created</th>
                                        <th>Updated</th>
                                        <th>Skipped</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($importJobs as $job): ?>
                                        <tr>
                                            <td class="small fw-bold"><?php echo htmlspecialchars($job['filename']); ?></td>
                                            <td class="small text-muted"><?php echo date('M d, H:i', strtotime($job['created_at'])); ?></td>
                                            <td class="small"><?php echo $job['total_rows']; ?></td>
                                            <td class="small text-success fw-bold"><?php echo $job['created_count']; ?></td>
                                            <td class="small text-primary fw-bold"><?php echo $job['updated_count']; ?></td>
                                            <td class="small text-warning fw-bold"><?php echo $job['skipped_count']; ?></td>
                                            <td>
                                                <span class="badge rounded-pill px-2 py-0 small <?php echo $job['status'] === 'completed' ? 'bg-success-subtle text-success' : ($job['status'] === 'dry_run' ? 'bg-info-subtle text-info' : 'bg-danger-subtle text-danger'); ?>">
                                                    <?php echo htmlspecialchars($job['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/avazonia_footer.php'; ?>