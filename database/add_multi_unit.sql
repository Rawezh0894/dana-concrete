-- Add multi-unit support to items
ALTER TABLE inv_items 
ADD COLUMN secondary_unit VARCHAR(100) DEFAULT NULL,
ADD COLUMN conversion_factor DECIMAL(15, 4) DEFAULT 1.0000;

-- Optional: Track which unit was used in transactions for clarity
ALTER TABLE inv_purchase_items
ADD COLUMN unit_used VARCHAR(50) DEFAULT NULL;

ALTER TABLE inv_issuance
ADD COLUMN unit_used VARCHAR(50) DEFAULT NULL;
