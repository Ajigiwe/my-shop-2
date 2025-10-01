-- Add transactions table for Paystack payment tracking
CREATE TABLE IF NOT EXISTS transactions (
    transaction_id INT AUTO_INCREMENT PRIMARY KEY,
    reference VARCHAR(255) UNIQUE NOT NULL,
    order_id INT,
    user_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'NGN',
    status ENUM('pending', 'success', 'failed', 'cancelled') DEFAULT 'pending',
    gateway_response TEXT,
    payment_method VARCHAR(50) DEFAULT 'paystack',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE SET NULL,
    INDEX idx_reference (reference),
    INDEX idx_status (status),
    INDEX idx_user_id (user_id)
);

-- Update orders table to include payment tracking columns
ALTER TABLE orders
ADD COLUMN IF NOT EXISTS payment_reference VARCHAR(255) AFTER payment_method,
ADD COLUMN IF NOT EXISTS payment_status ENUM('pending', 'paid', 'failed', 'cancelled') DEFAULT 'pending' AFTER payment_reference,
ADD INDEX IF NOT EXISTS idx_payment_reference (payment_reference),
ADD INDEX IF NOT EXISTS idx_payment_status (payment_status);
