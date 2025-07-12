-- Fix employee_payments triggers to use total amount instead of just salary
-- Drop existing triggers first
DROP TRIGGER IF EXISTS `trg_after_insert_employee_payments`;
DROP TRIGGER IF EXISTS `trg_before_delete_employee_payments`;
DROP TRIGGER IF EXISTS `trg_before_update_employee_payments`;

-- Create corrected triggers
DELIMITER $$

CREATE TRIGGER `trg_after_insert_employee_payments` AFTER INSERT ON `employee_payments` FOR EACH ROW BEGIN
    INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
    VALUES (NEW.created_at, 'withdraw', NEW.total, 0, 'دینار', CONCAT('پارەدان بە کارمەند: ', NEW.employee_id), NULL);
END$$

CREATE TRIGGER `trg_before_delete_employee_payments` BEFORE DELETE ON `employee_payments` FOR EACH ROW BEGIN
    DELETE FROM cash_box
    WHERE `date` = OLD.created_at AND `type` = 'withdraw' AND amount_iqd = OLD.total AND currency = 'دینار' AND note = CONCAT('پارەدان بە کارمەند: ', OLD.employee_id);
END$$

CREATE TRIGGER `trg_before_update_employee_payments` BEFORE UPDATE ON `employee_payments` FOR EACH ROW BEGIN
    -- سڕینەوەی مامەڵەی کۆن
    DELETE FROM cash_box
    WHERE `date` = OLD.created_at AND `type` = 'withdraw' AND amount_iqd = OLD.total AND currency = 'دینار' AND note = CONCAT('پارەدان بە کارمەند: ', OLD.employee_id);

    -- زیادکردنی مامەڵەی نوێ
    INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
    VALUES (NEW.created_at, 'withdraw', NEW.total, 0, 'دینار', CONCAT('پارەدان بە کارمەند: ', NEW.employee_id), NULL);
END$$

DELIMITER ; 