<?php
/**
 * Apply database migrations
 */

require_once '../includes/db.php';

// Check if user is admin
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die('Unauthorized access');
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
    
    echo "Migration applied successfully!";
    
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    die("Migration failed: " . $e->getMessage());
}
