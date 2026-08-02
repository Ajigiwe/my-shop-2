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

// Handle Sample CSV Download
if (isset($_GET['action']) && $_GET['action'] === 'download_sample') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=sample_products_import.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['sku', 'name', 'category', 'subcategory', 'price', 'original_price', 'stock_quantity', 'status', 'description', 'image']);
    fputcsv($output, ['SKU-101', 'Sample Wireless Headphones', 'Electronics', 'Audio', '49.99', '69.99', '50', 'published', 'High quality bluetooth audio headphones', 'headphones.jpg']);
    fputcsv($output, ['SKU-102', 'Ergonomic Office Chair', 'Furniture', 'Chairs', '129.00', '150.00', '15', 'published', 'Comfortable mesh swivel chair', 'office_chair.jpg']);
    fclose($output);
    exit();
}

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
    'image' => 'Image Filename (e.g., photo.jpg)',
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
    if (empty($_FILES['csv']['tmp_name'])) {
        $errors[] = 'Please choose a CSV file to upload';
    } else {
        $parsed = parseCsvFile($_FILES['csv']['tmp_name']);
        if (empty($parsed)) {
            $errors[] = 'No valid rows found. Check the file and try again.';
        } else {
            // Process optional ZIP images archive
            if (!empty($_FILES['zip_images']['tmp_name']) && class_exists('ZipArchive')) {
                $zip = new ZipArchive();
                if ($zip->open($_FILES['zip_images']['tmp_name']) === TRUE) {
                    $targetDir = realpath(__DIR__ . '/../assets/images/');
                    if ($targetDir) {
                        for ($i = 0; $i < $zip->numFiles; $i++) {
                            $filename = $zip->getNameIndex($i);
                            // Avoid subdirectories / hidden files
                            $basename = basename($filename);
                            if ($basename && strpos($basename, '.') !== 0) {
                                $ext = strtolower(pathinfo($basename, PATHINFO_EXTENSION));
                                if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'])) {
                                    copy('zip://' . $_FILES['zip_images']['tmp_name'] . '#' . $filename, $targetDir . DIRECTORY_SEPARATOR . $basename);
                                }
                            }
                        }
                    }
                    $zip->close();
                }
            }

            $_SESSION['csv_headers'] = array_keys($parsed[0]);
            $_SESSION['csv_rows'] = $parsed;
            $_SESSION['csv_filename'] = $_FILES['csv']['name'];
            header('Location: csv_import.php?step=map');
            exit();
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
        $columnMapping = $_POST['column_map'] ?? [];
        $_SESSION['csv_column_map'] = $columnMapping;
        header('Location: csv_import.php?step=preview');
        exit();
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
    $_SESSION['csv_preview_validated'] = $preview;
}

// ------------------------------------------------------------------
// Step 4: Confirm + import
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'confirm') {
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

// ------------------------------------------------------------------
// Helpers
// ------------------------------------------------------------------
function parseXlsxFile($path) {
    if (!class_exists('ZipArchive')) return [];
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) return [];

    $sharedStrings = [];
    $ssData = $zip->getFromName('xl/sharedStrings.xml');
    if ($ssData) {
        $xml = @simplexml_load_string($ssData);
        if ($xml && isset($xml->si)) {
            foreach ($xml->si as $val) {
                if (isset($val->t)) {
                    $sharedStrings[] = (string)$val->t;
                } elseif (isset($val->r)) {
                    $t = '';
                    foreach ($val->r as $r) {
                        $t .= (string)$r->t;
                    }
                    $sharedStrings[] = $t;
                } else {
                    $sharedStrings[] = '';
                }
            }
        }
    }

    $sheetData = $zip->getFromName('xl/worksheets/sheet1.xml');
    $zip->close();
    if (!$sheetData) return [];

    $xml = @simplexml_load_string($sheetData);
    if (!$xml || !isset($xml->sheetData->row)) return [];

    $rawRows = [];
    foreach ($xml->sheetData->row as $r) {
        $rowCells = [];
        foreach ($r->c as $c) {
            $type = (string)$c['t'];
            $val = (string)$c->v;
            if ($type === 's' && isset($sharedStrings[(int)$val])) {
                $val = $sharedStrings[(int)$val];
            }
            $rowCells[] = trim($val);
        }
        if (!empty($rowCells)) {
            $rawRows[] = $rowCells;
        }
    }

    if (empty($rawRows)) return [];

    $headers = array_shift($rawRows);
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
    foreach ($rawRows as $line) {
        $row = [];
        foreach ($map as $i => $key) {
            $row[$key] = $line[$i] ?? '';
        }
        if (trim((string)($row['name'] ?? '')) === '' && trim((string)($row['sku'] ?? '')) === '' && trim((string)($row['product_id'] ?? '')) === '') {
            continue;
        }
        $rows[] = $row;
    }
    return $rows;
}

function parseCsvFile($path) {
    if (!file_exists($path)) return [];

    // Detect if file is XLSX (ZIP archive with PK magic header)
    $handle = fopen($path, 'rb');
    if ($handle) {
        $magic = fread($handle, 4);
        fclose($handle);
        if ($magic === "PK\x03\x04") {
            return parseXlsxFile($path);
        }
    }

    $content = file_get_contents($path);
    if (!$content) return [];

    // Strip UTF-8 BOM
    $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

    // Auto-detect delimiter from first line
    $lines = preg_split('/\r\n|\r|\n/', $content);
    $firstLine = $lines[0] ?? '';
    
    $delimiters = [',' => 0, ';' => 0, "\t" => 0, '|' => 0];
    foreach ($delimiters as $delim => &$count) {
        $count = substr_count($firstLine, $delim);
    }
    arsort($delimiters);
    $delimiter = key($delimiters) ?: ',';

    $handle = fopen($path, 'r');
    if (!$handle) return [];

    $rawRows = [];
    while (($line = fgetcsv($handle, 0, $delimiter)) !== false) {
        if (!empty($line[0])) {
            $line[0] = preg_replace('/^\xEF\xBB\xBF/', '', $line[0]);
        }
        $rawRows[] = $line;
    }
    fclose($handle);

    if (empty($rawRows)) return [];

    $headers = array_shift($rawRows);
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
    foreach ($rawRows as $line) {
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

        // Apply column mapping if provided
        $mappedData = $row;
        if (!empty($mapping)) {
            foreach ($mapping as $csvCol => $dbField) {
                if ($dbField !== 'skip' && isset($row[$csvCol])) {
                    $mappedData[$dbField] = $row[$csvCol];
                }
            }
        }

        $name = trim((string)($mappedData['name'] ?? ''));
        $sku = trim((string)($mappedData['sku'] ?? ''));
        $productId = (int)($mappedData['product_id'] ?? 0);
        $price = (float)($mappedData['price'] ?? 0);
        $categoryName = trim((string)($mappedData['category'] ?? ''));
        $stockQuantity = (int)($mappedData['stock_quantity'] ?? 0);

        // Validate structure
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
            'data' => $mappedData,
            'raw_data' => $row,
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
        $image = trim((string)($row['image'] ?? ''));

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
                    $stmt = $pdo->prepare('UPDATE products SET category_id = ?, subcategory_id = ?, name = ?, sku = ?, description = ?, features = ?, price = ?, original_price = ?, stock_quantity = ?, low_stock_threshold = ?, status = ?, is_featured = ?, meta_title = ?, meta_description = ?, slug = ?' . ($image !== '' ? ', image = ?' : '') . ' WHERE product_id = ?');
                    $params = [$categoryId, $subcategoryId, $name !== '' ? $name : null, $sku !== '' ? $sku : null, $description, $features, $price, $originalPrice, $stockQuantity, $lowStockThreshold, $status, $isFeatured, $metaTitle !== '' ? $metaTitle : null, $metaDescription !== '' ? $metaDescription : null, $slug !== '' ? $slug : null];
                    if ($image !== '') $params[] = $image;
                    $params[] = $existingId;
                    $stmt->execute($params);
                } else {
                    $stmt = $pdo->prepare('UPDATE products SET name = ?, sku = ?, description = ?, features = ?, price = ?, original_price = ?, stock_quantity = ?, low_stock_threshold = ?, status = ?, is_featured = ?, meta_title = ?, meta_description = ?, slug = ?' . ($image !== '' ? ', image = ?' : '') . ' WHERE product_id = ?');
                    $params = [$name !== '' ? $name : null, $sku !== '' ? $sku : null, $description, $features, $price, $originalPrice, $stockQuantity, $lowStockThreshold, $status, $isFeatured, $metaTitle !== '' ? $metaTitle : null, $metaDescription !== '' ? $metaDescription : null, $slug !== '' ? $slug : null];
                    if ($image !== '') $params[] = $image;
                    $params[] = $existingId;
                    $stmt->execute($params);
                }
                if ($image !== '') {
                    $pdo->prepare('INSERT IGNORE INTO product_images (product_id, image_path, is_primary) VALUES (?, ?, 1)')->execute([$existingId, $image]);
                }
                importTags($pdo, $existingId, $tags);
                $results['updated']++;
            } else {
                $subcategoryId = subcategoryIdByName($pdo, $subcategoryName, $categoryId, $autoCreate);
                $stmt = $pdo->prepare('INSERT INTO products (category_id, subcategory_id, name, sku, description, features, price, original_price, stock_quantity, low_stock_threshold, status, is_featured, meta_title, meta_description, slug, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([$categoryId, $subcategoryId, $name, $sku !== '' ? $sku : null, $description, $features, $price, $originalPrice, $stockQuantity, $lowStockThreshold, $status, $isFeatured, $metaTitle !== '' ? $metaTitle : null, $metaDescription !== '' ? $metaDescription : null, $slug !== '' ? $slug : null, $image !== '' ? $image : 'placeholder.jpg']);
                $newId = (int)$pdo->lastInsertId();
                if ($image !== '') {
                    $pdo->prepare('INSERT IGNORE INTO product_images (product_id, image_path, is_primary) VALUES (?, ?, 1)')->execute([$newId, $image]);
                }
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
    <div class="alert-box alert-error">
        <ul style="margin: 0; padding-left: 20px;">
            <?php foreach ($errors as $e): ?><li><?php echo htmlspecialchars($e); ?></li><?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if (!empty($results)): ?>
    <div class="panel" style="margin-bottom: 40px;">
        <div class="panel-header">
            <div class="panel-title"><?php echo ($results['dry_run'] ?? false) ? 'Dry Run Results' : 'Import Results'; ?></div>
        </div>
        <div style="padding: 24px;">
            <div class="analytics-grid">
                <div class="stat-card-bold">
                    <span class="label">Created</span>
                    <span class="value"><?php echo $results['created'] ?? 0; ?></span>
                    <div style="font-family: var(--f-mono); font-size: 10px; color: var(--mid-gray);">NEW PRODUCTS</div>
                </div>
                <div class="stat-card-bold">
                    <span class="label">Updated</span>
                    <span class="value"><?php echo $results['updated'] ?? 0; ?></span>
                    <div style="font-family: var(--f-mono); font-size: 10px; color: var(--mid-gray);">MATCHED RECORDS</div>
                </div>
                <div class="stat-card-bold">
                    <span class="label">Skipped / Errors</span>
                    <span class="value"><?php echo $results['skipped'] ?? 0; ?></span>
                    <div style="font-family: var(--f-mono); font-size: 10px; color: var(--mid-gray);">BLOCKED ROWS</div>
                </div>
                <div class="stat-card-bold" style="background: var(--ink); color: #fff; border: none;">
                    <span class="label" style="color: rgba(255,255,255,0.6);"><?php echo ($results['dry_run'] ?? false) ? 'Dry Run' : 'Committed'; ?></span>
                    <span class="value" style="font-size: 26px;"><?php echo ($results['dry_run'] ?? false) ? 'NO CHANGES' : 'APPLIED'; ?></span>
                    <div style="font-family: var(--f-mono); font-size: 10px; color: rgba(255,255,255,0.6); margin-top: 8px;"><?php echo ($results['dry_run'] ?? false) ? 'Simulation only' : 'Data written to catalog'; ?></div>
                </div>
            </div>
            <?php if (!empty($results['errors'])): ?>
                <div class="alert-box alert-error">
                    <div>
                        <strong>Errors:</strong>
                        <ul style="margin: 8px 0 0; padding-left: 20px;">
                            <?php foreach ($results['errors'] as $err): ?><li><?php echo htmlspecialchars($err); ?></li><?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>
            <div class="d-flex gap-2">
                <a href="csv_import.php" class="btn-red">New Import</a>
                <a href="manage_products.php" class="btn-ink" style="background: transparent; color: var(--ink); border: 1px solid var(--ink);">Back to Products</a>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if (isset($_GET['step']) && $_GET['step'] === 'map'): ?>
    <!-- Step 2: Column Mapping -->
    <div class="panel">
        <div class="panel-header">
            <div class="panel-title">Map Columns</div>
            <span style="font-family: var(--f-mono); font-size: 11px; color: var(--mid-gray);"><?php echo htmlspecialchars($_SESSION['csv_filename'] ?? ''); ?> — <?php echo count($_SESSION['csv_headers'] ?? []); ?> columns</span>
        </div>
        <div class="panel-body">
            <form method="POST" action="">
                <input type="hidden" name="action" value="map">
                <div class="table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th style="width: 200px;">CSV Column</th>
                                <th>Map To</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($_SESSION['csv_headers'] as $header): ?>
                                <tr>
                                    <td style="font-weight: 700;"><?php echo htmlspecialchars($header); ?></td>
                                    <td>
                                        <select name="column_map[<?php echo htmlspecialchars($header); ?>]" class="field-input" style="width: auto; min-width: 280px; height: 40px; padding: 0 12px;">
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
                <div class="d-flex justify-content-between align-items-center" style="margin-top: 24px;">
                    <a href="csv_import.php" class="btn-ink" style="background: transparent; color: var(--ink); border: 1px solid var(--ink);">Re-upload</a>
                    <button class="btn-red" type="submit">Preview</button>
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
    <div class="panel">
        <div class="panel-header">
            <div class="panel-title">Preview &amp; Validation <span style="opacity: 0.4;">(<?php echo count($validatedPreview); ?> rows)</span></div>
            <span style="font-family: var(--f-mono); font-size: 11px; color: var(--mid-gray);"><?php echo htmlspecialchars($_SESSION['csv_filename'] ?? ''); ?></span>
        </div>
        <div class="table-container" style="border: none; margin-bottom: 0; border-radius: 0; max-height: 420px; overflow-y: auto;">
            <table class="admin-table">
                <thead style="position: sticky; top: 0; background: #fff;">
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
                        <tr style="<?php echo !$vrow['valid'] ? 'background: #fff1f0;' : ''; ?>">
                            <td style="color: var(--mid-gray);"><?php echo $vrow['row_num']; ?></td>
                            <?php foreach ($mapping as $csvCol => $dbField): ?>
                                <?php if ($dbField !== 'skip'): ?>
                                    <td style="font-size: 12px;"><?php echo htmlspecialchars($vrow['data'][$csvCol] ?? ''); ?></td>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <td>
                                <?php if (!$vrow['valid']): ?>
                                    <span class="status-badge status-suspended"><?php echo implode(', ', $vrow['errors']); ?></span>
                                <?php elseif (!empty($vrow['warnings'])): ?>
                                    <span class="status-badge" style="background: #fff7e6; color: #d48806;"><?php echo implode(', ', $vrow['warnings']); ?></span>
                                <?php else: ?>
                                    <span class="status-badge status-active">OK</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2" style="padding: 24px;">
            <label class="check-row" style="padding-top: 0;">
                <input type="checkbox" name="auto_create_categories" form="confirmImportForm" id="autoCreate" class="field-check" checked>
                <span class="field-label" style="margin: 0;">Auto-create missing categories &amp; subcategories</span>
            </label>
            <div class="d-flex gap-2 flex-wrap">
                <a href="csv_import.php" class="btn-ink" style="background: transparent; color: var(--ink); border: 1px solid var(--ink);">Start Over</a>
                <form method="POST" action="" id="confirmImportForm" onsubmit="return confirmAction(event, 'Run this import now?');">
                    <input type="hidden" name="action" value="confirm">
                    <button class="btn-red" type="submit">Confirm Import</button>
                </form>
                <form method="POST" action="" onsubmit="return confirmAction(event, 'Run a DRY RUN? No data will be changed.');" class="d-inline">
                    <input type="hidden" name="action" value="confirm">
                    <input type="hidden" name="dry_run" value="1">
                    <button class="btn-ink" type="submit" style="background: transparent; color: var(--ink); border: 1px solid var(--ink);">Dry Run</button>
                </form>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- Step 1: Upload -->
    <div class="panel">
        <div class="panel-header">
            <div class="panel-title">Upload CSV</div>
            <a href="csv_import.php?action=download_sample" class="action-btn">Download Sample CSV Template</a>
        </div>
        <div class="panel-body">
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="action" value="upload">
                <div class="field-grid">
                    <div class="field-group">
                        <label class="field-label">Excel (.xlsx) or CSV File (Required)</label>
                        <input type="file" class="file-input" name="csv" accept=".csv,.xlsx,.xls,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel" required>
                    </div>
                    <div class="field-group">
                        <label class="field-label">Product Images ZIP (Optional)</label>
                        <input type="file" class="file-input" name="zip_images" accept=".zip">
                        <span class="field-sub">Upload a ZIP containing product image files</span>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn-red" type="submit">Continue to Column Mapping</button>
                    <a href="manage_products.php" class="btn-ink" style="background: transparent; color: var(--ink); border: 1px solid var(--ink);">Cancel</a>
                </div>
            </form>
            <hr style="border: none; border-top: 1px solid var(--light-gray); margin: 24px 0;">
            <div style="font-size: 12px; color: var(--mid-gray);">
                <strong style="color: var(--ink);">Supported columns:</strong>
                <code style="font-family: var(--f-mono); font-size: 11px; display: block; margin-top: 8px;">product_id, name, sku, category, subcategory, price, original_price, stock_quantity, low_stock_threshold, status, is_featured, image, description, features, tags, meta_title, meta_description, slug</code>
                <ul style="margin: 12px 0 0; padding-left: 20px; line-height: 1.8;">
                    <li>Products are updated when a matching <code style="font-family: var(--f-mono); font-size: 11px;">product_id</code> or <code style="font-family: var(--f-mono); font-size: 11px;">sku</code> exists; otherwise a new product is created.</li>
                    <li><code style="font-family: var(--f-mono); font-size: 11px;">price</code> and <code style="font-family: var(--f-mono); font-size: 11px;">category</code> are required for new products.</li>
                    <li><code style="font-family: var(--f-mono); font-size: 11px;">status</code>: <code style="font-family: var(--f-mono); font-size: 11px;">published</code> or <code style="font-family: var(--f-mono); font-size: 11px;">draft</code>. <code style="font-family: var(--f-mono); font-size: 11px;">is_featured</code>: <code style="font-family: var(--f-mono); font-size: 11px;">0</code>/<code style="font-family: var(--f-mono); font-size: 11px;">1</code>.</li>
                    <li><code style="font-family: var(--f-mono); font-size: 11px;">image</code>: Filename matching files in the uploaded ZIP (e.g. <code style="font-family: var(--f-mono); font-size: 11px;">headphone.jpg</code>).</li>
                    <li><code style="font-family: var(--f-mono); font-size: 11px;">tags</code> are comma-separated.</li>
                </ul>
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
        <div class="panel" style="margin-top: 40px;">
            <div class="panel-header"><div class="panel-title">Import History</div></div>
            <div class="table-container" style="border: none; margin-bottom: 0; border-radius: 0;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>File</th>
                            <th>Date</th>
                            <th>Rows</th>
                            <th>Created</th>
                            <th>Updated</th>
                            <th>Skipped</th>
                            <th style="text-align: center;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($importJobs as $job): ?>
                            <tr>
                                <td style="font-weight: 700;"><?php echo htmlspecialchars($job['filename']); ?></td>
                                <td style="color: var(--mid-gray);"><?php echo date('M d, H:i', strtotime($job['created_at'])); ?></td>
                                <td><?php echo $job['total_rows']; ?></td>
                                <td style="font-weight: 700; color: #00a854;"><?php echo $job['created_count']; ?></td>
                                <td style="font-weight: 700;"><?php echo $job['updated_count']; ?></td>
                                <td style="font-weight: 700; color: #d48806;"><?php echo $job['skipped_count']; ?></td>
                                <td style="text-align: center;">
                                    <span class="status-badge <?php echo $job['status'] === 'completed' ? 'status-active' : ($job['status'] === 'dry_run' ? 'status-suspended' : 'status-suspended'); ?>">
                                        <?php echo htmlspecialchars($job['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php include 'includes/avazonia_footer.php'; ?>
