-- کوێری بۆ زیادکردنی ستوونی bonus و status بۆ تەیبڵی employees
-- ئەم کوێرییە لەگەڵ MySQL/MariaDB وەشانی کۆنتر کاردەکات
-- Run this query in your dana_concrete_db database

USE dana_concrete_db;

-- زیادکردنی ستوونی bonus (ئەگەر نەبێت)
SET @dbname = DATABASE();
SET @tablename = 'employees';
SET @columnname = 'bonus';

SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT "Column bonus already exists" as message',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' DECIMAL(10,2) DEFAULT 0.00 AFTER salary')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- زیادکردنی ستوونی status (ئەگەر نەبێت)
SET @columnname = 'status';

SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT "Column status already exists" as message',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, " ENUM('active','inactive','on_leave','resigned') DEFAULT 'active' AFTER role")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- پشکنینی ستوونەکان
SELECT COLUMN_NAME, DATA_TYPE, COLUMN_DEFAULT, IS_NULLABLE
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'employees' 
  AND COLUMN_NAME IN ('bonus', 'status')
ORDER BY COLUMN_NAME;
