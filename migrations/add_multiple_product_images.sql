-- Migration to add support for multiple product images

-- Create product_images table
CREATE TABLE IF NOT EXISTS product_images (
    image_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    is_primary BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    INDEX idx_product_id (product_id)
);

-- Add has_multiple_images column to products table
ALTER TABLE products 
ADD COLUMN has_multiple_images BOOLEAN DEFAULT FALSE,
ADD COLUMN main_image_id INT NULL,
ADD CONSTRAINT fk_main_image 
    FOREIGN KEY (main_image_id) 
    REFERENCES product_images(image_id) 
    ON DELETE SET NULL;

-- Create a trigger to set the first image as primary
DELIMITER //
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
END //
DELIMITER ;

-- Create a procedure to set a new primary image
DELIMITER //
CREATE PROCEDURE SetPrimaryImage(IN p_product_id INT, IN p_image_id INT)
BEGIN
    -- Reset all images for this product to not primary
    UPDATE product_images 
    SET is_primary = FALSE 
    WHERE product_id = p_product_id;
    
    -- Set the specified image as primary
    UPDATE product_images 
    SET is_primary = TRUE 
    WHERE image_id = p_image_id 
    AND product_id = p_product_id;
    
    -- Update the products table to point to the new primary image
    UPDATE products p
    JOIN product_images pi ON p.product_id = pi.product_id
    SET p.main_image_id = p_image_id,
        p.image = pi.image_path,
        p.has_multiple_images = TRUE
    WHERE pi.image_id = p_image_id
    AND p.product_id = p_product_id;
END //
DELIMITER ;
