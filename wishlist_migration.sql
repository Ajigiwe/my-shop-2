-- Wishlist feature migration
-- Run this SQL to add wishlist functionality to your database

USE ecommerce_db;

-- Create wishlist table
CREATE TABLE IF NOT EXISTS wishlist (
    wishlist_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    UNIQUE KEY unique_wishlist (user_id, product_id)
);

-- Create product reviews table
CREATE TABLE IF NOT EXISTS product_reviews (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    review_text TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_review (product_id, user_id)
);

-- Add average rating column to products table
ALTER TABLE products ADD COLUMN IF NOT EXISTS average_rating DECIMAL(3,2) DEFAULT 0.00;
ALTER TABLE products ADD COLUMN IF NOT EXISTS review_count INT DEFAULT 0;

-- Create product comparison table
CREATE TABLE IF NOT EXISTS product_comparisons (
    comparison_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    UNIQUE KEY unique_comparison (user_id, product_id)
);

-- Add view count to products
ALTER TABLE products ADD COLUMN IF NOT EXISTS view_count INT DEFAULT 0;

-- Create product tags table
CREATE TABLE IF NOT EXISTS product_tags (
    tag_id INT AUTO_INCREMENT PRIMARY KEY,
    tag_name VARCHAR(50) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create product_tag_relations table
CREATE TABLE IF NOT EXISTS product_tag_relations (
    product_id INT NOT NULL,
    tag_id INT NOT NULL,
    PRIMARY KEY (product_id, tag_id),
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES product_tags(tag_id) ON DELETE CASCADE
);

-- Insert some sample tags
INSERT IGNORE INTO product_tags (tag_name) VALUES 
('Featured'), ('Sale'), ('New Arrival'), ('Popular'), ('Limited Edition'),
('Eco-Friendly'), ('Premium'), ('Budget-Friendly'), ('Trending'), ('Best Seller');

-- Add some sample reviews (optional)
-- Note: Make sure you have products and users before running this
-- INSERT INTO product_reviews (product_id, user_id, rating, review_text) VALUES
-- (1, 2, 5, 'Excellent product! Highly recommended.'),
-- (1, 3, 4, 'Good quality, fast delivery.'),
-- (2, 2, 3, 'Average product, could be better.');

-- Update product ratings (run this after adding reviews)
-- UPDATE products p SET 
--     average_rating = (SELECT AVG(rating) FROM product_reviews WHERE product_id = p.product_id),
--     review_count = (SELECT COUNT(*) FROM product_reviews WHERE product_id = p.product_id);
