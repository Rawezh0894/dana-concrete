-- کوێری بۆ زیادکردنی ستوونی bonus و status بۆ تەیبڵی employees
-- تێبینی: MySQL/MariaDB وەشانی کۆنتر پشتگیری IF NOT EXISTS ناکات لەگەڵ ADD COLUMN
-- بۆیە پێویستە بە شێوەیەکی دیکە بکرێت

-- کوێری ساکار (ئەگەر دڵنیایت ستوونەکان نین):
-- ALTER TABLE employees ADD COLUMN bonus DECIMAL(10,2) DEFAULT 0.00 AFTER salary;
-- ALTER TABLE employees ADD COLUMN status ENUM('active','inactive','on_leave','resigned') DEFAULT 'active' AFTER role;

-- یان بەکارهێنانی کوێری تەفسیلی لە add_bonus_status_columns_fixed.sql
