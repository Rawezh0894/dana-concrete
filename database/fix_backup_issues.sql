-- Fix backup issues for dana_concrete_db
-- چارەسەرکردنی کێشەکانی باک ئەپ

USE dana_concrete_db;

-- Fix character set and collation
ALTER DATABASE dana_concrete_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Fix other_expenses table character set
ALTER TABLE other_expenses CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Fix enum columns with proper defaults
ALTER TABLE other_expenses 
MODIFY COLUMN payment_type ENUM('نەقد','قەرز') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'نەقد' NOT NULL;

ALTER TABLE other_expenses 
MODIFY COLUMN currency_type ENUM('دینار','دۆلار') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'دینار' NOT NULL;

ALTER TABLE other_expenses 
MODIFY COLUMN expense_type ENUM('بەکارهێنانی کاڵای کۆگا','بەکارهێنانی گاز','خەرجی تر','خواردنگە','ئۆفیس') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'خەرجی تر' NOT NULL;

ALTER TABLE other_expenses 
MODIFY COLUMN usage_unit_type ENUM('کارتۆن','دانە','بەرمیل','دەبە','لیتر') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL;

-- Update existing data to fix encoding issues
UPDATE other_expenses SET 
    payment_type = 'نەقد' WHERE payment_type IS NULL OR payment_type = '',
    currency_type = 'دینار' WHERE currency_type IS NULL OR currency_type = '',
    expense_type = 'خەرجی تر' WHERE expense_type IS NULL OR expense_type = '';

-- Show table structure after fixes
DESCRIBE other_expenses;

-- Show sample data to verify encoding
SELECT id, payment_type, currency_type, expense_type, usage_unit_type FROM other_expenses LIMIT 5; 