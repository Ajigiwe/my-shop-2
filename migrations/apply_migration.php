<?php
/**
 * Apply database migration for multiple product images
 */

require_once __DIR__ . '/../includes/db.php';

// Skip authentication check when running from command line
if (php_sapi_name() !== 'cli') {
    session_start();
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        die('Unauthorized access');
    }
}

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // Read the migration file
    $migrationFile = __DIR__ . '/add_multiple_product_images.sql';
    if (!file_exists($migrationFile)) {
        throw new Exception("Migration file not found: " . basename($migrationFile));
    }
    
    $sql = file_get_contents($migrationFile);
    
    // Split the SQL file into individual queries
    $queries = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($queries as $query) {
        if (!empty($query)) {
            $pdo->exec($query);
        }
    }
    
    // Commit transaction
    $pdo->commit();
    
    echo "Migration applied successfully!<br>";
    echo "<a href='/admin/manage_products.php'>Go to Products Management</a>";
    
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    die("Migration failed: " . $e->getMessage());
}
