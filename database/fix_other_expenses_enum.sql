-- Fix enum issues in other_expenses table
-- چارەسەرکردنی هەڵەی enum لە تەیبڵی other_expenses

USE dana_concrete_db;

-- Update currency_type to allow NULL values
ALTER TABLE other_expenses 
MODIFY COLUMN currency_type ENUM('دینار','دۆلار') DEFAULT 'دینار';

-- Update usage_unit_type to allow NULL values and empty strings
ALTER TABLE other_expenses 
MODIFY COLUMN usage_unit_type ENUM('کارتۆن','دانە','بەرمیل','دەبە','لیتر') DEFAULT NULL;

-- Update payment_type to allow NULL values
ALTER TABLE other_expenses 
MODIFY COLUMN payment_type ENUM('نەقد','قەرز') DEFAULT 'نەقد';

-- Update expense_type to allow NULL values
ALTER TABLE other_expenses 
MODIFY COLUMN expense_type ENUM('بەکارهێنانی کاڵای کۆگا','بەکارهێنانی گاز','خەرجی تر','خواردنگە','ئۆفیس') DEFAULT 'خەرجی تر';

-- Set default values for existing NULL records
UPDATE other_expenses SET currency_type = 'دینار' WHERE currency_type IS NULL;
UPDATE other_expenses SET payment_type = 'نەقد' WHERE payment_type IS NULL;
UPDATE other_expenses SET expense_type = 'خەرجی تر' WHERE expense_type IS NULL;

-- Show table structure after fixes
DESCRIBE other_expenses; 