-- Fix invoice_number field length in recycle_bin_sales table
-- This script updates the existing table to match the sales table structure

-- Update the invoice_number column to varchar(500) to match sales table
ALTER TABLE `recycle_bin_sales` 
MODIFY COLUMN `invoice_number` varchar(500) NOT NULL;

-- Verify the change
DESCRIBE `recycle_bin_sales`;
