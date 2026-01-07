-- کوئری بۆ دووبارە هەژمارکردنەوەی باڵانسەکانی کارمەندەکان
-- ئەم کوئریانە باڵانسەکان بە درووستی لە خەرجیەکان دەهەژمێرێتەوە

-- 1. پاککردنەوەی باڵانسەکانی هەموو کارمەندەکان
UPDATE `employees` 
SET `payable_balance` = 0, 
    `receivable_balance` = 0;

-- 2. دووبارە هەژمارکردنەوەی باڵانسەکان لە خەرجیەکان
-- بۆ مووچە، بەخشیش، کاروانحیسابی - زیادکردن بە payable_balance
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

-- بۆ پێشەکی - زیادکردن بە receivable_balance
UPDATE `employees` e
INNER JOIN (
    SELECT 
        employee_id,
        SUM(amount) as total_amount
    FROM `employee_expenses`
    WHERE expense_type = 'advance'
    GROUP BY employee_id
) calc ON e.id = calc.employee_id
SET e.receivable_balance = COALESCE(e.receivable_balance, 0) + calc.total_amount;

-- بۆ کەمکردنەوە و سزا - کەمکردنەوە لە payable_balance یان زیادکردن بە receivable_balance
-- ئەم کوئرییە بە شێوەیەکی خۆکار لۆجیکی deduction/penalty جێبەجێ دەکات
DELIMITER $$

DROP PROCEDURE IF EXISTS recalculate_deductions_penalties$$
CREATE PROCEDURE recalculate_deductions_penalties()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_employee_id INT;
    DECLARE v_amount DECIMAL(10,2);
    DECLARE v_current_payable DECIMAL(15,2);
    DECLARE v_current_receivable DECIMAL(15,2);
    
    DECLARE cur CURSOR FOR 
        SELECT employee_id, amount 
        FROM employee_expenses 
        WHERE expense_type IN ('deduction', 'penalty')
        ORDER BY employee_id, created_at;
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN cur;
    
    read_loop: LOOP
        FETCH cur INTO v_employee_id, v_amount;
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        -- Get current balances
        SELECT COALESCE(payable_balance, 0), COALESCE(receivable_balance, 0)
        INTO v_current_payable, v_current_receivable
        FROM employees
        WHERE id = v_employee_id;
        
        -- Apply deduction/penalty logic
        IF v_current_payable >= v_amount THEN
            -- All deduction comes from payable balance
            UPDATE employees
            SET payable_balance = v_current_payable - v_amount
            WHERE id = v_employee_id;
        ELSE
            -- Payable balance becomes 0, excess goes to receivable
            UPDATE employees
            SET payable_balance = 0,
                receivable_balance = v_current_receivable + (v_amount - v_current_payable)
            WHERE id = v_employee_id;
        END IF;
        
        -- Get updated balances for next iteration
        SELECT COALESCE(payable_balance, 0), COALESCE(receivable_balance, 0)
        INTO v_current_payable, v_current_receivable
        FROM employees
        WHERE id = v_employee_id;
        
    END LOOP;
    
    CLOSE cur;
END$$

DELIMITER ;

-- جێبەجێکردنی procedure
CALL recalculate_deductions_penalties();

-- سڕینەوەی procedure دوای بەکارهێنان
DROP PROCEDURE IF EXISTS recalculate_deductions_penalties;

-- 3. پیشاندانی کۆی باڵانسەکان بۆ دڵنیابوون
SELECT 
    COUNT(*) as total_employees,
    SUM(payable_balance) as total_payable,
    SUM(receivable_balance) as total_receivable,
    SUM(payable_balance) - SUM(receivable_balance) as net_balance
FROM employees;

