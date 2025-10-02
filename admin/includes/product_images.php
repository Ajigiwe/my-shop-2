<?php
/**
 * Product Images Handler
 * - Handles upload, delete, and management of product images
 */

require_once __DIR__ . '/../../includes/db.php';

class ProductImages {
    private $pdo;
    private $uploadDir;
    private $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    private $maxFileSize = 5 * 1024 * 1024; // 5MB

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->uploadDir = realpath(__DIR__ . '/../../assets/images/products');
        
        // Create upload directory if it doesn't exist
        if (!is_dir($this->uploadDir)) {
            @mkdir($this->uploadDir, 0777, true);
        }
    }

    /**
     * Upload multiple product images
     */
    public function uploadImages($productId, $files) {
        $uploaded = [];
        
        if (!isset($files['name']) || !is_array($files['name'])) {
            return [];
        }

        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                continue;
            }

            $ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
            if (!in_array($ext, $this->allowedTypes)) {
                continue;
            }

            if ($files['size'][$i] > $this->maxFileSize) {
                continue;
            }

            $newName = 'prod_' . $productId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $dest = $this->uploadDir . DIRECTORY_SEPARATOR . $newName;

            if (move_uploaded_file($files['tmp_name'][$i], $dest)) {
                $uploaded[] = [
                    'product_id' => $productId,
                    'image_path' => 'products/' . $newName,
                    'is_primary' => false
                ];
            }
        }

        if (!empty($uploaded)) {
            $this->saveImages($uploaded);
            
            // If this is the first image, set it as primary
            $imageCount = $this->getImageCount($productId);
            if ($imageCount === count($uploaded)) {
                $this->setPrimaryImage($productId, $uploaded[0]['image_path']);
            }
        }

        return $uploaded;
    }

    /**
     * Save image records to database
     */
    private function saveImages($images) {
        $sql = "INSERT INTO product_images (product_id, image_path, is_primary) VALUES ";
        $values = [];
        $params = [];
        
        foreach ($images as $image) {
            $values[] = "(?, ?, ?)";
            $params[] = $image['product_id'];
            $params[] = $image['image_path'];
            $params[] = $image['is_primary'] ? 1 : 0;
        }
        
        if (!empty($values)) {
            $sql .= implode(", ", $values);
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
        }
    }

    /**
     * Set image as primary
     */
    public function setPrimaryImage($productId, $imagePath) {
        try {
            $this->pdo->beginTransaction();
            
            // Reset all primary flags for this product
            $stmt = $this->pdo->prepare(
                "UPDATE product_images SET is_primary = FALSE WHERE product_id = ?"
            );
            $stmt->execute([$productId]);
            
            // Set the selected image as primary
            $stmt = $this->pdo->prepare(
                "UPDATE product_images SET is_primary = TRUE 
                 WHERE product_id = ? AND image_path = ?"
            );
            $stmt->execute([$productId, $imagePath]);
            
            // Update products table
            $stmt = $this->pdo->prepare(
                "UPDATE products 
                 SET has_multiple_images = TRUE, 
                     main_image_id = (SELECT image_id FROM product_images WHERE product_id = ? AND image_path = ?),
                     image = ?
                 WHERE product_id = ?"
            );
            $stmt->execute([$productId, $imagePath, $imagePath, $productId]);
            
            $this->pdo->commit();
            return true;
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Error setting primary image: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete an image
     */
    public function deleteImage($imageId, $productId) {
        try {
            $this->pdo->beginTransaction();
            
            // Get image info before deleting
            $stmt = $this->pdo->prepare(
                "SELECT image_path, is_primary FROM product_images WHERE image_id = ? AND product_id = ?"
            );
            $stmt->execute([$imageId, $productId]);
            $image = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$image) {
                throw new Exception("Image not found");
            }
            
            // Delete the record
            $stmt = $this->pdo->prepare(
                "DELETE FROM product_images WHERE image_id = ? AND product_id = ?"
            );
            $stmt->execute([$imageId, $productId]);
            
            // If this was the primary image, set a new one
            if ($image['is_primary']) {
                $this->setNewPrimaryImage($productId);
            }
            
            // Delete the file
            $filePath = $this->uploadDir . '/' . basename($image['image_path']);
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
            
            $this->pdo->commit();
            return true;
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Error deleting image: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Set a new primary image when the current one is deleted
     */
    private function setNewPrimaryImage($productId) {
        // Get the first available image
        $stmt = $this->pdo->prepare(
            "SELECT image_path FROM product_images WHERE product_id = ? ORDER BY image_id LIMIT 1"
        );
        $stmt->execute([$productId]);
        $newPrimary = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($newPrimary) {
            $this->setPrimaryImage($productId, $newPrimary['image_path']);
        } else {
            // No images left, update products table
            $stmt = $this->pdo->prepare(
                "UPDATE products 
                 SET has_multiple_images = FALSE, 
                     main_image_id = NULL,
                     image = ''
                 WHERE product_id = ?"
            );
            $stmt->execute([$productId]);
        }
    }
    
    /**
     * Get all images for a product
     */
    public function getProductImages($productId) {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM product_images 
             WHERE product_id = ? 
             ORDER BY is_primary DESC, image_id ASC"
        );
        $stmt->execute([$productId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get the number of images for a product
     */
    public function getImageCount($productId) {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) as count FROM product_images WHERE product_id = ?"
        );
        $stmt->execute([$productId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['count'];
    }
}
