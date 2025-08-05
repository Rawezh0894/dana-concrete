-- Complete fix for other_expenses table enum issues
-- چارەسەری تەواو بۆ هەڵەی enum لە تەیبڵی other_expenses

USE dana_concrete_db;

-- Drop existing triggers first
DROP TRIGGER IF EXISTS trg_after_insert_other_expenses;
DROP TRIGGER IF EXISTS trg_after_update_other_expenses;
DROP TRIGGER IF EXISTS trg_before_delete_other_expenses;
DROP TRIGGER IF EXISTS trg_before_update_other_expenses;

-- Update currency_type to have proper default
ALTER TABLE other_expenses 
MODIFY COLUMN currency_type ENUM('دینار','دۆلار') DEFAULT 'دینار' NOT NULL;

-- Update usage_unit_type to allow NULL but with proper validation
ALTER TABLE other_expenses 
MODIFY COLUMN usage_unit_type ENUM('کارتۆن','دانە','بەرمیل','دەبە','لیتر') DEFAULT NULL;

-- Update payment_type to have proper default
ALTER TABLE other_expenses 
MODIFY COLUMN payment_type ENUM('نەقد','قەرز') DEFAULT 'نەقد' NOT NULL;

-- Update expense_type to have proper default
ALTER TABLE other_expenses 
MODIFY COLUMN expense_type ENUM('بەکارهێنانی کاڵای کۆگا','بەکارهێنانی گاز','خەرجی تر','خواردنگە','ئۆفیس') DEFAULT 'خەرجی تر' NOT NULL;

-- Set default values for existing NULL records
UPDATE other_expenses SET currency_type = 'دینار' WHERE currency_type IS NULL OR currency_type = '';
UPDATE other_expenses SET payment_type = 'نەقد' WHERE payment_type IS NULL OR payment_type = '';
UPDATE other_expenses SET expense_type = 'خەرجی تر' WHERE expense_type IS NULL OR expense_type = '';

-- Show table structure after fixes
DESCRIBE other_expenses;

-- Show sample data to verify
SELECT id, payment_type, currency_type, expense_type, usage_unit_type FROM other_expenses LIMIT 5; 