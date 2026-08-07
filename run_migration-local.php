<?php
/**
 * ONE-TIME MIGRATION RUNNER — Made in Ghana local goods section
 * Delete this file after running!
 */
require_once __DIR__ . '/includes/db.php';

header('Content-Type: text/html; charset=utf-8');
echo "<h2>Running 'Made in Ghana' migration...</h2><pre>";

include __DIR__ . '/database/migrate_local_goods.php';

echo "</pre><h2>Done!</h2>";
echo "<p><strong>DELETE this file (run_migration-local.php) now!</strong></p>";
echo "<p><a href='admin/manage_products.php'>Go to Product Manager →</a></p>";
