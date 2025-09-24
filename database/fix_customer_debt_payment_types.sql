-- Fix Customer Debt Payment Types and Allocation System
-- This script adds proper tracking for different payment types and their allocations

-- Add payment_type column if it doesn't exist (it should already exist based on the schema)
-- ALTER TABLE customer_debt_payments ADD COLUMN payment_type ENUM('fifo','specific_sales','opening_debt_only') NOT NULL DEFAULT 'fifo';

-- Ensure customer_payment_allocations table exists and has proper structure
CREATE TABLE IF NOT EXISTS `customer_payment_allocations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `debt_payment_id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `allocated_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_debt_payment_id` (`debt_payment_id`),
  KEY `idx_sale_id` (`sale_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add indexes for better performance
ALTER TABLE customer_debt_payments ADD INDEX idx_customer_id (customer_id);
ALTER TABLE customer_debt_payments ADD INDEX idx_payment_type (payment_type);
ALTER TABLE customer_debt_payments ADD INDEX idx_date (date);

-- Update existing records to have proper payment_type based on their allocations
-- This is a one-time migration for existing data
UPDATE customer_debt_payments 
SET payment_type = 'specific_sales' 
WHERE id IN (
    SELECT DISTINCT debt_payment_id 
    FROM customer_payment_allocations
);

-- For records with from_opening_debt_usd > 0 and from_sales_usd = 0, mark as opening_debt_only
UPDATE customer_debt_payments 
SET payment_type = 'opening_debt_only' 
WHERE from_opening_debt_usd > 0 
AND from_sales_usd = 0 
AND payment_type = 'fifo';

-- Create a function to properly handle debt payment deletion based on payment type
DELIMITER $$

CREATE OR REPLACE FUNCTION handle_debt_payment_deletion(
    p_debt_payment_id INT
) RETURNS BOOLEAN
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE v_customer_id INT;
    DECLARE v_payment_type VARCHAR(20);
    DECLARE v_from_opening_debt_usd DECIMAL(14,2);
    DECLARE v_from_sales_usd DECIMAL(14,2);
    DECLARE v_done INT DEFAULT FALSE;
    DECLARE v_sale_id INT;
    DECLARE v_allocated_amount DECIMAL(14,2);
    
    -- Cursor for specific sales allocations
    DECLARE allocation_cursor CURSOR FOR
        SELECT sale_id, allocated_amount 
        FROM customer_payment_allocations 
        WHERE debt_payment_id = p_debt_payment_id;
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_done = TRUE;
    
    -- Get payment details
    SELECT customer_id, payment_type, from_opening_debt_usd, from_sales_usd
    INTO v_customer_id, v_payment_type, v_from_opening_debt_usd, v_from_sales_usd
    FROM customer_debt_payments 
    WHERE id = p_debt_payment_id;
    
    IF v_customer_id IS NULL THEN
        RETURN FALSE;
    END IF;
    
    -- Handle based on payment type
    CASE v_payment_type
        WHEN 'opening_debt_only' THEN
            -- Restore to opening debt
            UPDATE customers 
            SET opening_debt_usd = opening_debt_usd + v_from_opening_debt_usd 
            WHERE id = v_customer_id;
            
        WHEN 'specific_sales' THEN
            -- Restore to specific sales based on allocations
            OPEN allocation_cursor;
            allocation_loop: LOOP
                FETCH allocation_cursor INTO v_sale_id, v_allocated_amount;
                IF v_done THEN
                    LEAVE allocation_loop;
                END IF;
                
                -- Restore the allocated amount to the specific sale
                UPDATE sales 
                SET remaining_amount = remaining_amount + v_allocated_amount 
                WHERE id = v_sale_id AND customer_id = v_customer_id;
            END LOOP;
            CLOSE allocation_cursor;
            
        WHEN 'fifo' THEN
            -- Handle FIFO deletion (LIFO restoration)
            -- First restore to opening debt if any
            IF v_from_opening_debt_usd > 0 THEN
                UPDATE customers 
                SET opening_debt_usd = opening_debt_usd + v_from_opening_debt_usd 
                WHERE id = v_customer_id;
            END IF;
            
            -- Then restore to sales using LIFO (Last In, First Out)
            IF v_from_sales_usd > 0 THEN
                -- Get sales in reverse order (LIFO)
                SET @remaining_to_restore = v_from_sales_usd;
                
                -- Use a temporary table to handle LIFO restoration
                CREATE TEMPORARY TABLE temp_sales_restore AS
                SELECT id, remaining_amount, total_price,
                       (total_price - remaining_amount) as paid_amount
                FROM sales 
                WHERE customer_id = v_customer_id 
                AND (total_price - remaining_amount) > 0
                ORDER BY order_date DESC, id DESC;
                
                -- Restore using LIFO
                WHILE @remaining_to_restore > 0 DO
                    SELECT id, paid_amount INTO v_sale_id, v_allocated_amount
                    FROM temp_sales_restore 
                    WHERE paid_amount > 0 
                    LIMIT 1;
                    
                    IF v_sale_id IS NULL THEN
                        LEAVE;
                    END IF;
                    
                    SET v_allocated_amount = LEAST(v_allocated_amount, @remaining_to_restore);
                    
                    UPDATE sales 
                    SET remaining_amount = remaining_amount + v_allocated_amount 
                    WHERE id = v_sale_id;
                    
                    SET @remaining_to_restore = @remaining_to_restore - v_allocated_amount;
                    
                    UPDATE temp_sales_restore 
                    SET paid_amount = paid_amount - v_allocated_amount 
                    WHERE id = v_sale_id;
                END WHILE;
                
                DROP TEMPORARY TABLE temp_sales_restore;
            END IF;
    END CASE;
    
    RETURN TRUE;
END$$

DELIMITER ;

-- Create a function to properly handle debt payment updates
DELIMITER $$

CREATE OR REPLACE FUNCTION handle_debt_payment_update(
    p_debt_payment_id INT,
    p_new_payment_type VARCHAR(20),
    p_new_from_opening_debt_usd DECIMAL(14,2),
    p_new_from_sales_usd DECIMAL(14,2)
) RETURNS BOOLEAN
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE v_customer_id INT;
    DECLARE v_old_payment_type VARCHAR(20);
    DECLARE v_old_from_opening_debt_usd DECIMAL(14,2);
    DECLARE v_old_from_sales_usd DECIMAL(14,2);
    
    -- Get current payment details
    SELECT customer_id, payment_type, from_opening_debt_usd, from_sales_usd
    INTO v_customer_id, v_old_payment_type, v_old_from_opening_debt_usd, v_old_from_sales_usd
    FROM customer_debt_payments 
    WHERE id = p_debt_payment_id;
    
    IF v_customer_id IS NULL THEN
        RETURN FALSE;
    END IF;
    
    -- First, restore the old allocations (like deletion)
    IF NOT handle_debt_payment_deletion(p_debt_payment_id) THEN
        RETURN FALSE;
    END IF;
    
    -- Then apply new allocations (like insertion)
    -- This will be handled by the application logic
    
    RETURN TRUE;
END$$

DELIMITER ;

-- Add comments to explain the system
ALTER TABLE customer_debt_payments 
COMMENT = 'Customer debt payments with proper type tracking: fifo=First In First Out, specific_sales=allocated to specific sales, opening_debt_only=only opening debt';

ALTER TABLE customer_payment_allocations 
COMMENT = 'Tracks specific sales allocations for customer debt payments';

-- Create a view for better reporting
CREATE OR REPLACE VIEW customer_debt_payment_summary AS
SELECT 
    cdp.id,
    cdp.customer_id,
    c.name as customer_name,
    cdp.date,
    cdp.payment_type,
    cdp.paid_usd,
    cdp.paid_iqd,
    cdp.discount,
    cdp.from_opening_debt_usd,
    cdp.from_sales_usd,
    COUNT(cpa.id) as allocation_count,
    GROUP_CONCAT(CONCAT('Sale ', cpa.sale_id, ': ', cpa.allocated_amount, '$') SEPARATOR ', ') as allocations
FROM customer_debt_payments cdp
LEFT JOIN customers c ON cdp.customer_id = c.id
LEFT JOIN customer_payment_allocations cpa ON cdp.id = cpa.debt_payment_id
GROUP BY cdp.id, cdp.customer_id, c.name, cdp.date, cdp.payment_type, 
         cdp.paid_usd, cdp.paid_iqd, cdp.discount, cdp.from_opening_debt_usd, cdp.from_sales_usd;
