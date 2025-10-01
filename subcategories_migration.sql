-- Subcategories migration
-- Run this in phpMyAdmin after importing database_setup.sql

USE ecommerce_db;

-- 1) Create subcategories table
CREATE TABLE IF NOT EXISTS subcategories (
    subcategory_id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    subcategory_name VARCHAR(150) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE CASCADE,
    UNIQUE KEY unique_cat_subcat (category_id, subcategory_name)
);

-- 2) Add optional subcategory to products
ALTER TABLE products
    ADD COLUMN IF NOT EXISTS subcategory_id INT NULL AFTER category_id;

-- 3) Add FK for products.subcategory_id
ALTER TABLE products
    ADD CONSTRAINT fk_products_subcategory
    FOREIGN KEY (subcategory_id) REFERENCES subcategories(subcategory_id)
    ON DELETE SET NULL;

-- Optional sample subcategories
INSERT INTO subcategories (category_id, subcategory_name, description) VALUES
((SELECT category_id FROM categories WHERE category_name='Electronics' LIMIT 1), 'Phones', 'Smartphones and accessories'),
((SELECT category_id FROM categories WHERE category_name='Electronics' LIMIT 1), 'Audio', 'Headphones and speakers'),
((SELECT category_id FROM categories WHERE category_name='Clothing' LIMIT 1), 'Men', 'Menswear'),
((SELECT category_id FROM categories WHERE category_name='Clothing' LIMIT 1), 'Women', 'Womenswear');
