<?php
/**
 * ASO Online Market - Environment Diagnostics
 * This script checks if the local environment is correctly configured.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$results = [];

// 1. Check PHP Version
$results['php_version'] = [
    'title' => 'PHP Version',
    'status' => version_compare(PHP_VERSION, '8.0.0', '>=') ? 'success' : 'warning',
    'message' => 'Current version: ' . PHP_VERSION . ' (Recommended: 8.0+)'
];

// 2. Check Extensions
$required_extensions = ['pdo', 'pdo_mysql', 'curl', 'mbstring', 'openssl'];
$missing_extensions = [];
foreach ($required_extensions as $ext) {
    if (!extension_loaded($ext)) {
        $missing_extensions[] = $ext;
    }
}
$results['extensions'] = [
    'title' => 'Required Extensions',
    'status' => empty($missing_extensions) ? 'success' : 'error',
    'message' => empty($missing_extensions) ? 'All required extensions loaded.' : 'Missing extensions: ' . implode(', ', $missing_extensions)
];

// 3. Check .env file
$env_exists = file_exists('.env');
$results['env_file'] = [
    'title' => '.env Configuration',
    'status' => $env_exists ? 'success' : 'error',
    'message' => $env_exists ? '.env file found.' : '.env file missing! Copy .env.example to .env'
];

// 4. Check Database Connection
if ($env_exists) {
    require_once 'includes/db.php';
    if (isset($pdo) && $pdo !== null) {
        $results['db_connection'] = [
            'title' => 'Database Connection',
            'status' => 'success',
            'message' => 'Successfully connected to the database.'
        ];

        // 5. Check Tables
        $required_tables = ['users', 'products', 'categories', 'orders', 'order_items'];
        try {
            $existing_tables = [];
            $stmt = $pdo->query("SHOW TABLES");
            while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
                $existing_tables[] = $row[0];
            }
            
            $missing_tables = array_diff($required_tables, $existing_tables);
            $results['db_tables'] = [
                'title' => 'Database Tables',
                'status' => empty($missing_tables) ? 'success' : 'error',
                'message' => empty($missing_tables) ? 'All core tables exist.' : 'Missing tables: ' . implode(', ', $missing_tables) . '. Run master_setup.sql'
            ];
        } catch (Exception $e) {
            $results['db_tables'] = [
                'title' => 'Database Tables',
                'status' => 'error',
                'message' => 'Could not check tables: ' . $e->getMessage()
            ];
        }
    } else {
        $results['db_connection'] = [
            'title' => 'Database Connection',
            'status' => 'error',
            'message' => 'Database connection failed. Check .env credentials.'
        ];
    }
}

// 6. Check Directories
$log_dir = 'logs';
if (!file_exists($log_dir)) {
    @mkdir($log_dir, 0755, true);
}
$results['log_dir'] = [
    'title' => 'Logs Directory',
    'status' => is_writable($log_dir) ? 'success' : 'warning',
    'message' => is_writable($log_dir) ? 'Logs directory is writable.' : 'Logs directory is not writable.'
];

// 7. Check SITE_URL
if (defined('SITE_URL')) {
    $results['site_url'] = [
        'title' => 'Site URL',
        'status' => 'info',
        'message' => 'Configured URL: ' . SITE_URL
    ];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ASO Online Market - Diagnostics</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; padding: 40px 0; }
        .card { border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .status-success { color: #198754; font-weight: bold; }
        .status-error { color: #dc3545; font-weight: bold; }
        .status-warning { color: #ffc107; font-weight: bold; }
        .status-info { color: #0dcaf0; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card p-4">
                    <h2 class="mb-4 text-center">Environment Diagnostics</h2>
                    <p class="text-muted text-center mb-4">Checking your local setup for ASO Online Market</p>
                    
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Check</th>
                                    <th>Status</th>
                                    <th>Message</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($results as $key => $res): ?>
                                <tr>
                                    <td><strong><?php echo $res['title']; ?></strong></td>
                                    <td class="status-<?php echo $res['status']; ?>">
                                        <?php 
                                            if ($res['status'] == 'success') echo '✓ OK';
                                            elseif ($res['status'] == 'error') echo '✗ Failed';
                                            elseif ($res['status'] == 'warning') echo '⚠ Warning';
                                            else echo 'ℹ Info';
                                        ?>
                                    </td>
                                    <td><?php echo $res['message']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-4 text-center">
                        <a href="index.php" class="btn btn-primary">Go to Home</a>
                        <a href="setup_database.php" class="btn btn-outline-secondary">Database Setup Guide</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
