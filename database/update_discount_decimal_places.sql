-- Update discount field to support 4 decimal places
-- This allows for more precise discount calculations like 0.3496

ALTER TABLE `customer_debt_payments` 
MODIFY COLUMN `discount` decimal(14,4) DEFAULT 0.0000;

-- Also update the payment allocations table if it has similar fields
-- (This is for future use if needed)
-- ALTER TABLE `customer_payment_allocations` 
-- MODIFY COLUMN `allocated_amount` decimal(14,4) DEFAULT 0.0000;
