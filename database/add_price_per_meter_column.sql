-- Add price_per_meter column to concrete_receipts table
ALTER TABLE `concrete_receipts` 
ADD COLUMN `price_per_meter` DECIMAL(10,2) DEFAULT NULL 
AFTER `meter_amount`; 