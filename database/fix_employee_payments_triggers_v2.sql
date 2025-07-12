-- Fix employee_payments triggers to properly handle cash_box operations
-- Drop existing triggers first
DROP TRIGGER IF EXISTS `trg_after_insert_employee_payments`;
DROP TRIGGER IF EXISTS `trg_before_delete_employee_payments`;
DROP TRIGGER IF EXISTS `trg_before_update_employee_payments`;

-- Create corrected triggers
DELIMITER $$

-- Trigger for INSERT - add withdrawal to cash_box
CREATE TRIGGER `trg_after_insert_employee_payments` AFTER INSERT ON `employee_payments` FOR EACH ROW 
BEGIN
    INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
    VALUES (NEW.created_at, 'withdraw', NEW.total, 0, 'دینار', CONCAT('پارەدان بە کارمەند: ', NEW.employee_id), NULL);
END$$

-- Trigger for DELETE - add deposit to cash_box (return money)
CREATE TRIGGER `trg_before_delete_employee_payments` BEFORE DELETE ON `employee_payments` FOR EACH ROW 
BEGIN
    INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
    VALUES (NOW(), 'deposit', OLD.total, 0, 'دینار', CONCAT('گەڕانەوەی پارەدان بە کارمەند: ', OLD.employee_id), NULL);
END$$

-- Trigger for UPDATE - handle difference only
CREATE TRIGGER `trg_before_update_employee_payments` BEFORE UPDATE ON `employee_payments` FOR EACH ROW 
BEGIN
    DECLARE difference DECIMAL(15,2);
    
    -- Calculate the difference
    SET difference = NEW.total - OLD.total;
    
    -- If there's a difference, handle it
    IF difference != 0 THEN
        IF difference > 0 THEN
            -- New amount is higher - withdraw the difference
            INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
            VALUES (NOW(), 'withdraw', difference, 0, 'دینار', CONCAT('زیادکردنی پارەدان بە کارمەند: ', NEW.employee_id), NULL);
        ELSE
            -- New amount is lower - deposit the difference (return money)
            INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
            VALUES (NOW(), 'deposit', ABS(difference), 0, 'دینار', CONCAT('کەمکردنی پارەدان بە کارمەند: ', NEW.employee_id), NULL);
        END IF;
    END IF;
END$$

DELIMITER ; 