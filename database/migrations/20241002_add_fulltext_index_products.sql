-- Add full-text index for better search functionality
ALTER TABLE products 
ADD FULLTEXT INDEX `ft_name_description` (name, description) 
COMMENT 'Full-text index for product search';

-- For MyISAM tables (if not using InnoDB with full-text support):
-- ALTER TABLE products ENGINE = MyISAM;
-- Then create the full-text index
