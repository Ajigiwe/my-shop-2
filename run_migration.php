<?php
/**
 * ONE-TIME MIGRATION RUNNER
 * Delete this file after running!
 */
require_once __DIR__ . '/includes/db.php';

$tables = [
    'product_attributes',
    'product_attribute_terms',
    'product_variations',
    'product_variation_images',
    'product_attribute_relations',
    'product_variation_term_relations',
];

echo "<h2>Running Migration...</h2>";
echo "<pre>";

foreach ($tables as $table) {
    try {
        $pdo->query("SELECT 1 FROM `$table` LIMIT 1");
        echo "✓ $table already exists\n";
    } catch (PDOException $e) {
        echo "✗ $table missing — creating...\n";
    }
}

echo "\n</pre>";

// Run the actual migration
include __DIR__ . '/database/migrate_variable_products.php';

echo "<h2>Done!</h2>";
echo "<p><strong>DELETE this file (run_migration.php) now!</strong></p>";
echo "<p><a href='admin/manage_products.php'>Go to Product Manager →</a></p>";
