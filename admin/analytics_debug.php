<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Include database connection
require_once '../includes/db.php';

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    die('Access denied. Please login as admin.');
}

// Function to run and display query results
function runQuery($pdo, $sql) {
    try {
        $stmt = $pdo->query($sql);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return [
            'success' => true,
            'data' => $results,
            'count' => count($results)
        ];
    } catch (PDOException $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

// Queries to run
$queries = [
    'orders_count' => "SELECT COUNT(*) as count FROM orders",
    'recent_orders' => "SELECT * FROM orders ORDER BY order_date DESC LIMIT 5",
    'monthly_sales' => "SELECT 
                        DATE_FORMAT(order_date, '%Y-%m') as month,
                        COUNT(*) as order_count,
                        COALESCE(SUM(total_amount), 0) as revenue
                      FROM orders 
                      WHERE order_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                      AND status != 'cancelled'
                      GROUP BY DATE_FORMAT(order_date, '%Y-%m')
                      ORDER BY month",
    'payment_methods' => "SELECT 
                            payment_method,
                            COUNT(*) as count,
                            COALESCE(SUM(total_amount), 0) as amount
                          FROM orders 
                          GROUP BY payment_method"
];

// Run all queries
$results = [];
foreach ($queries as $name => $sql) {
    $results[$name] = runQuery($pdo, $sql);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Debug</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .query-result {
            margin-bottom: 2rem;
            border: 1px solid #dee2e6;
            border-radius: 0.25rem;
            padding: 1rem;
        }
        .query-result h3 {
            margin-top: 0;
            color: #0d6efd;
        }
        table {
            width: 100%;
            margin-bottom: 1rem;
            color: #212529;
        }
        table th, table td {
            padding: 0.5rem;
            vertical-align: top;
            border-top: 1px solid #dee2e6;
        }
        table thead th {
            vertical-align: bottom;
            border-bottom: 2px solid #dee2e6;
            background-color: #f8f9fa;
        }
        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border: 1px solid transparent;
            border-radius: 0.25rem;
        }
        .alert-success {
            color: #0f5132;
            background-color: #d1e7dd;
            border-color: #badbcc;
        }
        .alert-danger {
            color: #842029;
            background-color: #f8d7da;
            border-color: #f5c2c7;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <h1 class="mb-4">Analytics Debug</h1>
        
        <div class="alert alert-info">
            <h4 class="alert-heading">Database Connection</h4>
            <?php if (isset($pdo)): ?>
                <p class="mb-0">✓ Successfully connected to the database.</p>
            <?php else: ?>
                <p class="mb-0 text-danger">✗ Failed to connect to the database.</p>
            <?php endif; ?>
        </div>
        
        <?php foreach ($results as $name => $result): ?>
            <div class="query-result">
                <h3><?php echo ucfirst(str_replace('_', ' ', $name)); ?></h3>
                <p><strong>Query:</strong> <code><?php echo htmlspecialchars($queries[$name]); ?></code></p>
                
                <?php if ($result['success']): ?>
                    <?php if (count($result['data']) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <?php foreach (array_keys($result['data'][0]) as $column): ?>
                                            <th><?php echo htmlspecialchars($column); ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($result['data'] as $row): ?>
                                        <tr>
                                            <?php foreach ($row as $value): ?>
                                                <td><?php echo htmlspecialchars($value); ?></td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <p>Total rows: <?php echo $result['count']; ?></p>
                    <?php else: ?>
                        <div class="alert alert-warning">No results found.</div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="alert alert-danger">
                        <strong>Error:</strong> <?php echo htmlspecialchars($result['error']); ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        
        <div class="query-result">
            <h3>Table Structure</h3>
            <?php 
            $tables = ['orders', 'order_items', 'users'];
            foreach ($tables as $table): 
                $columns = runQuery($pdo, "SHOW COLUMNS FROM $table");
            ?>
                <h4><?php echo $table; ?></h4>
                <?php if ($columns['success'] && count($columns['data']) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Field</th>
                                    <th>Type</th>
                                    <th>Null</th>
                                    <th>Key</th>
                                    <th>Default</th>
                                    <th>Extra</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($columns['data'] as $column): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($column['Field']); ?></td>
                                        <td><?php echo htmlspecialchars($column['Type']); ?></td>
                                        <td><?php echo htmlspecialchars($column['Null']); ?></td>
                                        <td><?php echo htmlspecialchars($column['Key']); ?></td>
                                        <td><?php echo htmlspecialchars($column['Default'] ?? 'NULL'); ?></td>
                                        <td><?php echo htmlspecialchars($column['Extra']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-danger">
                        Failed to get columns for <?php echo $table; ?>: 
                        <?php echo htmlspecialchars($columns['error'] ?? 'Unknown error'); ?>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
