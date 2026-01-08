-- کوێری بۆ زیادکردنی ڕۆڵەکانی نوێ بۆ تەیبڵی employees
-- Run this query in your dana_concrete_db database

USE dana_concrete_db;

-- زیادکردنی ڕۆڵەکانی نوێ: پاسەوان، فیتەر، موساعید
ALTER TABLE employees 
MODIFY COLUMN role ENUM('شۆفێر','موحاسیب','وەکیل','پاسەوان','فیتەر','موساعید') NOT NULL;

-- پشکنینی ستوونی role
DESCRIBE employees;
