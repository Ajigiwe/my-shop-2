-- Paystack Migration
-- Add payment reference and payment status columns to orders table

-- Add payment reference column
ALTER TABLE orders ADD COLUMN payment_reference VARCHAR(255) NULL;

-- Add payment status column
ALTER TABLE orders ADD COLUMN payment_status ENUM('pending', 'completed', 'failed', 'cancelled') DEFAULT 'pending';

-- Add index for payment reference for faster lookups
CREATE INDEX idx_payment_reference ON orders(payment_reference);

-- Update existing orders to have default payment status
UPDATE orders SET payment_status = 'completed' WHERE payment_method = 'cash_on_delivery';
UPDATE orders SET payment_status = 'pending' WHERE payment_method = 'paystack';
