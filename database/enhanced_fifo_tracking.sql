-- Enhanced FIFO Tracking System
-- This script creates a better tracking system for FIFO payments

-- Create a table to track FIFO payment allocations
CREATE TABLE IF NOT EXISTS `customer_fifo_allocations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `debt_payment_id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `allocated_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_debt_payment_id` (`debt_payment_id`),
  KEY `idx_sale_id` (`sale_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add a function to properly handle FIFO deletion with exact tracking
DELIMITER $$

CREATE OR REPLACE FUNCTION handle_fifo_debt_payment_deletion(
    p_debt_payment_id INT
) RETURNS BOOLEAN
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE v_customer_id INT;
    DECLARE v_from_opening_debt_usd DECIMAL(14,2);
    DECLARE v_from_sales_usd DECIMAL(14,2);
    DECLARE v_done INT DEFAULT FALSE;
    DECLARE v_sale_id INT;
    DECLARE v_allocated_amount DECIMAL(14,2);
    
    -- Cursor for FIFO allocations
    DECLARE fifo_cursor CURSOR FOR
        SELECT sale_id, allocated_amount 
        FROM customer_fifo_allocations 
        WHERE debt_payment_id = p_debt_payment_id;
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_done = TRUE;
    
    -- Get payment details
    SELECT customer_id, from_opening_debt_usd, from_sales_usd
    INTO v_customer_id, v_from_opening_debt_usd, v_from_sales_usd
    FROM customer_debt_payments 
    WHERE id = p_debt_payment_id;
    
    IF v_customer_id IS NULL THEN
        RETURN FALSE;
    END IF;
    
    -- Restore opening debt
    IF v_from_opening_debt_usd > 0 THEN
        UPDATE customers 
        SET opening_debt_usd = opening_debt_usd + v_from_opening_debt_usd 
        WHERE id = v_customer_id;
    END IF;
    
    -- Restore sales based on exact FIFO allocations
    OPEN fifo_cursor;
    fifo_loop: LOOP
        FETCH fifo_cursor INTO v_sale_id, v_allocated_amount;
        IF v_done THEN
            LEAVE fifo_loop;
        END IF;
        
        -- Restore the exact allocated amount to the specific sale
        UPDATE sales 
        SET remaining_amount = remaining_amount + v_allocated_amount 
        WHERE id = v_sale_id AND customer_id = v_customer_id;
    END LOOP;
    CLOSE fifo_cursor;
    
    RETURN TRUE;
END$$

DELIMITER ;

-- Update the add_return_debt.php to create FIFO allocation records
-- This will be handled in the PHP code, but here's the SQL structure

-- Create a view for better FIFO payment reporting
CREATE OR REPLACE VIEW customer_fifo_payment_summary AS
SELECT 
    cdp.id,
    cdp.customer_id,
    c.name as customer_name,
    cdp.date,
    cdp.payment_type,
    cdp.paid_usd,
    cdp.paid_iqd,
    cdp.from_opening_debt_usd,
    cdp.from_sales_usd,
    COUNT(cfa.id) as fifo_allocation_count,
    GROUP_CONCAT(CONCAT('Sale ', cfa.sale_id, ': ', cfa.allocated_amount, '$') SEPARATOR ', ') as fifo_allocations
FROM customer_debt_payments cdp
LEFT JOIN customers c ON cdp.customer_id = c.id
LEFT JOIN customer_fifo_allocations cfa ON cdp.id = cfa.debt_payment_id
WHERE cdp.payment_type = 'fifo'
GROUP BY cdp.id, cdp.customer_id, c.name, cdp.date, cdp.payment_type, 
         cdp.paid_usd, cdp.paid_iqd, cdp.from_opening_debt_usd, cdp.from_sales_usd;
