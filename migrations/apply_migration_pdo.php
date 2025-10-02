<?php
/**
 * Apply database migration for multiple product images (PDO version)
 */

require_once __DIR__ . '/../includes/db.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    echo "Starting migration...\n";
    
    // Set PDO to throw exceptions on errors
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
    
    // 1. Create product_images table
    echo "Creating product_images table...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS product_images (
        image_id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        image_path VARCHAR(255) NOT NULL,
        is_primary BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
        INDEX idx_product_id (product_id)
    )");
    echo "- Created product_images table\n";
    
    // 2. Add columns to products table
    echo "Adding columns to products table...\n";
    try {
        $pdo->exec("ALTER TABLE products ADD COLUMN has_multiple_images BOOLEAN DEFAULT FALSE");
        echo "- Added has_multiple_images column\n";
    } catch (Exception $e) {
        echo "- has_multiple_images column already exists\n";
    }
    
    try {
        $pdo->exec("ALTER TABLE products ADD COLUMN main_image_id INT NULL");
        echo "- Added main_image_id column\n";
    } catch (Exception $e) {
        echo "- main_image_id column already exists\n";
    }
    
    // 3. Add foreign key constraint if it doesn't exist
    echo "Checking for foreign key constraint...\n";
    $stmt = $pdo->query("SELECT COUNT(*) as count 
                         FROM information_schema.TABLE_CONSTRAINTS 
                         WHERE CONSTRAINT_SCHEMA = DATABASE() 
                         AND TABLE_NAME = 'products' 
                         AND CONSTRAINT_NAME = 'fk_main_image'");
    $constraintExists = $stmt->fetch()['count'] > 0;
    
    if (!$constraintExists) {
        try {
            $pdo->exec("ALTER TABLE products 
                       ADD CONSTRAINT fk_main_image 
                       FOREIGN KEY (main_image_id) 
                       REFERENCES product_images(image_id) 
                       ON DELETE SET NULL");
            echo "- Added foreign key constraint\n";
        } catch (Exception $e) {
            echo "- Could not add foreign key: " . $e->getMessage() . "\n";
        }
    } else {
        echo "- Foreign key constraint already exists\n";
    }
    
    // 4. Create the trigger
    echo "Creating trigger...\n";
    try {
        $pdo->exec("DROP TRIGGER IF EXISTS after_product_image_insert");
        $pdo->exec("
        CREATE TRIGGER after_product_image_insert
        AFTER INSERT ON product_images
        FOR EACH ROW
        BEGIN
            -- If this is the first image for the product, set it as primary
            IF (SELECT COUNT(*) FROM product_images WHERE product_id = NEW.product_id) = 1 THEN
                UPDATE product_images 
                SET is_primary = TRUE 
                WHERE image_id = NEW.image_id;
                
                -- Update the products table to point to this image
                UPDATE products 
                SET main_image_id = NEW.image_id, 
                    has_multiple_images = FALSE,
                    image = NEW.image_path
                WHERE product_id = NEW.product_id;
            END IF;
        END");
        echo "- Created trigger after_product_image_insert\n";
    } catch (Exception $e) {
        echo "- Could not create trigger: " . $e->getMessage() . "\n";
    }
    
    // 5. Create the procedure
    echo "Creating stored procedure...\n";
    try {
        $pdo->exec("DROP PROCEDURE IF EXISTS SetPrimaryImage");
        $pdo->exec("
        CREATE PROCEDURE SetPrimaryImage(IN p_product_id INT, IN p_image_id INT)
        BEGIN
            -- Reset all images for this product to not primary
            UPDATE product_images 
            SET is_primary = FALSE 
            WHERE product_id = p_product_id;
            
            -- Set the specified image as primary
            UPDATE product_images 
            SET is_primary = TRUE 
            WHERE image_id = p_image_id AND product_id = p_product_id;
            
            -- Update the products table to point to this image
            UPDATE products 
            SET main_image_id = p_image_id,
                has_multiple_images = TRUE,
                image = (SELECT image_path FROM product_images WHERE image_id = p_image_id)
            WHERE product_id = p_product_id;
        END");
        echo "- Created stored procedure SetPrimaryImage\n";
    } catch (Exception $e) {
        echo "- Could not create stored procedure: " . $e->getMessage() . "\n";
    }
    
    echo "\nMigration completed!\n";
    
} catch (Exception $e) {
    echo "\nError during migration: " . $e->getMessage() . "\n";
    die("Migration failed. See above for details.\n");
}

echo "\nVerifying database structure...\n";

// Verify the tables and columns exist
try {
    // Check product_images table
    $stmt = $pdo->query("SHOW TABLES LIKE 'product_images'");
    if ($stmt->rowCount() > 0) {
        echo "✓ product_images table exists\n";
    } else {
        echo "✗ product_images table does not exist\n";
    }
    
    // Check products table columns
    $columns = [
        'has_multiple_images' => false,
        'main_image_id' => false
    ];
    
    $stmt = $pdo->query("SHOW COLUMNS FROM products");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (isset($columns[$row['Field']])) {
            $columns[$row['Field']] = true;
        }
    }
    
    foreach ($columns as $column => $exists) {
        echo ($exists ? "✓" : "✗") . " Column 'products.$column' " . ($exists ? "exists" : "does not exist") . "\n";
    }
    
} catch (Exception $e) {
    echo "Error verifying database structure: " . $e->getMessage() . "\n";
}

echo "\nDone!\n";
