-- Orders System Tables
-- Add these tables to your database for the checkout system

-- Orders table - stores order information
CREATE TABLE IF NOT EXISTS orders (
    order_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    payment_status VARCHAR(50) DEFAULT 'pending',
    order_status VARCHAR(50) DEFAULT 'pending',
    shipping_address TEXT NOT NULL,
    billing_address TEXT,
    order_notes TEXT,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- Order items table - stores individual items in each order
CREATE TABLE IF NOT EXISTS order_items (
    order_item_id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    product_price DECIMAL(10,2) NOT NULL,
    quantity INT NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(order_id),
    FOREIGN KEY (product_id) REFERENCES products(product_id)
);

-- Order status updates table - tracks status changes
CREATE TABLE IF NOT EXISTS order_status_updates (
    update_id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    old_status VARCHAR(50),
    new_status VARCHAR(50) NOT NULL,
    updated_by INT, -- user_id of who made the update (admin/user)
    update_notes TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(order_id)
);

-- Insert some sample order statuses for reference
INSERT IGNORE INTO order_status_updates (order_id, new_status, updated_by, update_notes) VALUES
(1, 'pending', 1, 'Order created'),
(1, 'processing', 1, 'Payment confirmed'),
(1, 'shipped', 1, 'Order shipped'),
(1, 'delivered', 1, 'Order delivered');
