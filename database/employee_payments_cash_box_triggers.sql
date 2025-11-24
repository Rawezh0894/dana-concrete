-- ---------------------------------------------
-- Employee payment cash-box trigger refresh
-- Refresh only the triggers that sync employee
-- payments with the cash_box ledger.
-- ---------------------------------------------

DROP TRIGGER IF EXISTS `trg_after_insert_employee_payment_cash_box`;
DROP TRIGGER IF EXISTS `trg_after_insert_employee_payments`;
DROP TRIGGER IF EXISTS `trg_before_delete_employee_payment_cash_box`;
DROP TRIGGER IF EXISTS `trg_before_delete_employee_payments`;
DROP TRIGGER IF EXISTS `trg_before_update_employee_payment_cash_box`;
DROP TRIGGER IF EXISTS `trg_before_update_employee_payments`;

DELIMITER $$

CREATE TRIGGER `trg_after_insert_employee_payment_cash_box`
AFTER INSERT ON `employee_payments`
FOR EACH ROW
BEGIN
    DECLARE v_employee_name VARCHAR(255) DEFAULT '';
    DECLARE v_reference_tag VARCHAR(64) DEFAULT '';
    DECLARE v_note_text TEXT DEFAULT '';
    DECLARE v_effective_date DATETIME;

    IF NEW.total > 0 THEN
        SET v_employee_name = (
            SELECT name FROM employees WHERE id = NEW.employee_id LIMIT 1
        );
        SET v_reference_tag = CONCAT('[REF:EMP_PAY#', NEW.id, '#IQD]');
        SET v_effective_date = IFNULL(NEW.created_at, NOW());

        SET v_note_text = CONCAT(
            'پارەدان بە کارمەند | ',
            'ناو: ', IFNULL(v_employee_name, 'نەناسراو'), ' | ',
            'مانگی مووچە: ', IFNULL(NEW.pay_month, '-'), ' | ',
            'بڕ: ', NEW.total, ' د.ع | ',
            v_reference_tag
        );

        INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
        VALUES (v_effective_date, 'withdraw', NEW.total, 0, 'دینار', v_note_text, NULL);
    END IF;
END$$

CREATE TRIGGER `trg_before_update_employee_payment_cash_box`
BEFORE UPDATE ON `employee_payments`
FOR EACH ROW
BEGIN
    DECLARE v_employee_name VARCHAR(255) DEFAULT '';
    DECLARE v_reference_tag VARCHAR(64) DEFAULT '';
    DECLARE v_note_text TEXT DEFAULT '';
    DECLARE v_effective_date DATETIME;

    DELETE FROM cash_box
    WHERE note LIKE CONCAT('%[REF:EMP_PAY#', OLD.id, '#IQD]%');

    IF NEW.total > 0 THEN
        SET v_employee_name = (
            SELECT name FROM employees WHERE id = NEW.employee_id LIMIT 1
        );
        SET v_reference_tag = CONCAT('[REF:EMP_PAY#', NEW.id, '#IQD]');
        SET v_effective_date = IFNULL(NEW.created_at, NOW());

        SET v_note_text = CONCAT(
            'پارەدان بە کارمەند | ',
            'ناو: ', IFNULL(v_employee_name, 'نەناسراو'), ' | ',
            'مانگی مووچە: ', IFNULL(NEW.pay_month, '-'), ' | ',
            'بڕ: ', NEW.total, ' د.ع | ',
            v_reference_tag
        );

        INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
        VALUES (v_effective_date, 'withdraw', NEW.total, 0, 'دینار', v_note_text, NULL);
    END IF;
END$$

CREATE TRIGGER `trg_before_delete_employee_payment_cash_box`
BEFORE DELETE ON `employee_payments`
FOR EACH ROW
BEGIN
    DELETE FROM cash_box
    WHERE note LIKE CONCAT('%[REF:EMP_PAY#', OLD.id, '#IQD]%');
END$$

DELIMITER ;








