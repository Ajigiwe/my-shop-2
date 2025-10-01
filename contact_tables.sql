-- Contact Messages Table
-- Stores customer inquiries and messages

CREATE TABLE IF NOT EXISTS contact_messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    newsletter_subscribe BOOLEAN DEFAULT FALSE,
    status ENUM('new', 'read', 'replied', 'closed') DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Newsletter Subscribers Table
-- Stores email addresses for newsletter subscriptions

CREATE TABLE IF NOT EXISTS newsletter_subscribers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) NOT NULL UNIQUE,
    name VARCHAR(100),
    subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    unsubscribed_at TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    INDEX idx_email (email),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Update existing contact_messages table if it exists (add new columns)
ALTER TABLE contact_messages
ADD COLUMN IF NOT EXISTS status ENUM('new', 'read', 'replied', 'closed') DEFAULT 'new',
ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Update existing newsletter_subscribers table if it exists (add new columns)
ALTER TABLE newsletter_subscribers
ADD COLUMN IF NOT EXISTS unsubscribed_at TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS is_active BOOLEAN DEFAULT TRUE;
