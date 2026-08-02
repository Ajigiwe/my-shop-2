<?php
require_once __DIR__ . '/../includes/db.php';

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `import_jobs` (
            `job_id` INT AUTO_INCREMENT PRIMARY KEY,
            `filename` VARCHAR(255) NOT NULL,
            `total_rows` INT NOT NULL DEFAULT 0,
            `created_count` INT NOT NULL DEFAULT 0,
            `updated_count` INT NOT NULL DEFAULT 0,
            `skipped_count` INT NOT NULL DEFAULT 0,
            `error_count` INT NOT NULL DEFAULT 0,
            `status` ENUM('completed','failed','dry_run') NOT NULL DEFAULT 'completed',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY `idx_status` (`status`),
            KEY `idx_created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "Table import_jobs created.\n";
} catch (PDOException $e) {
    if ($e->getCode() == '42S01') {
        echo "Table import_jobs already exists, skipping.\n";
    } else {
        echo "Error creating import_jobs: " . $e->getMessage() . "\n";
    }
}

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `import_job_errors` (
            `error_id` INT AUTO_INCREMENT PRIMARY KEY,
            `job_id` INT NOT NULL,
            `row_number` INT NULL,
            `message` TEXT NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`job_id`) REFERENCES `import_jobs`(`job_id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "Table import_job_errors created.\n";
} catch (PDOException $e) {
    if ($e->getCode() == '42S01') {
        echo "Table import_job_errors already exists, skipping.\n";
    } else {
        echo "Error creating import_job_errors: " . $e->getMessage() . "\n";
    }
}

echo "\nMigration complete.\n";