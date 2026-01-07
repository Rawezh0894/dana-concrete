-- کوئری بۆ گواستنەوەی داتا لە employee_payments بۆ employee_expenses
-- ئەم کوئریانە داتاکانی کۆن دەگۆڕێت بۆ سیستەمی نوێ

-- تێبینی: ئەم کوئریانە دەتوانێت داتای پێشوو بگۆڕێت
-- پێش جێبەجێکردن، backup بگرە!

-- 1. گواستنەوەی مووچە
INSERT INTO employee_expenses (employee_id, expense_type, amount, notes, created_by, expense_date, created_at, updated_at)
SELECT 
    employee_id,
    'salary' as expense_type,
    salary as amount,
    CONCAT('گواستنەوە لە employee_payments - ID: ', id) as notes,
    NULL as created_by,
    pay_month as expense_date,
    created_at,
    updated_at
FROM employee_payments
WHERE salary > 0;

-- 2. گواستنەوەی کاروانحیسابی
INSERT INTO employee_expenses (employee_id, expense_type, amount, notes, created_by, expense_date, created_at, updated_at)
SELECT 
    employee_id,
    'overtime' as expense_type,
    CAST(REPLACE(karwanhisabi, ',', '') AS DECIMAL(10,2)) as amount,
    CONCAT('گواستنەوە لە employee_payments - ID: ', id) as notes,
    NULL as created_by,
    pay_month as expense_date,
    created_at,
    updated_at
FROM employee_payments
WHERE karwanhisabi IS NOT NULL 
  AND karwanhisabi != '' 
  AND karwanhisabi != '0'
  AND CAST(REPLACE(karwanhisabi, ',', '') AS DECIMAL(10,2)) > 0;

-- 3. گواستنەوەی بەخشیش
INSERT INTO employee_expenses (employee_id, expense_type, amount, notes, created_by, expense_date, created_at, updated_at)
SELECT 
    employee_id,
    'bonus' as expense_type,
    bonus as amount,
    CONCAT('گواستنەوە لە employee_payments - ID: ', id) as notes,
    NULL as created_by,
    pay_month as expense_date,
    created_at,
    updated_at
FROM employee_payments
WHERE bonus > 0;

-- 4. دووبارە هەژمارکردنەوەی باڵانسەکان
-- پاککردنەوەی باڵانسەکان
UPDATE `employees` 
SET `payable_balance` = 0, 
    `receivable_balance` = 0;

-- دووبارە هەژمارکردنەوە لە employee_expenses
-- مووچە، بەخشیش، کاروانحیسابی
UPDATE `employees` e
INNER JOIN (
    SELECT 
        employee_id,
        SUM(amount) as total_amount
    FROM `employee_expenses`
    WHERE expense_type IN ('salary', 'bonus', 'overtime')
    GROUP BY employee_id
) calc ON e.id = calc.employee_id
SET e.payable_balance = COALESCE(e.payable_balance, 0) + calc.total_amount;

-- پێشەکی، کەمکردنەوە، سزا
DELIMITER $$

DROP PROCEDURE IF EXISTS recalculate_all_deductions$$
CREATE PROCEDURE recalculate_all_deductions()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_employee_id INT;
    DECLARE v_amount DECIMAL(10,2);
    DECLARE v_expense_type VARCHAR(20);
    DECLARE v_current_payable DECIMAL(15,2);
    DECLARE v_current_receivable DECIMAL(15,2);
    
    DECLARE cur CURSOR FOR 
        SELECT employee_id, amount, expense_type
        FROM employee_expenses 
        WHERE expense_type IN ('advance', 'deduction', 'penalty')
        ORDER BY employee_id, created_at ASC;
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN cur;
    
    read_loop: LOOP
        FETCH cur INTO v_employee_id, v_amount, v_expense_type;
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        SELECT COALESCE(payable_balance, 0), COALESCE(receivable_balance, 0)
        INTO v_current_payable, v_current_receivable
        FROM employees
        WHERE id = v_employee_id;
        
        IF v_current_payable >= v_amount THEN
            UPDATE employees
            SET payable_balance = v_current_payable - v_amount
            WHERE id = v_employee_id;
        ELSE
            UPDATE employees
            SET payable_balance = 0,
                receivable_balance = v_current_receivable + (v_amount - v_current_payable)
            WHERE id = v_employee_id;
        END IF;
        
        SELECT COALESCE(payable_balance, 0), COALESCE(receivable_balance, 0)
        INTO v_current_payable, v_current_receivable
        FROM employees
        WHERE id = v_employee_id;
        
    END LOOP;
    
    CLOSE cur;
END$$

DELIMITER ;

CALL recalculate_all_deductions();
DROP PROCEDURE IF EXISTS recalculate_all_deductions;

-- 5. پیشاندانی کۆی باڵانسەکان بۆ دڵنیابوون
SELECT 
    e.id,
    e.name,
    COALESCE(e.payable_balance, 0) as payable_balance,
    COALESCE(e.receivable_balance, 0) as receivable_balance,
    (COALESCE(e.payable_balance, 0) - COALESCE(e.receivable_balance, 0)) as net_balance
FROM employees e
ORDER BY e.id;

-- تێبینی: دوای گواستنەوە، دەتوانیت تەیبڵی employee_payments بسڕیتەوە
-- بەڵام پێشتر دڵنیابوونەوە لەوەی کە هەموو داتا گواستراونەتەوە!

