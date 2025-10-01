-- Paystack Payments Table
-- This table stores all payment transactions from Paystack

CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    order_id INT,
    email VARCHAR(255) NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'GHS',
    reference VARCHAR(100) UNIQUE NOT NULL,
    payment_method VARCHAR(50) DEFAULT 'paystack',
    status ENUM('pending', 'success', 'failed', 'cancelled') DEFAULT 'pending',
    paystack_response LONGTEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Indexes for performance
    INDEX idx_user_id (user_id),
    INDEX idx_order_id (order_id),
    INDEX idx_reference (reference),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
);

-- Orders table (enhanced for Paystack integration)
-- Add these columns to your existing orders table if it doesn't have them

ALTER TABLE orders
ADD COLUMN IF NOT EXISTS payment_reference VARCHAR(100),
ADD COLUMN IF NOT EXISTS payment_method VARCHAR(50) DEFAULT 'paystack',
ADD COLUMN IF NOT EXISTS payment_status ENUM('pending', 'paid', 'failed', 'cancelled') DEFAULT 'pending',
ADD COLUMN IF NOT EXISTS payment_response LONGTEXT,
ADD INDEX IF NOT EXISTS idx_payment_reference (payment_reference),
ADD INDEX IF NOT EXISTS idx_payment_status (payment_status);
