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

<?php if (!empty($errors)): ?>
    <div class="alert-box alert-error" style="flex-direction: column; align-items: flex-start;">
        <ul style="margin: 0; padding-left: 18px;">
            <?php foreach ($errors as $e): ?><li><?php echo htmlspecialchars($e); ?></li><?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if (!empty($results)): ?>
    <div class="panel animate-up">
        <div class="panel-header">
            <div class="panel-title">
                <i class="fas <?php echo ($results['dry_run'] ?? false) ? 'fa-flask' : 'fa-check-circle'; ?>" style="margin-right: 8px;"></i>
                <?php echo ($results['dry_run'] ?? false) ? 'Dry Run Results' : 'Import Results'; ?>
            </div>
        </div>
        <div class="panel-body">
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 32px;">
                <div style="padding: 20px; border-radius: 4px; background: #f0fdf4; text-align: center;">
                    <div style="font-size: 28px; font-weight: 900; color: #16a34a; margin-bottom: 4px;"><?php echo $results['created'] ?? 0; ?></div>
                    <div style="font-size: 11px; color: var(--mid-gray); font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;">Created</div>
                </div>
                <div style="padding: 20px; border-radius: 4px; background: #f0f4ff; text-align: center;">
                    <div style="font-size: 28px; font-weight: 900; color: #3b82f6; margin-bottom: 4px;"><?php echo $results['updated'] ?? 0; ?></div>
                    <div style="font-size: 11px; color: var(--mid-gray); font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;">Updated</div>
                </div>
                <div style="padding: 20px; border-radius: 4px; background: #fefce8; text-align: center;">
                    <div style="font-size: 28px; font-weight: 900; color: #ca8a04; margin-bottom: 4px;"><?php echo $results['skipped'] ?? 0; ?></div>
                    <div style="font-size: 11px; color: var(--mid-gray); font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;">Skipped</div>
                </div>
                <div style="padding: 20px; border-radius: 4px; background: var(--off); text-align: center;">
                    <div style="font-size: 14px; font-weight: 900; color: var(--ink); margin-bottom: 4px; text-transform: uppercase;"><?php echo ($results['dry_run'] ?? false) ? 'Dry Run' : 'Committed'; ?></div>
                    <div style="font-size: 11px; color: var(--mid-gray); font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;"><?php echo ($results['dry_run'] ?? false) ? 'No changes' : 'Applied'; ?></div>
                </div>
            </div>
            <?php if (!empty($results['errors'])): ?>
                <div class="alert-box alert-error" style="margin-bottom: 16px;">
                    <div style="font-weight: 700;">Errors:</div>
                    <ul style="margin: 4px 0 0; padding-left: 18px;">
                        <?php foreach ($results['errors'] as $err): ?><li style="font-size: 12px;"><?php echo htmlspecialchars($err); ?></li><?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <div style="display: flex; gap: 12px; margin-top: 24px;">
                <a href="csv_import.php" class="btn-premium"><i class="fas fa-redo" style="margin-right: 8px;"></i>New Import</a>
                <a href="manage_products.php" class="btn-premium-outline"><i class="fas fa-arrow-left" style="margin-right: 8px;"></i>Back to Products</a>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if (isset($_GET['step']) && $_GET['step'] === 'map'): ?>
    <div class="panel animate-up">
        <div class="panel-header">
            <div class="panel-title"><i class="fas fa-columns" style="margin-right: 8px;"></i>Map Columns</div>
            <span style="font-size: 12px; color: var(--mid-gray); font-weight: 700;"><?php echo htmlspecialchars($_SESSION['csv_filename'] ?? ''); ?> — <?php echo count($_SESSION['csv_headers'] ?? []); ?> columns</span>
        </div>
        <div class="panel-body">
            <form method="POST" action="">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="map">
                <div class="table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>CSV Column</th>
                                <th>Map To</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($_SESSION['csv_headers'] as $header): ?>
                                <tr>
                                    <td style="font-weight: 700; font-size: 13px;"><?php echo htmlspecialchars($header); ?></td>
                                    <td>
                                        <select name="column_map[<?php echo htmlspecialchars($header); ?>]" class="field-input" style="height: 40px; font-size: 13px;">
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
                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 16px;">
                    <a href="csv_import.php" class="btn-ink" style="font-size: 11px;"><i class="fas fa-redo" style="margin-right: 6px;"></i>Re-upload</a>
                    <button class="btn-premium" type="submit"><i class="fas fa-arrow-right" style="margin-right: 6px;"></i>Preview</button>
                </div>
            </form>
        </div>
    </div>

<?php elseif (isset($_GET['step']) && $_GET['step'] === 'preview'): ?>
    <?php
    $validatedPreview = $_SESSION['csv_preview_validated'] ?? [];
    $mapping = $_SESSION['csv_column_map'] ?? [];
    ?>
    <div class="panel animate-up">
        <div class="panel-header">
            <div class="panel-title"><i class="fas fa-eye" style="margin-right: 8px;"></i>Preview &amp; Validation <span style="font-size: 11px; font-weight: 700; background: var(--off); padding: 2px 10px; border-radius: 99px; margin-left: 8px;"><?php echo count($validatedPreview); ?> rows</span></div>
            <span style="font-size: 12px; color: var(--mid-gray); font-weight: 700;"><?php echo htmlspecialchars($_SESSION['csv_filename'] ?? ''); ?></span>
        </div>
        <div class="table-container" style="max-height: 420px; overflow-y: auto; border: none; border-radius: 0;">
            <table class="admin-table" style="min-width: 600px;">
                <thead style="position: sticky; top: 0; background: #fff; z-index: 1;">
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
                        <tr style="<?php echo !$vrow['valid'] ? 'background: #fffafa;' : ''; ?>">
                            <td style="color: var(--mid-gray);"><?php echo $vrow['row_num']; ?></td>
                            <?php foreach ($mapping as $csvCol => $dbField): ?>
                                <?php if ($dbField !== 'skip'): ?>
                                    <td style="font-size: 12px;"><?php echo htmlspecialchars($vrow['data'][$csvCol] ?? ''); ?></td>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <td>
                                <?php if (!$vrow['valid']): ?>
                                    <span style="display: inline-block; font-size: 10px; font-weight: 700; padding: 3px 10px; border-radius: 99px; background: #fffafa; color: #d32f2f; text-transform: uppercase; letter-spacing: 0.03em;">
                                        <i class="fas fa-exclamation-triangle" style="margin-right: 4px;"></i><?php echo htmlspecialchars(implode(', ', $vrow['errors'])); ?>
                                    </span>
                                <?php elseif (!empty($vrow['warnings'])): ?>
                                    <span style="display: inline-block; font-size: 10px; font-weight: 700; padding: 3px 10px; border-radius: 99px; background: #fefce8; color: #ca8a04; text-transform: uppercase; letter-spacing: 0.03em;">
                                        <i class="fas fa-exclamation-circle" style="margin-right: 4px;"></i><?php echo htmlspecialchars(implode(', ', $vrow['warnings'])); ?>
                                    </span>
                                <?php else: ?>
                                    <span style="display: inline-block; font-size: 10px; font-weight: 700; padding: 3px 10px; border-radius: 99px; background: #f0fdf4; color: #16a34a; text-transform: uppercase; letter-spacing: 0.03em;">
                                        <i class="fas fa-check" style="margin-right: 4px;"></i>OK
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="panel-body" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; border-top: 1px solid var(--light-gray);">
            <div class="check-row" style="padding-top: 0; margin: 0;">
                <input class="field-check" type="checkbox" name="auto_create_categories" form="confirmImportForm" id="autoCreate">
                <label class="field-label" for="autoCreate" style="margin: 0;">Auto-create missing categories &amp; subcategories</label>
            </div>
            <div style="display: flex; gap: 12px;">
                <a href="csv_import.php" class="btn-ink" style="font-size: 11px;"><i class="fas fa-redo" style="margin-right: 6px;"></i>Start Over</a>
                <form method="POST" action="" id="confirmImportForm" onsubmit="return confirmAction(event, 'Run this import now?');" style="display: inline;">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="action" value="confirm">
                    <button class="btn-premium" type="submit"><i class="fas fa-check" style="margin-right: 6px;"></i>Confirm Import</button>
                </form>
                <form method="POST" action="" onsubmit="return confirmAction(event, 'Run a DRY RUN? No data will be changed.');" style="display: inline;">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="action" value="confirm">
                    <input type="hidden" name="dry_run" value="1">
                    <button class="btn-ink" type="submit" style="font-size: 11px;"><i class="fas fa-flask" style="margin-right: 6px;"></i>Dry Run</button>
                </form>
            </div>
        </div>
    </div>

<?php else: ?>
    <div class="panel animate-up">
        <div class="panel-header">
            <div class="panel-title"><i class="fas fa-file-import" style="margin-right: 8px;"></i>Upload CSV</div>
        </div>
        <div class="panel-body">
            <form method="POST" action="" enctype="multipart/form-data">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="upload">
                <div class="field-group">
                    <label class="field-label">CSV File</label>
                    <input type="file" class="field-input" style="padding: 10px 12px; height: auto;" name="csv" accept=".csv,text/csv" required>
                </div>
                <div style="display: flex; gap: 12px; align-items: center;">
                    <button class="btn-premium" type="submit"><i class="fas fa-arrow-right" style="margin-right: 6px;"></i>Continue to Column Mapping</button>
                    <a href="manage_products.php" class="btn-ink" style="font-size: 11px;">Cancel</a>
                </div>
            </form>
            <hr style="margin: 24px 0; border: none; border-top: 1px solid var(--light-gray);">
            <div style="font-size: 12px; color: var(--mid-gray);">
                <strong>Supported columns:</strong>
                <code style="font-size: 11px; background: var(--off); padding: 2px 6px; border-radius: 3px;">product_id, name, sku, category, subcategory, price, original_price, stock_quantity, low_stock_threshold, status, is_featured, description, features, tags, meta_title, meta_description, slug</code>
                <div style="margin-top: 12px;">
                    <ul style="margin: 0; padding-left: 18px;">
                        <li>Products are updated when a matching <code style="font-size: 11px; background: var(--off); padding: 2px 6px; border-radius: 3px;">product_id</code> or <code style="font-size: 11px; background: var(--off); padding: 2px 6px; border-radius: 3px;">sku</code> exists; otherwise a new product is created.</li>
                        <li><code style="font-size: 11px; background: var(--off); padding: 2px 6px; border-radius: 3px;">price</code> and <code style="font-size: 11px; background: var(--off); padding: 2px 6px; border-radius: 3px;">category</code> are required for new products.</li>
                        <li><code style="font-size: 11px; background: var(--off); padding: 2px 6px; border-radius: 3px;">status</code>: <code style="font-size: 11px; background: var(--off); padding: 2px 6px; border-radius: 3px;">published</code> or <code style="font-size: 11px; background: var(--off); padding: 2px 6px; border-radius: 3px;">draft</code>. <code style="font-size: 11px; background: var(--off); padding: 2px 6px; border-radius: 3px;">is_featured</code>: <code style="font-size: 11px; background: var(--off); padding: 2px 6px; border-radius: 3px;">0</code>/<code style="font-size: 11px; background: var(--off); padding: 2px 6px; border-radius: 3px;">1</code>.</li>
                        <li><code style="font-size: 11px; background: var(--off); padding: 2px 6px; border-radius: 3px;">tags</code> are comma-separated.</li>
                        <li>You can map CSV columns to any field above — unmatched columns are skipped.</li>
                    </ul>
                </div>
                <a href="csv_export.php" style="font-size: 12px; font-weight: 700; color: var(--ink); text-decoration: underline; text-underline-offset: 3px;">Download a sample export to use as a template &rarr;</a>
            </div>
        </div>
    </div>

    <?php
    $importJobs = [];
    try {
        $stmt = $pdo->query('SELECT * FROM import_jobs ORDER BY created_at DESC LIMIT 10');
        $importJobs = $stmt->fetchAll();
    } catch (PDOException $e) {}
    ?>
    <?php if (!empty($importJobs)): ?>
        <div class="panel animate-up" style="margin-top: 32px;">
            <div class="panel-header">
                <div class="panel-title"><i class="fas fa-history" style="margin-right: 8px;"></i>Import History</div>
            </div>
            <div class="panel-body">
                <div class="table-container">
                    <table class="admin-table">
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
                                    <td style="font-size: 13px; font-weight: 700;"><?php echo htmlspecialchars($job['filename']); ?></td>
                                    <td style="font-size: 12px; color: var(--mid-gray);"><?php echo date('M d, H:i', strtotime($job['created_at'])); ?></td>
                                    <td style="font-size: 13px;"><?php echo $job['total_rows']; ?></td>
                                    <td style="font-size: 13px; font-weight: 700; color: #16a34a;"><?php echo $job['created_count']; ?></td>
                                    <td style="font-size: 13px; font-weight: 700; color: #3b82f6;"><?php echo $job['updated_count']; ?></td>
                                    <td style="font-size: 13px; font-weight: 700; color: #ca8a04;"><?php echo $job['skipped_count']; ?></td>
                                    <td>
                                        <span style="display: inline-block; font-size: 10px; font-weight: 700; padding: 3px 10px; border-radius: 99px; text-transform: uppercase; letter-spacing: 0.03em; <?php echo $job['status'] === 'completed' ? 'background: #f0fdf4; color: #16a34a;' : ($job['status'] === 'dry_run' ? 'background: #f0f4ff; color: #3b82f6;' : 'background: #fffafa; color: #d32f2f;'); ?>">
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

<?php include 'includes/avazonia_footer.php'; ?>