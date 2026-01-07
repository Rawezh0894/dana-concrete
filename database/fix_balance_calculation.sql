-- کوئری بۆ چاککردنی هەژمارکردنی باڵانس
-- ئەم کوئریانە باڵانسەکان بە درووستی لە خەرجیەکان دەهەژمێرێتەوە

-- 1. پاککردنەوەی باڵانسەکانی هەموو کارمەندەکان
UPDATE `employees` 
SET `payable_balance` = 0, 
    `receivable_balance` = 0;

-- 2. دووبارە هەژمارکردنەوەی باڵانسەکان بە ترتیبی دروست
-- یەکەم: مووچە، بەخشیش، کاروانحیسابی (زیاد بە payable_balance)
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

-- دووەم: پێشەکی، کەمکردنەوە، سزا (کەمکردنەوە لە payable_balance)
-- ئەم کوئرییە بە شێوەیەکی خۆکار لۆجیکی advance/deduction/penalty جێبەجێ دەکات
DELIMITER $$

DROP PROCEDURE IF EXISTS recalculate_advances_deductions_penalties$$
CREATE PROCEDURE recalculate_advances_deductions_penalties()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_employee_id INT;
    DECLARE v_amount DECIMAL(10,2);
    DECLARE v_expense_type VARCHAR(20);
    DECLARE v_current_payable DECIMAL(15,2);
    DECLARE v_current_receivable DECIMAL(15,2);
    
    -- Process expenses in order: advance, deduction, penalty
    -- Order by created_at to maintain chronological order
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
        
        -- Get current balances
        SELECT COALESCE(payable_balance, 0), COALESCE(receivable_balance, 0)
        INTO v_current_payable, v_current_receivable
        FROM employees
        WHERE id = v_employee_id;
        
        -- Apply advance/deduction/penalty logic (same logic for all)
        -- First reduce payable balance, then add excess to receivable balance
        IF v_current_payable >= v_amount THEN
            -- All comes from payable balance
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
CALL recalculate_advances_deductions_penalties();

-- سڕینەوەی procedure دوای بەکارهێنان
DROP PROCEDURE IF EXISTS recalculate_advances_deductions_penalties;

-- 3. پیشاندانی کۆی باڵانسەکان بۆ دڵنیابوون
SELECT 
    e.id,
    e.name,
    COALESCE(e.payable_balance, 0) as payable_balance,
    COALESCE(e.receivable_balance, 0) as receivable_balance,
    (COALESCE(e.payable_balance, 0) - COALESCE(e.receivable_balance, 0)) as net_balance,
    -- Calculate expected balance from expenses
    (SELECT COALESCE(SUM(amount), 0) FROM employee_expenses WHERE employee_id = e.id AND expense_type IN ('salary', 'bonus', 'overtime')) as total_salary_bonus_overtime,
    (SELECT COALESCE(SUM(amount), 0) FROM employee_expenses WHERE employee_id = e.id AND expense_type IN ('advance', 'deduction', 'penalty')) as total_advance_deduction_penalty,
    (SELECT COALESCE(SUM(amount), 0) FROM employee_expenses WHERE employee_id = e.id AND expense_type IN ('salary', 'bonus', 'overtime')) - 
    (SELECT COALESCE(SUM(amount), 0) FROM employee_expenses WHERE employee_id = e.id AND expense_type IN ('advance', 'deduction', 'penalty')) as expected_net_balance
FROM employees e
ORDER BY e.id;

