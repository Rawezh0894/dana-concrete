-- کوێری بۆ گۆڕینی ستونی role لە ENUM بۆ VARCHAR بۆ پشتگیری لە چەند ڕۆڵ
-- Run this query in your dana_concrete_db database

USE dana_concrete_db;

-- گۆڕینی ستونی role بۆ VARCHAR بۆ پشتگیری لە چەند ڕۆڵ (comma-separated)
ALTER TABLE employees 
MODIFY COLUMN role VARCHAR(500) NOT NULL;

-- پشکنینی ستونی role
DESCRIBE employees;
