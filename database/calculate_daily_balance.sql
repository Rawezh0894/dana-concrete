-- SQL Queries for Daily Balance Calculation Based on Month Days
-- ئەم کوئریانە بۆ هەژمارکردنی باڵانس بە پێی ژمارەی ڕۆژەکانی مانگ

-- 1. فانکشنی SQL بۆ دۆزینەوەی ژمارەی ڕۆژەکانی مانگ
DELIMITER $$

DROP FUNCTION IF EXISTS get_days_in_month$$
CREATE FUNCTION get_days_in_month(year_val INT, month_val INT)
RETURNS INT
DETERMINISTIC
BEGIN
    DECLARE days INT;
    
    -- Check for February (leap year)
    IF month_val = 2 THEN
        IF (year_val % 4 = 0 AND year_val % 100 != 0) OR (year_val % 400 = 0) THEN
            SET days = 29;
        ELSE
            SET days = 28;
        END IF;
    -- Months with 31 days
    ELSEIF month_val IN (1, 3, 5, 7, 8, 10, 12) THEN
        SET days = 31;
    -- Months with 30 days
    ELSE
        SET days = 30;
    END IF;
    
    RETURN days;
END$$

DELIMITER ;

-- 2. فانکشنی SQL بۆ دۆزینەوەی ژمارەی ڕۆژەکانی بەکارهاتوو لە مانگێکدا
-- بە پێی بەرواری دەستپێکردن و بەرواری کۆتایی
DELIMITER $$

DROP FUNCTION IF EXISTS get_days_used_in_month$$
CREATE FUNCTION get_days_used_in_month(expense_date DATE, current_date DATE)
RETURNS INT
DETERMINISTIC
BEGIN
    DECLARE year_val INT;
    DECLARE month_val INT;
    DECLARE days_in_month INT;
    DECLARE days_used INT;
    DECLARE start_date DATE;
    DECLARE end_date DATE;
    
    -- Extract year and month from expense_date
    SET year_val = YEAR(expense_date);
    SET month_val = MONTH(expense_date);
    
    -- Get days in month
    SET days_in_month = get_days_in_month(year_val, month_val);
    
    -- Start date is first day of the month
    SET start_date = DATE(CONCAT(year_val, '-', LPAD(month_val, 2, '0'), '-01'));
    
    -- End date is current date if same month, otherwise last day of expense month
    IF YEAR(current_date) = year_val AND MONTH(current_date) = month_val THEN
        SET end_date = current_date;
    ELSE
        SET end_date = LAST_DAY(expense_date);
    END IF;
    
    -- Calculate days used (including both start and end day)
    SET days_used = DATEDIFF(end_date, start_date) + 1;
    
    -- Ensure days_used doesn't exceed days_in_month
    IF days_used > days_in_month THEN
        SET days_used = days_in_month;
    END IF;
    
    RETURN days_used;
END$$

DELIMITER ;

-- 3. فانکشنی SQL بۆ هەژمارکردنی بڕی ڕۆژانەی مووچە
DELIMITER $$

DROP FUNCTION IF EXISTS calculate_daily_salary$$
CREATE FUNCTION calculate_daily_salary(monthly_salary DECIMAL(15,2), expense_date DATE, current_date DATE)
RETURNS DECIMAL(15,2)
DETERMINISTIC
BEGIN
    DECLARE days_used INT;
    DECLARE days_in_month INT;
    DECLARE year_val INT;
    DECLARE month_val INT;
    DECLARE daily_salary DECIMAL(15,2);
    
    SET year_val = YEAR(expense_date);
    SET month_val = MONTH(expense_date);
    SET days_in_month = get_days_in_month(year_val, month_val);
    SET days_used = get_days_used_in_month(expense_date, current_date);
    
    -- Calculate daily salary: (monthly_salary / days_in_month) * days_used
    SET daily_salary = (monthly_salary / days_in_month) * days_used;
    
    RETURN daily_salary;
END$$

DELIMITER ;

-- 4. فانکشنی SQL بۆ هەژمارکردنی بڕی ڕۆژانەی پێشەکی/وەرگرتن
DELIMITER $$

DROP FUNCTION IF EXISTS calculate_daily_advance$$
CREATE FUNCTION calculate_daily_advance(advance_amount DECIMAL(15,2), expense_date DATE, current_date DATE)
RETURNS DECIMAL(15,2)
DETERMINISTIC
BEGIN
    DECLARE days_used INT;
    DECLARE days_in_month INT;
    DECLARE year_val INT;
    DECLARE month_val INT;
    DECLARE daily_advance DECIMAL(15,2);
    
    SET year_val = YEAR(expense_date);
    SET month_val = MONTH(expense_date);
    SET days_in_month = get_days_in_month(year_val, month_val);
    SET days_used = get_days_used_in_month(expense_date, current_date);
    
    -- Calculate daily advance: (advance_amount / days_in_month) * days_used
    SET daily_advance = (advance_amount / days_in_month) * days_used;
    
    RETURN daily_advance;
END$$

DELIMITER ;

-- 5. کوئری بۆ هەژمارکردنی باڵانسی کارمەند بە پێی ڕۆژەکان
-- ئەم کوئرییە باڵانسی کارمەند بە پێی مووچە و پێشەکی بە شێوەی ڕۆژانە هەژمار دەکات
SELECT 
    e.id,
    e.name,
    e.salary as monthly_salary,
    -- Calculate total salary earned up to current date
    COALESCE(SUM(
        CASE 
            WHEN ee.expense_type = 'salary' THEN 
                calculate_daily_salary(ee.amount, ee.expense_date, CURDATE())
            WHEN ee.expense_type = 'bonus' THEN ee.amount
            WHEN ee.expense_type = 'overtime' THEN ee.amount
            ELSE 0
        END
    ), 0) as total_earned_salary,
    -- Calculate total advances taken up to current date
    COALESCE(SUM(
        CASE 
            WHEN ee.expense_type = 'advance' THEN 
                calculate_daily_advance(ee.amount, ee.expense_date, CURDATE())
            WHEN ee.expense_type IN ('deduction', 'penalty') THEN ee.amount
            ELSE 0
        END
    ), 0) as total_taken_advances,
    -- Net balance (earned - taken)
    (COALESCE(SUM(
        CASE 
            WHEN ee.expense_type = 'salary' THEN 
                calculate_daily_salary(ee.amount, ee.expense_date, CURDATE())
            WHEN ee.expense_type = 'bonus' THEN ee.amount
            WHEN ee.expense_type = 'overtime' THEN ee.amount
            ELSE 0
        END
    ), 0) - COALESCE(SUM(
        CASE 
            WHEN ee.expense_type = 'advance' THEN 
                calculate_daily_advance(ee.amount, ee.expense_date, CURDATE())
            WHEN ee.expense_type IN ('deduction', 'penalty') THEN ee.amount
            ELSE 0
        END
    ), 0)) as net_balance,
    -- Days used in current month
    get_days_used_in_month(
        COALESCE((SELECT MIN(expense_date) FROM employee_expenses WHERE employee_id = e.id), CURDATE()),
        CURDATE()
    ) as days_used,
    -- Days in current month
    get_days_in_month(YEAR(CURDATE()), MONTH(CURDATE())) as days_in_month
FROM employees e
LEFT JOIN employee_expenses ee ON e.id = ee.employee_id
WHERE e.id = ?  -- Replace ? with employee_id
GROUP BY e.id, e.name, e.salary;

-- 6. کوئری بۆ هەژمارکردنی باڵانسی هەموو کارمەندەکان بە پێی ڕۆژەکان
SELECT 
    e.id,
    e.name,
    e.salary as monthly_salary,
    COALESCE(SUM(
        CASE 
            WHEN ee.expense_type = 'salary' THEN 
                calculate_daily_salary(ee.amount, ee.expense_date, CURDATE())
            WHEN ee.expense_type = 'bonus' THEN ee.amount
            WHEN ee.expense_type = 'overtime' THEN ee.amount
            ELSE 0
        END
    ), 0) as total_earned_salary,
    COALESCE(SUM(
        CASE 
            WHEN ee.expense_type = 'advance' THEN 
                calculate_daily_advance(ee.amount, ee.expense_date, CURDATE())
            WHEN ee.expense_type IN ('deduction', 'penalty') THEN ee.amount
            ELSE 0
        END
    ), 0) as total_taken_advances,
    (COALESCE(SUM(
        CASE 
            WHEN ee.expense_type = 'salary' THEN 
                calculate_daily_salary(ee.amount, ee.expense_date, CURDATE())
            WHEN ee.expense_type = 'bonus' THEN ee.amount
            WHEN ee.expense_type = 'overtime' THEN ee.amount
            ELSE 0
        END
    ), 0) - COALESCE(SUM(
        CASE 
            WHEN ee.expense_type = 'advance' THEN 
                calculate_daily_advance(ee.amount, ee.expense_date, CURDATE())
            WHEN ee.expense_type IN ('deduction', 'penalty') THEN ee.amount
            ELSE 0
        END
    ), 0)) as net_balance
FROM employees e
LEFT JOIN employee_expenses ee ON e.id = ee.employee_id
GROUP BY e.id, e.name, e.salary
ORDER BY e.name;

-- 7. کوئری بۆ هەژمارکردنی باڵانس بۆ مانگێکی تایبەت
-- بۆ نموونە: مانگی 1 (کانوونی دووەم) 2024
SELECT 
    e.id,
    e.name,
    e.salary as monthly_salary,
    '2024-01' as expense_month,
    -- Calculate salary for January 2024 up to current date (if current month) or full month
    COALESCE(SUM(
        CASE 
            WHEN ee.expense_type = 'salary' AND ee.expense_date = '2024-01-01' THEN 
                calculate_daily_salary(ee.amount, '2024-01-01', 
                    CASE 
                        WHEN YEAR(CURDATE()) = 2024 AND MONTH(CURDATE()) = 1 THEN CURDATE()
                        ELSE '2024-01-31'
                    END)
            WHEN ee.expense_type = 'bonus' AND ee.expense_date = '2024-01-01' THEN ee.amount
            WHEN ee.expense_type = 'overtime' AND ee.expense_date = '2024-01-01' THEN ee.amount
            ELSE 0
        END
    ), 0) as total_earned_salary,
    -- Calculate advances for January 2024
    COALESCE(SUM(
        CASE 
            WHEN ee.expense_type = 'advance' AND ee.expense_date = '2024-01-01' THEN 
                calculate_daily_advance(ee.amount, '2024-01-01',
                    CASE 
                        WHEN YEAR(CURDATE()) = 2024 AND MONTH(CURDATE()) = 1 THEN CURDATE()
                        ELSE '2024-01-31'
                    END)
            WHEN ee.expense_type IN ('deduction', 'penalty') AND ee.expense_date = '2024-01-01' THEN ee.amount
            ELSE 0
        END
    ), 0) as total_taken_advances,
    (COALESCE(SUM(
        CASE 
            WHEN ee.expense_type = 'salary' AND ee.expense_date = '2024-01-01' THEN 
                calculate_daily_salary(ee.amount, '2024-01-01',
                    CASE 
                        WHEN YEAR(CURDATE()) = 2024 AND MONTH(CURDATE()) = 1 THEN CURDATE()
                        ELSE '2024-01-31'
                    END)
            WHEN ee.expense_type = 'bonus' AND ee.expense_date = '2024-01-01' THEN ee.amount
            WHEN ee.expense_type = 'overtime' AND ee.expense_date = '2024-01-01' THEN ee.amount
            ELSE 0
        END
    ), 0) - COALESCE(SUM(
        CASE 
            WHEN ee.expense_type = 'advance' AND ee.expense_date = '2024-01-01' THEN 
                calculate_daily_advance(ee.amount, '2024-01-01',
                    CASE 
                        WHEN YEAR(CURDATE()) = 2024 AND MONTH(CURDATE()) = 1 THEN CURDATE()
                        ELSE '2024-01-31'
                    END)
            WHEN ee.expense_type IN ('deduction', 'penalty') AND ee.expense_date = '2024-01-01' THEN ee.amount
            ELSE 0
        END
    ), 0)) as net_balance
FROM employees e
LEFT JOIN employee_expenses ee ON e.id = ee.employee_id AND ee.expense_date LIKE '2024-01%'
WHERE e.id = ?  -- Replace ? with employee_id
GROUP BY e.id, e.name, e.salary;

