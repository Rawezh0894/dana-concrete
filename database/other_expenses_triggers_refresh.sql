-- ---------------------------------------------
-- Other expenses trigger refresh script
-- Drops existing triggers and recreates the latest
-- definitions that normalize cash movements
-- ---------------------------------------------

DROP TRIGGER IF EXISTS `trg_after_insert_other_expenses`;
DROP TRIGGER IF EXISTS `trg_after_update_other_expenses`;
DROP TRIGGER IF EXISTS `trg_before_delete_other_expenses`;
DROP TRIGGER IF EXISTS `trg_before_update_other_expenses`;

DELIMITER $$

CREATE TRIGGER `trg_after_insert_other_expenses` AFTER INSERT ON `other_expenses`
FOR EACH ROW
BEGIN
    DECLARE v_person_name VARCHAR(255) DEFAULT '';
    DECLARE v_employee_name VARCHAR(255) DEFAULT '';
    DECLARE v_car_name VARCHAR(255) DEFAULT '';
    DECLARE v_material_name VARCHAR(255) DEFAULT '';
    DECLARE v_note_text TEXT DEFAULT '';
    DECLARE v_reference_tag VARCHAR(64) DEFAULT '';
    DECLARE v_paid_usd DECIMAL(14,2) DEFAULT 0;
    DECLARE v_paid_iqd DECIMAL(20,2) DEFAULT 0;

    IF NEW.person_id IS NOT NULL THEN
        SELECT name INTO v_person_name FROM other_expense_persons WHERE id = NEW.person_id;
    END IF;
    IF NEW.employee_id IS NOT NULL THEN
        SELECT name INTO v_employee_name FROM employees WHERE id = NEW.employee_id;
    END IF;
    IF NEW.car_id IS NOT NULL THEN
        SELECT name INTO v_car_name FROM cars WHERE id = NEW.car_id;
    END IF;
    IF NEW.material_id IS NOT NULL THEN
        SELECT name INTO v_material_name FROM list_materials WHERE id = NEW.material_id;
    END IF;

    IF NEW.payment_type = 'نەقد' THEN
        SET v_paid_usd = CASE
            WHEN NEW.paid_usd > 0 THEN NEW.paid_usd
            WHEN NEW.currency_type = 'دۆلار' THEN IFNULL(NEW.amount_usd, 0) - IFNULL(NEW.remaining_usd, 0)
            ELSE 0
        END;
        IF v_paid_usd <= 0 AND NEW.currency_type = 'دۆلار' THEN
            SET v_paid_usd = IFNULL(NEW.amount_usd, 0);
        END IF;
        IF v_paid_usd < 0 THEN
            SET v_paid_usd = 0;
        END IF;

        SET v_paid_iqd = CASE
            WHEN NEW.paid_iqd > 0 THEN NEW.paid_iqd
            WHEN NEW.currency_type = 'دینار' THEN IFNULL(NEW.amount_iqd, 0) - IFNULL(NEW.remaining_iqd, 0)
            ELSE 0
        END;
        IF v_paid_iqd <= 0 AND NEW.currency_type = 'دینار' THEN
            SET v_paid_iqd = IFNULL(NEW.amount_iqd, 0);
        END IF;
        IF v_paid_iqd < 0 THEN
            SET v_paid_iqd = 0;
        END IF;

        IF v_paid_usd > 0 THEN
            SET v_reference_tag = CONCAT(' [REF:OTHEREXP#', NEW.id, '#USD]');
            SET v_note_text = CONCAT(
                'خەرجی تر | ',
                'جۆری خەرجی: ', IFNULL(NEW.expense_type, '-'), ' | ',
                'مەبەست: ', IFNULL(NEW.purpose, '-'), ' | ',
                'ژمارەی پسووڵە: ', IFNULL(NEW.invoice_number, '-'), ' | '
            );
            IF v_person_name != '' THEN
                SET v_note_text = CONCAT(v_note_text, 'کەس: ', v_person_name, ' | ');
            END IF;
            IF v_employee_name != '' THEN
                SET v_note_text = CONCAT(v_note_text, 'کارمەند: ', v_employee_name, ' | ');
            END IF;
            IF v_car_name != '' THEN
                SET v_note_text = CONCAT(v_note_text, 'ئۆتۆمبێل: ', v_car_name, ' | ');
            END IF;
            IF v_material_name != '' THEN
                SET v_note_text = CONCAT(v_note_text, 'مەواد: ', v_material_name, ' | ');
            END IF;
            SET v_note_text = CONCAT(
                v_note_text,
                'کۆی خەرجی: $', IFNULL(NEW.amount_usd, 0), ' | ',
                'پارەی دراو: $', v_paid_usd, ' | ',
                'پارەی ماوە: $', IFNULL(NEW.remaining_usd, 0)
            );
            SET v_note_text = CONCAT(v_note_text, v_reference_tag);

            INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
            VALUES (NEW.date, 'withdraw', 0, v_paid_usd, 'دۆلار', v_note_text, NULL);
        END IF;

        IF v_paid_iqd > 0 THEN
            SET v_reference_tag = CONCAT(' [REF:OTHEREXP#', NEW.id, '#IQD]');
            SET v_note_text = CONCAT(
                'خەرجی تر | ',
                'جۆری خەرجی: ', IFNULL(NEW.expense_type, '-'), ' | ',
                'مەبەست: ', IFNULL(NEW.purpose, '-'), ' | ',
                'ژمارەی پسووڵە: ', IFNULL(NEW.invoice_number, '-'), ' | '
            );
            IF v_person_name != '' THEN
                SET v_note_text = CONCAT(v_note_text, 'کەس: ', v_person_name, ' | ');
            END IF;
            IF v_employee_name != '' THEN
                SET v_note_text = CONCAT(v_note_text, 'کارمەند: ', v_employee_name, ' | ');
            END IF;
            IF v_car_name != '' THEN
                SET v_note_text = CONCAT(v_note_text, 'ئۆتۆمبێل: ', v_car_name, ' | ');
            END IF;
            IF v_material_name != '' THEN
                SET v_note_text = CONCAT(v_note_text, 'مەواد: ', v_material_name, ' | ');
            END IF;
            SET v_note_text = CONCAT(
                v_note_text,
                'کۆی خەرجی: ', IFNULL(NEW.amount_iqd, 0), ' د.ع | ',
                'پارەی دراو: ', v_paid_iqd, ' د.ع | ',
                'پارەی ماوە: ', IFNULL(NEW.remaining_iqd, 0), ' د.ع'
            );
            SET v_note_text = CONCAT(v_note_text, v_reference_tag);

            INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
            VALUES (NEW.date, 'withdraw', v_paid_iqd, 0, 'دینار', v_note_text, NULL);
        END IF;
    END IF;

    IF NEW.expense_type = 'بەکارهێنانی گاز' AND NEW.gas_liters IS NOT NULL AND NEW.gas_liters > 0 THEN
        UPDATE bins_silos
        SET amount = amount - NEW.gas_liters
        WHERE type = 'تەنکی' AND material_type = 'گاز'
        LIMIT 1;
    END IF;

    IF NEW.expense_type = 'بەکارهێنانی کاڵای کۆگا' AND NEW.material_id IS NOT NULL AND NEW.base_material_quantity IS NOT NULL AND NEW.base_material_quantity > 0 THEN
        UPDATE list_materials
        SET quantity = quantity - NEW.base_material_quantity
        WHERE id = NEW.material_id;
    END IF;
END$$

CREATE TRIGGER `trg_after_update_other_expenses` AFTER UPDATE ON `other_expenses`
FOR EACH ROW
BEGIN
    DECLARE v_person_name VARCHAR(255) DEFAULT '';
    DECLARE v_employee_name VARCHAR(255) DEFAULT '';
    DECLARE v_car_name VARCHAR(255) DEFAULT '';
    DECLARE v_material_name VARCHAR(255) DEFAULT '';
    DECLARE v_note_text TEXT DEFAULT '';
    DECLARE v_reference_tag VARCHAR(64) DEFAULT '';
    DECLARE v_paid_usd DECIMAL(14,2) DEFAULT 0;
    DECLARE v_paid_iqd DECIMAL(20,2) DEFAULT 0;

    IF NEW.person_id IS NOT NULL THEN
        SELECT name INTO v_person_name FROM other_expense_persons WHERE id = NEW.person_id;
    END IF;
    IF NEW.employee_id IS NOT NULL THEN
        SELECT name INTO v_employee_name FROM employees WHERE id = NEW.employee_id;
    END IF;
    IF NEW.car_id IS NOT NULL THEN
        SELECT name INTO v_car_name FROM cars WHERE id = NEW.car_id;
    END IF;
    IF NEW.material_id IS NOT NULL THEN
        SELECT name INTO v_material_name FROM list_materials WHERE id = NEW.material_id;
    END IF;

    IF NEW.payment_type = 'نەقد' THEN
        SET v_paid_usd = CASE
            WHEN NEW.paid_usd > 0 THEN NEW.paid_usd
            WHEN NEW.currency_type = 'دۆلار' THEN IFNULL(NEW.amount_usd, 0) - IFNULL(NEW.remaining_usd, 0)
            ELSE 0
        END;
        IF v_paid_usd <= 0 AND NEW.currency_type = 'دۆلار' THEN
            SET v_paid_usd = IFNULL(NEW.amount_usd, 0);
        END IF;
        IF v_paid_usd < 0 THEN
            SET v_paid_usd = 0;
        END IF;

        SET v_paid_iqd = CASE
            WHEN NEW.paid_iqd > 0 THEN NEW.paid_iqd
            WHEN NEW.currency_type = 'دینار' THEN IFNULL(NEW.amount_iqd, 0) - IFNULL(NEW.remaining_iqd, 0)
            ELSE 0
        END;
        IF v_paid_iqd <= 0 AND NEW.currency_type = 'دینار' THEN
            SET v_paid_iqd = IFNULL(NEW.amount_iqd, 0);
        END IF;
        IF v_paid_iqd < 0 THEN
            SET v_paid_iqd = 0;
        END IF;

        IF v_paid_usd > 0 THEN
            SET v_reference_tag = CONCAT(' [REF:OTHEREXP#', NEW.id, '#USD]');
            SET v_note_text = CONCAT(
                'خەرجی تر | ',
                'جۆری خەرجی: ', IFNULL(NEW.expense_type, '-'), ' | ',
                'مەبەست: ', IFNULL(NEW.purpose, '-'), ' | ',
                'ژمارەی پسووڵە: ', IFNULL(NEW.invoice_number, '-'), ' | '
            );
            IF v_person_name != '' THEN
                SET v_note_text = CONCAT(v_note_text, 'کەس: ', v_person_name, ' | ');
            END IF;
            IF v_employee_name != '' THEN
                SET v_note_text = CONCAT(v_note_text, 'کارمەند: ', v_employee_name, ' | ');
            END IF;
            IF v_car_name != '' THEN
                SET v_note_text = CONCAT(v_note_text, 'ئۆتۆمبێل: ', v_car_name, ' | ');
            END IF;
            IF v_material_name != '' THEN
                SET v_note_text = CONCAT(v_note_text, 'مەواد: ', v_material_name, ' | ');
            END IF;
            SET v_note_text = CONCAT(
                v_note_text,
                'کۆی خەرجی: $', IFNULL(NEW.amount_usd, 0), ' | ',
                'پارەی دراو: $', v_paid_usd, ' | ',
                'پارەی ماوە: $', IFNULL(NEW.remaining_usd, 0)
            );
            SET v_note_text = CONCAT(v_note_text, v_reference_tag);

            INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
            VALUES (NEW.date, 'withdraw', 0, v_paid_usd, 'دۆلار', v_note_text, NULL);
        END IF;

        IF v_paid_iqd > 0 THEN
            SET v_reference_tag = CONCAT(' [REF:OTHEREXP#', NEW.id, '#IQD]');
            SET v_note_text = CONCAT(
                'خەرجی تر | ',
                'جۆری خەرجی: ', IFNULL(NEW.expense_type, '-'), ' | ',
                'مەبەست: ', IFNULL(NEW.purpose, '-'), ' | ',
                'ژمارەی پسووڵە: ', IFNULL(NEW.invoice_number, '-'), ' | '
            );
            IF v_person_name != '' THEN
                SET v_note_text = CONCAT(v_note_text, 'کەس: ', v_person_name, ' | ');
            END IF;
            IF v_employee_name != '' THEN
                SET v_note_text = CONCAT(v_note_text, 'کارمەند: ', v_employee_name, ' | ');
            END IF;
            IF v_car_name != '' THEN
                SET v_note_text = CONCAT(v_note_text, 'ئۆتۆمبێل: ', v_car_name, ' | ');
            END IF;
            IF v_material_name != '' THEN
                SET v_note_text = CONCAT(v_note_text, 'مەواد: ', v_material_name, ' | ');
            END IF;
            SET v_note_text = CONCAT(
                v_note_text,
                'کۆی خەرجی: ', IFNULL(NEW.amount_iqd, 0), ' د.ع | ',
                'پارەی دراو: ', v_paid_iqd, ' د.ع | ',
                'پارەی ماوە: ', IFNULL(NEW.remaining_iqd, 0), ' د.ع'
            );
            SET v_note_text = CONCAT(v_note_text, v_reference_tag);

            INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
            VALUES (NEW.date, 'withdraw', v_paid_iqd, 0, 'دینار', v_note_text, NULL);
        END IF;
    END IF;

    IF NEW.expense_type = 'بەکارهێنانی گاز' AND NEW.gas_liters IS NOT NULL AND NEW.gas_liters > 0 THEN
        UPDATE bins_silos
        SET amount = amount - NEW.gas_liters
        WHERE type = 'تەنکی' AND material_type = 'گاز'
        LIMIT 1;
    END IF;

    IF NEW.expense_type = 'بەکارهێنانی کاڵای کۆگا' AND NEW.material_id IS NOT NULL AND NEW.base_material_quantity IS NOT NULL AND NEW.base_material_quantity > 0 THEN
        UPDATE list_materials
        SET quantity = quantity - NEW.base_material_quantity
        WHERE id = NEW.material_id;
    END IF;
END$$

CREATE TRIGGER `trg_before_delete_other_expenses` BEFORE DELETE ON `other_expenses`
FOR EACH ROW
BEGIN
    DECLARE v_paid_usd DECIMAL(14,2) DEFAULT 0;
    DECLARE v_paid_iqd DECIMAL(20,2) DEFAULT 0;

    SET v_paid_usd = CASE
        WHEN OLD.paid_usd > 0 THEN OLD.paid_usd
        WHEN OLD.currency_type = 'دۆلار' THEN IFNULL(OLD.amount_usd, 0) - IFNULL(OLD.remaining_usd, 0)
        ELSE 0
    END;
    IF v_paid_usd <= 0 AND OLD.currency_type = 'دۆلار' THEN
        SET v_paid_usd = IFNULL(OLD.amount_usd, 0);
    END IF;
    IF v_paid_usd < 0 THEN
        SET v_paid_usd = 0;
    END IF;

    SET v_paid_iqd = CASE
        WHEN OLD.paid_iqd > 0 THEN OLD.paid_iqd
        WHEN OLD.currency_type = 'دینار' THEN IFNULL(OLD.amount_iqd, 0) - IFNULL(OLD.remaining_iqd, 0)
        ELSE 0
    END;
    IF v_paid_iqd <= 0 AND OLD.currency_type = 'دینار' THEN
        SET v_paid_iqd = IFNULL(OLD.amount_iqd, 0);
    END IF;
    IF v_paid_iqd < 0 THEN
        SET v_paid_iqd = 0;
    END IF;

    IF OLD.payment_type = 'نەقد' THEN
        IF v_paid_usd > 0 THEN
            DELETE FROM cash_box
            WHERE `type` = 'withdraw'
              AND currency = 'دۆلار'
              AND (
                    note LIKE CONCAT('%[REF:OTHEREXP#', OLD.id, '#USD]%')
                    OR note LIKE CONCAT('%ژمارەی پسووڵە: ', IFNULL(OLD.invoice_number, '-'), '%')
                    OR note LIKE CONCAT('%invoice ', IFNULL(OLD.invoice_number, ''), '%')
              )
              AND (
                    amount_usd = v_paid_usd
                    OR note LIKE CONCAT('%[REF:OTHEREXP#', OLD.id, '#USD]%')
              );
        END IF;
        IF v_paid_iqd > 0 THEN
            DELETE FROM cash_box
            WHERE `type` = 'withdraw'
              AND currency = 'دینار'
              AND (
                    note LIKE CONCAT('%[REF:OTHEREXP#', OLD.id, '#IQD]%')
                    OR note LIKE CONCAT('%ژمارەی پسووڵە: ', IFNULL(OLD.invoice_number, '-'), '%')
                    OR note LIKE CONCAT('%invoice ', IFNULL(OLD.invoice_number, ''), '%')
              )
              AND (
                    amount_iqd = v_paid_iqd
                    OR note LIKE CONCAT('%[REF:OTHEREXP#', OLD.id, '#IQD]%')
              );
        END IF;
    END IF;

    IF OLD.expense_type = 'بەکارهێنانی گاز' AND OLD.gas_liters IS NOT NULL AND OLD.gas_liters > 0 THEN
        UPDATE bins_silos
        SET amount = amount + OLD.gas_liters
        WHERE type = 'تەنکی' AND material_type = 'گاز'
        LIMIT 1;
    END IF;

    IF OLD.expense_type = 'بەکارهێنانی کاڵای کۆگا' AND OLD.material_id IS NOT NULL AND OLD.base_material_quantity IS NOT NULL AND OLD.base_material_quantity > 0 THEN
        UPDATE list_materials
        SET quantity = quantity + OLD.base_material_quantity
        WHERE id = OLD.material_id;
    END IF;
END$$

CREATE TRIGGER `trg_before_update_other_expenses` BEFORE UPDATE ON `other_expenses`
FOR EACH ROW
BEGIN
    DECLARE v_paid_usd DECIMAL(14,2) DEFAULT 0;
    DECLARE v_paid_iqd DECIMAL(20,2) DEFAULT 0;

    SET v_paid_usd = CASE
        WHEN OLD.paid_usd > 0 THEN OLD.paid_usd
        WHEN OLD.currency_type = 'دۆلار' THEN IFNULL(OLD.amount_usd, 0) - IFNULL(OLD.remaining_usd, 0)
        ELSE 0
    END;
    IF v_paid_usd <= 0 AND OLD.currency_type = 'دۆلار' THEN
        SET v_paid_usd = IFNULL(OLD.amount_usd, 0);
    END IF;
    IF v_paid_usd < 0 THEN
        SET v_paid_usd = 0;
    END IF;

    SET v_paid_iqd = CASE
        WHEN OLD.paid_iqd > 0 THEN OLD.paid_iqd
        WHEN OLD.currency_type = 'دینار' THEN IFNULL(OLD.amount_iqd, 0) - IFNULL(OLD.remaining_iqd, 0)
        ELSE 0
    END;
    IF v_paid_iqd <= 0 AND OLD.currency_type = 'دینار' THEN
        SET v_paid_iqd = IFNULL(OLD.amount_iqd, 0);
    END IF;
    IF v_paid_iqd < 0 THEN
        SET v_paid_iqd = 0;
    END IF;

    IF OLD.payment_type = 'نەقد' THEN
        IF v_paid_usd > 0 THEN
            DELETE FROM cash_box
            WHERE `type` = 'withdraw'
              AND currency = 'دۆلار'
              AND (
                    note LIKE CONCAT('%[REF:OTHEREXP#', OLD.id, '#USD]%')
                    OR note LIKE CONCAT('%ژمارەی پسووڵە: ', IFNULL(OLD.invoice_number, '-'), '%')
                    OR note LIKE CONCAT('%invoice ', IFNULL(OLD.invoice_number, ''), '%')
              )
              AND (
                    amount_usd = v_paid_usd
                    OR note LIKE CONCAT('%[REF:OTHEREXP#', OLD.id, '#USD]%')
              );
        END IF;
        IF v_paid_iqd > 0 THEN
            DELETE FROM cash_box
            WHERE `type` = 'withdraw'
              AND currency = 'دینار'
              AND (
                    note LIKE CONCAT('%[REF:OTHEREXP#', OLD.id, '#IQD]%')
                    OR note LIKE CONCAT('%ژمارەی پسووڵە: ', IFNULL(OLD.invoice_number, '-'), '%')
                    OR note LIKE CONCAT('%invoice ', IFNULL(OLD.invoice_number, ''), '%')
              )
              AND (
                    amount_iqd = v_paid_iqd
                    OR note LIKE CONCAT('%[REF:OTHEREXP#', OLD.id, '#IQD]%')
              );
        END IF;
    END IF;

    IF OLD.expense_type = 'بەکارهێنانی گاز' AND OLD.gas_liters IS NOT NULL AND OLD.gas_liters > 0 THEN
        UPDATE bins_silos
        SET amount = amount + OLD.gas_liters
        WHERE type = 'تەنکی' AND material_type = 'گاز'
        LIMIT 1;
    END IF;

    IF OLD.expense_type = 'بەکارهێنانی کاڵای کۆگا' AND OLD.material_id IS NOT NULL AND OLD.base_material_quantity IS NOT NULL AND OLD.base_material_quantity > 0 THEN
        UPDATE list_materials
        SET quantity = quantity + OLD.base_material_quantity
        WHERE id = OLD.material_id;
    END IF;
END$$

DELIMITER ;

