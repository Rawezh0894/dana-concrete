-- ---------------------------------------------
-- Cash-box trigger refresh script
-- Includes drop statements for legacy triggers
-- and the latest trigger definitions with
-- reference tags to keep cash_box rows in sync
-- ---------------------------------------------

-- Drop obsolete validation triggers
DROP TRIGGER IF EXISTS `trg_before_delete_cash_box`;
DROP TRIGGER IF EXISTS `trg_before_update_cash_box`;
DROP TRIGGER IF EXISTS `trg_before_withdraw_cash_box`;

DROP TRIGGER IF EXISTS `trg_after_insert_sale_cash_box`;
DROP TRIGGER IF EXISTS `trg_before_delete_sale_cash_box`;
DROP TRIGGER IF EXISTS `trg_before_update_sale_cash_box`;
DROP TRIGGER IF EXISTS `trg_after_insert_purchase_cash_box`;
DROP TRIGGER IF EXISTS `trg_before_delete_purchase_cash_box`;
DROP TRIGGER IF EXISTS `trg_before_update_purchase_cash_box`;

-- Drop other cash-related triggers (customer/company debts, employees, expenses, materials)
DROP TRIGGER IF EXISTS `trg_after_insert_customer_debt_payments`;
DROP TRIGGER IF EXISTS `trg_before_delete_customer_debt_payments`;
DROP TRIGGER IF EXISTS `trg_before_update_customer_debt_payments`;
DROP TRIGGER IF EXISTS `trg_after_insert_debt_payments`;
DROP TRIGGER IF EXISTS `trg_before_delete_debt_payments`;
DROP TRIGGER IF EXISTS `trg_before_update_debt_payments`;
DROP TRIGGER IF EXISTS `trg_after_insert_employee_payment_cash_box`;
DROP TRIGGER IF EXISTS `trg_after_insert_employee_payments`;
DROP TRIGGER IF EXISTS `trg_before_delete_employee_payment_cash_box`;
DROP TRIGGER IF EXISTS `trg_before_delete_employee_payments`;
DROP TRIGGER IF EXISTS `trg_before_update_employee_payment_cash_box`;
DROP TRIGGER IF EXISTS `trg_before_update_employee_payments`;
DROP TRIGGER IF EXISTS `trg_after_insert_other_expenses`;
DROP TRIGGER IF EXISTS `trg_after_update_other_expenses`;
DROP TRIGGER IF EXISTS `trg_before_delete_other_expenses`;
DROP TRIGGER IF EXISTS `trg_before_update_other_expenses`;
DROP TRIGGER IF EXISTS `trg_after_insert_person_other_expenses_debt_payments`;
DROP TRIGGER IF EXISTS `trg_before_delete_person_other_expenses_debt_payments`;
DROP TRIGGER IF EXISTS `trg_before_update_person_other_expenses_debt_payments`;
DROP TRIGGER IF EXISTS `after_purchase_materials_delete`;
DROP TRIGGER IF EXISTS `after_purchase_materials_insert`;
DROP TRIGGER IF EXISTS `after_purchase_materials_update`;

DELIMITER $$

CREATE TRIGGER `trg_after_insert_sale_cash_box` AFTER INSERT ON `sales`
FOR EACH ROW
BEGIN
    DECLARE v_customer_name VARCHAR(255) DEFAULT '';
    DECLARE v_formula_name VARCHAR(255) DEFAULT '';
    DECLARE v_note_text TEXT DEFAULT '';
    DECLARE v_reference_tag VARCHAR(64) DEFAULT '';

    SELECT name INTO v_customer_name FROM customers WHERE id = NEW.customer_id;
    SELECT name INTO v_formula_name FROM concrete_formulas WHERE id = NEW.formula_id;

    IF NEW.payment_type = 'نەقد' THEN
        IF NEW.amount_paid_usd > 0 THEN
            SET v_reference_tag = CONCAT(' [REF:SALE#', NEW.id, '#USD]');
            SET v_note_text = CONCAT(
                'فرۆشتن | ',
                'ژمارەی پسووڵە: ', NEW.invoice_number, ' | ',
                'کڕیار: ', IFNULL(v_customer_name, 'نەناسراو'), ' | ',
                'وەرگر: ', IFNULL(NEW.recipient, '-'), ' | ',
                'شوێن: ', IFNULL(NEW.location, '-'), ' | ',
                'فۆرمۆلا: ', IFNULL(v_formula_name, '-'), ' | ',
                'بڕ: ', IFNULL(NEW.quantity, 0), ' م³ | ',
                'نرخی یەکە: $', IFNULL(NEW.price_per_unit, 0), ' | ',
                'کۆی نرخ: $', IFNULL(NEW.total_price, 0), ' | ',
                'پارەی دراو: $', NEW.amount_paid_usd, ' | ',
                'پارەی ماوە: $', IFNULL(NEW.remaining_amount, 0)
            );
            IF NEW.notes IS NOT NULL AND NEW.notes != '' THEN
                SET v_note_text = CONCAT(v_note_text, ' | تێبینی: ', NEW.notes);
            END IF;
            SET v_note_text = CONCAT(v_note_text, v_reference_tag);

            INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
            VALUES (NEW.order_date, 'deposit', 0, NEW.amount_paid_usd, 'دۆلار', v_note_text, NULL);
        END IF;

        IF NEW.amount_paid_iq > 0 THEN
            SET v_reference_tag = CONCAT(' [REF:SALE#', NEW.id, '#IQD]');
            SET v_note_text = CONCAT(
                'فرۆشتن | ',
                'ژمارەی پسووڵە: ', NEW.invoice_number, ' | ',
                'کڕیار: ', IFNULL(v_customer_name, 'نەناسراو'), ' | ',
                'وەرگر: ', IFNULL(NEW.recipient, '-'), ' | ',
                'شوێن: ', IFNULL(NEW.location, '-'), ' | ',
                'فۆرمۆلا: ', IFNULL(v_formula_name, '-'), ' | ',
                'بڕ: ', IFNULL(NEW.quantity, 0), ' م³ | ',
                'نرخی یەکە: ', IFNULL(NEW.price_per_unit, 0), ' د.ع | ',
                'کۆی نرخ: ', IFNULL(NEW.total_price, 0), ' د.ع | ',
                'پارەی دراو: ', NEW.amount_paid_iq, ' د.ع | ',
                'پارەی ماوە: ', IFNULL(NEW.remaining_amount, 0), ' د.ع'
            );
            IF NEW.notes IS NOT NULL AND NEW.notes != '' THEN
                SET v_note_text = CONCAT(v_note_text, ' | تێبینی: ', NEW.notes);
            END IF;
            SET v_note_text = CONCAT(v_note_text, v_reference_tag);

            INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
            VALUES (NEW.order_date, 'deposit', NEW.amount_paid_iq, 0, 'دینار', v_note_text, NULL);
        END IF;
    END IF;
END$$

CREATE TRIGGER `trg_before_delete_sale_cash_box` BEFORE DELETE ON `sales`
FOR EACH ROW
BEGIN
    IF OLD.payment_type = 'نەقد' THEN
        IF OLD.amount_paid_usd > 0 THEN
            DELETE FROM cash_box
            WHERE `type` = 'deposit'
              AND currency = 'دۆلار'
              AND (
                    note LIKE CONCAT('%[REF:SALE#', OLD.id, '#USD]%')
                    OR note LIKE CONCAT('%ژمارەی پسووڵە: ', OLD.invoice_number, '%')
                    OR note LIKE CONCAT('%invoice ', OLD.invoice_number, '%')
              );
        END IF;

        IF OLD.amount_paid_iq > 0 THEN
            DELETE FROM cash_box
            WHERE `type` = 'deposit'
              AND currency = 'دینار'
              AND (
                    note LIKE CONCAT('%[REF:SALE#', OLD.id, '#IQD]%')
                    OR note LIKE CONCAT('%ژمارەی پسووڵە: ', OLD.invoice_number, '%')
                    OR note LIKE CONCAT('%invoice ', OLD.invoice_number, '%')
              );
        END IF;
    END IF;
END$$

CREATE TRIGGER `trg_before_update_sale_cash_box` BEFORE UPDATE ON `sales`
FOR EACH ROW
BEGIN
    DECLARE v_customer_name VARCHAR(255) DEFAULT '';
    DECLARE v_formula_name VARCHAR(255) DEFAULT '';
    DECLARE v_note_text TEXT DEFAULT '';
    DECLARE v_reference_tag VARCHAR(64) DEFAULT '';

    IF OLD.payment_type = 'نەقد' THEN
        IF OLD.amount_paid_usd > 0 THEN
            DELETE FROM cash_box
            WHERE `type` = 'deposit'
              AND currency = 'دۆلار'
              AND (
                    note LIKE CONCAT('%[REF:SALE#', OLD.id, '#USD]%')
                    OR note LIKE CONCAT('%ژمارەی پسووڵە: ', OLD.invoice_number, '%')
                    OR note LIKE CONCAT('%invoice ', OLD.invoice_number, '%')
              );
        END IF;

        IF OLD.amount_paid_iq > 0 THEN
            DELETE FROM cash_box
            WHERE `type` = 'deposit'
              AND currency = 'دینار'
              AND (
                    note LIKE CONCAT('%[REF:SALE#', OLD.id, '#IQD]%')
                    OR note LIKE CONCAT('%ژمارەی پسووڵە: ', OLD.invoice_number, '%')
                    OR note LIKE CONCAT('%invoice ', OLD.invoice_number, '%')
              );
        END IF;
    END IF;

    IF NEW.payment_type = 'نەقد' THEN
        SELECT name INTO v_customer_name FROM customers WHERE id = NEW.customer_id;
        SELECT name INTO v_formula_name FROM concrete_formulas WHERE id = NEW.formula_id;

        IF NEW.amount_paid_usd > 0 THEN
            SET v_reference_tag = CONCAT(' [REF:SALE#', NEW.id, '#USD]');
            SET v_note_text = CONCAT(
                'فرۆشتن | ',
                'ژمارەی پسووڵە: ', NEW.invoice_number, ' | ',
                'کڕیار: ', IFNULL(v_customer_name, 'نەناسراو'), ' | ',
                'وەرگر: ', IFNULL(NEW.recipient, '-'), ' | ',
                'شوێن: ', IFNULL(NEW.location, '-'), ' | ',
                'فۆرمۆلا: ', IFNULL(v_formula_name, '-'), ' | ',
                'بڕ: ', IFNULL(NEW.quantity, 0), ' م³ | ',
                'نرخی یەکە: $', IFNULL(NEW.price_per_unit, 0), ' | ',
                'کۆی نرخ: $', IFNULL(NEW.total_price, 0), ' | ',
                'پارەی دراو: $', NEW.amount_paid_usd, ' | ',
                'پارەی ماوە: $', IFNULL(NEW.remaining_amount, 0)
            );
            IF NEW.notes IS NOT NULL AND NEW.notes != '' THEN
                SET v_note_text = CONCAT(v_note_text, ' | تێبینی: ', NEW.notes);
            END IF;
            SET v_note_text = CONCAT(v_note_text, v_reference_tag);

            INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
            VALUES (NEW.order_date, 'deposit', 0, NEW.amount_paid_usd, 'دۆلار', v_note_text, NULL);
        END IF;

        IF NEW.amount_paid_iq > 0 THEN
            SET v_reference_tag = CONCAT(' [REF:SALE#', NEW.id, '#IQD]');
            SET v_note_text = CONCAT(
                'فرۆشتن | ',
                'ژمارەی پسووڵە: ', NEW.invoice_number, ' | ',
                'کڕیار: ', IFNULL(v_customer_name, 'نەناسراو'), ' | ',
                'وەرگر: ', IFNULL(NEW.recipient, '-'), ' | ',
                'شوێن: ', IFNULL(NEW.location, '-'), ' | ',
                'فۆرمۆلا: ', IFNULL(v_formula_name, '-'), ' | ',
                'بڕ: ', IFNULL(NEW.quantity, 0), ' م³ | ',
                'نرخی یەکە: ', IFNULL(NEW.price_per_unit, 0), ' د.ع | ',
                'کۆی نرخ: ', IFNULL(NEW.total_price, 0), ' د.ع | ',
                'پارەی دراو: ', NEW.amount_paid_iq, ' د.ع | ',
                'پارەی ماوە: ', IFNULL(NEW.remaining_amount, 0), ' د.ع'
            );
            IF NEW.notes IS NOT NULL AND NEW.notes != '' THEN
                SET v_note_text = CONCAT(v_note_text, ' | تێبینی: ', NEW.notes);
            END IF;
            SET v_note_text = CONCAT(v_note_text, v_reference_tag);

            INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
            VALUES (NEW.order_date, 'deposit', NEW.amount_paid_iq, 0, 'دینار', v_note_text, NULL);
        END IF;
    END IF;
END$$

CREATE TRIGGER `trg_after_insert_purchase_cash_box` AFTER INSERT ON `purchases`
FOR EACH ROW
BEGIN
    DECLARE v_company_name VARCHAR(255) DEFAULT '';
    DECLARE v_material_name VARCHAR(255) DEFAULT '';
    DECLARE v_note_text TEXT DEFAULT '';
    DECLARE v_reference_tag VARCHAR(64) DEFAULT '';

    SELECT name INTO v_company_name FROM company WHERE id = NEW.company_id;
    SELECT name INTO v_material_name FROM list_materials WHERE id = NEW.material_id;

    IF NEW.payment_type = 'نەقد' THEN
        IF NEW.type = 'دۆلار' THEN
            SET v_reference_tag = CONCAT(' [REF:PURCHASE#', NEW.id, '#USD]');
            SET v_note_text = CONCAT(
                'کڕین | ',
                'ژمارەی پسووڵە: ', NEW.invoice_number, ' | ',
                'کۆمپانیا: ', IFNULL(v_company_name, 'نەناسراو'), ' | ',
                'مەواد: ', IFNULL(v_material_name, '-'), ' | ',
                'بڕ: ', IFNULL(NEW.kg, 0), ' کیلۆگرام | ',
                'شوێن: ', IFNULL(NEW.location, '-'), ' | ',
                'شۆفێر: ', IFNULL(NEW.driver, '-'), ' | ',
                'نرخی کڕین: $', IFNULL(NEW.price, 0), ' | ',
                'پارەی دراو: $', NEW.paid_usd, ' | ',
                'پارەی ماوە: $', IFNULL(NEW.remaining_usd, 0)
            );
            SET v_note_text = CONCAT(v_note_text, v_reference_tag);

            INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
            VALUES (NEW.date, 'withdraw', 0, NEW.paid_usd, 'دۆلار', v_note_text, NULL);
        ELSE
            SET v_reference_tag = CONCAT(' [REF:PURCHASE#', NEW.id, '#IQD]');
            SET v_note_text = CONCAT(
                'کڕین | ',
                'ژمارەی پسووڵە: ', NEW.invoice_number, ' | ',
                'کۆمپانیا: ', IFNULL(v_company_name, 'نەناسراو'), ' | ',
                'مەواد: ', IFNULL(v_material_name, '-'), ' | ',
                'بڕ: ', IFNULL(NEW.kg, 0), ' کیلۆگرام | ',
                'شوێن: ', IFNULL(NEW.location, '-'), ' | ',
                'شۆفێر: ', IFNULL(NEW.driver, '-'), ' | ',
                'نرخی کڕین: ', IFNULL(NEW.amount_iqd, 0), ' د.ع | ',
                'پارەی دراو: ', NEW.paid_iqd, ' د.ع | ',
                'پارەی ماوە: ', IFNULL(NEW.remaining_iqd, 0), ' د.ع'
            );
            SET v_note_text = CONCAT(v_note_text, v_reference_tag);

            INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
            VALUES (NEW.date, 'withdraw', NEW.paid_iqd, 0, 'دینار', v_note_text, NULL);
        END IF;
    END IF;
END$$

CREATE TRIGGER `trg_before_delete_purchase_cash_box` BEFORE DELETE ON `purchases`
FOR EACH ROW
BEGIN
    IF OLD.payment_type = 'نەقد' THEN
        IF OLD.type = 'دۆلار' THEN
            DELETE FROM cash_box
            WHERE `type` = 'withdraw'
              AND currency = 'دۆلار'
              AND (
                    note LIKE CONCAT('%[REF:PURCHASE#', OLD.id, '#USD]%')
                    OR note LIKE CONCAT('%ژمارەی پسووڵە: ', OLD.invoice_number, '%')
                    OR note LIKE CONCAT('%invoice ', OLD.invoice_number, '%')
              );
        ELSE
            DELETE FROM cash_box
            WHERE `type` = 'withdraw'
              AND currency = 'دینار'
              AND (
                    note LIKE CONCAT('%[REF:PURCHASE#', OLD.id, '#IQD]%')
                    OR note LIKE CONCAT('%ژمارەی پسووڵە: ', OLD.invoice_number, '%')
                    OR note LIKE CONCAT('%invoice ', OLD.invoice_number, '%')
              );
        END IF;
    END IF;
END$$

CREATE TRIGGER `trg_before_update_purchase_cash_box` BEFORE UPDATE ON `purchases`
FOR EACH ROW
BEGIN
    DECLARE v_company_name VARCHAR(255) DEFAULT '';
    DECLARE v_material_name VARCHAR(255) DEFAULT '';
    DECLARE v_note_text TEXT DEFAULT '';
    DECLARE v_reference_tag VARCHAR(64) DEFAULT '';

    IF OLD.payment_type = 'نەقد' THEN
        IF OLD.type = 'دۆلار' THEN
            DELETE FROM cash_box
            WHERE `type` = 'withdraw'
              AND currency = 'دۆلار'
              AND (
                    note LIKE CONCAT('%[REF:PURCHASE#', OLD.id, '#USD]%')
                    OR note LIKE CONCAT('%ژمارەی پسووڵە: ', OLD.invoice_number, '%')
                    OR note LIKE CONCAT('%invoice ', OLD.invoice_number, '%')
              );
        ELSE
            DELETE FROM cash_box
            WHERE `type` = 'withdraw'
              AND currency = 'دینار'
              AND (
                    note LIKE CONCAT('%[REF:PURCHASE#', OLD.id, '#IQD]%')
                    OR note LIKE CONCAT('%ژمارەی پسووڵە: ', OLD.invoice_number, '%')
                    OR note LIKE CONCAT('%invoice ', OLD.invoice_number, '%')
              );
        END IF;
    END IF;

    IF NEW.payment_type = 'نەقد' THEN
        SELECT name INTO v_company_name FROM company WHERE id = NEW.company_id;
        SELECT name INTO v_material_name FROM list_materials WHERE id = NEW.material_id;

        IF NEW.type = 'دۆلار' THEN
            SET v_reference_tag = CONCAT(' [REF:PURCHASE#', NEW.id, '#USD]');
            SET v_note_text = CONCAT(
                'کڕین | ',
                'ژمارەی پسووڵە: ', NEW.invoice_number, ' | ',
                'کۆمپانیا: ', IFNULL(v_company_name, 'نەناسراو'), ' | ',
                'مەواد: ', IFNULL(v_material_name, '-'), ' | ',
                'بڕ: ', IFNULL(NEW.kg, 0), ' کیلۆگرام | ',
                'شوێن: ', IFNULL(NEW.location, '-'), ' | ',
                'شۆفێر: ', IFNULL(NEW.driver, '-'), ' | ',
                'نرخی کڕین: $', IFNULL(NEW.price, 0), ' | ',
                'پارەی دراو: $', NEW.paid_usd, ' | ',
                'پارەی ماوە: $', IFNULL(NEW.remaining_usd, 0)
            );
            SET v_note_text = CONCAT(v_note_text, v_reference_tag);

            INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
            VALUES (NEW.date, 'withdraw', 0, NEW.paid_usd, 'دۆلار', v_note_text, NULL);
        ELSE
            SET v_reference_tag = CONCAT(' [REF:PURCHASE#', NEW.id, '#IQD]');
            SET v_note_text = CONCAT(
                'کڕین | ',
                'ژمارەی پسووڵە: ', NEW.invoice_number, ' | ',
                'کۆمپانیا: ', IFNULL(v_company_name, 'نەناسراو'), ' | ',
                'مەواد: ', IFNULL(v_material_name, '-'), ' | ',
                'بڕ: ', IFNULL(NEW.kg, 0), ' کیلۆگرام | ',
                'شوێن: ', IFNULL(NEW.location, '-'), ' | ',
                'شۆفێر: ', IFNULL(NEW.driver, '-'), ' | ',
                'نرخی کڕین: ', IFNULL(NEW.amount_iqd, 0), ' د.ع | ',
                'پارەی دراو: ', NEW.paid_iqd, ' د.ع | ',
                'پارەی ماوە: ', IFNULL(NEW.remaining_iqd, 0), ' د.ع'
            );
            SET v_note_text = CONCAT(v_note_text, v_reference_tag);

            INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
            VALUES (NEW.date, 'withdraw', NEW.paid_iqd, 0, 'دینار', v_note_text, NULL);
        END IF;
    END IF;
END$$

DELIMITER $$

-- Customer debt payment triggers
CREATE TRIGGER `trg_after_insert_customer_debt_payments` AFTER INSERT ON `customer_debt_payments` FOR EACH ROW BEGIN
    IF NEW.paid_usd > 0 THEN
        INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
        VALUES (NEW.date, 'deposit', 0, NEW.paid_usd, 'دۆلار', 'گەڕاندنەوەی قەرزی کڕیار', NULL);
    END IF;
    IF NEW.paid_iqd > 0 THEN
        INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
        VALUES (NEW.date, 'deposit', NEW.paid_iqd, 0, 'دینار', 'گەڕاندنەوەی قەرزی کڕیار', NULL);
    END IF;
END$$

CREATE TRIGGER `trg_before_delete_customer_debt_payments` BEFORE DELETE ON `customer_debt_payments` FOR EACH ROW BEGIN
    IF OLD.paid_usd > 0 THEN
        DELETE FROM cash_box
        WHERE `date` = OLD.date AND `type` = 'deposit' AND amount_usd = OLD.paid_usd AND currency = 'دۆلار' AND note = 'گەڕاندنەوەی قەرزی کڕیار';
    END IF;
    IF OLD.paid_iqd > 0 THEN
        DELETE FROM cash_box
        WHERE `date` = OLD.date AND `type` = 'deposit' AND amount_iqd = OLD.paid_iqd AND currency = 'دینار' AND note = 'گەڕاندنەوەی قەرزی کڕیار';
    END IF;
END$$

CREATE TRIGGER `trg_before_update_customer_debt_payments` BEFORE UPDATE ON `customer_debt_payments` FOR EACH ROW BEGIN
    IF OLD.paid_usd > 0 THEN
        DELETE FROM cash_box
        WHERE `date` = OLD.date AND `type` = 'deposit' AND amount_usd = OLD.paid_usd AND currency = 'دۆلار' AND note = 'گەڕاندنەوەی قەرزی کڕیار';
    END IF;
    IF OLD.paid_iqd > 0 THEN
        DELETE FROM cash_box
        WHERE `date` = OLD.date AND `type` = 'deposit' AND amount_iqd = OLD.paid_iqd AND currency = 'دینار' AND note = 'گەڕاندنەوەی قەرزی کڕیار';
    END IF;
    IF NEW.paid_usd > 0 THEN
        INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
        VALUES (NEW.date, 'deposit', 0, NEW.paid_usd, 'دۆلار', 'گەڕاندنەوەی قەرزی کڕیار', NULL);
    END IF;
    IF NEW.paid_iqd > 0 THEN
        INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
        VALUES (NEW.date, 'deposit', NEW.paid_iqd, 0, 'دینار', 'گەڕاندنەوەی قەرزی کڕیار', NULL);
    END IF;
END$$

-- Company debt payment triggers
CREATE TRIGGER `trg_after_insert_debt_payments` AFTER INSERT ON `debt_payments` FOR EACH ROW BEGIN
    IF NEW.amount_usd > 0 THEN
        INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
        VALUES (NEW.date, 'withdraw', 0, NEW.amount_usd, 'دۆلار', CONCAT('گەڕاندنەوەی قەرزی کۆمپانیا: ', NEW.company_id), NEW.created_by);
    END IF;
    IF NEW.amount_iqd > 0 THEN
        INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
        VALUES (NEW.date, 'withdraw', NEW.amount_iqd, 0, 'دینار', CONCAT('گەڕاندنەوەی قەرزی کۆمپانیا: ', NEW.company_id), NEW.created_by);
    END IF;
END$$

CREATE TRIGGER `trg_before_delete_debt_payments` BEFORE DELETE ON `debt_payments` FOR EACH ROW BEGIN
    IF OLD.amount_usd > 0 THEN
        DELETE FROM cash_box
        WHERE `date` = OLD.date AND `type` = 'withdraw' AND amount_usd = OLD.amount_usd AND currency = 'دۆلار' AND note = CONCAT('گەڕاندنەوەی قەرزی کۆمپانیا: ', OLD.company_id);
    END IF;
    IF OLD.amount_iqd > 0 THEN
        DELETE FROM cash_box
        WHERE `date` = OLD.date AND `type` = 'withdraw' AND amount_iqd = OLD.amount_iqd AND currency = 'دینار' AND note = CONCAT('گەڕاندنەوەی قەرزی کۆمپانیا: ', OLD.company_id);
    END IF;
END$$

CREATE TRIGGER `trg_before_update_debt_payments` BEFORE UPDATE ON `debt_payments` FOR EACH ROW BEGIN
    IF OLD.amount_usd > 0 THEN
        DELETE FROM cash_box
        WHERE `date` = OLD.date AND `type` = 'withdraw' AND amount_usd = OLD.amount_usd AND currency = 'دۆلار' AND note = CONCAT('گەڕاندنەوەی قەرزی کۆمپانیا: ', OLD.company_id);
    END IF;
    IF OLD.amount_iqd > 0 THEN
        DELETE FROM cash_box
        WHERE `date` = OLD.date AND `type` = 'withdraw' AND amount_iqd = OLD.amount_iqd AND currency = 'دینار' AND note = CONCAT('گەڕاندنەوەی قەرزی کۆمپانیا: ', OLD.company_id);
    END IF;
    IF NEW.amount_usd > 0 THEN
        INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
        VALUES (NEW.date, 'withdraw', 0, NEW.amount_usd, 'دۆلار', CONCAT('گەڕاندنەوەی قەرزی کۆمپانیا: ', NEW.company_id), NEW.created_by);
    END IF;
    IF NEW.amount_iqd > 0 THEN
        INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
        VALUES (NEW.date, 'withdraw', NEW.amount_iqd, 0, 'دینار', CONCAT('گەڕاندنەوەی قەرزی کۆمپانیا: ', NEW.company_id), NEW.created_by);
    END IF;
END$$

-- Employee payment triggers
CREATE TRIGGER `trg_after_insert_employee_payment_cash_box` AFTER INSERT ON `employee_payments` FOR EACH ROW BEGIN
    INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
    VALUES (NEW.created_at, 'withdraw', NEW.total, 0, 'دینار', CONCAT('پارەدان بۆ کارمەند: ', NEW.employee_id), NULL);
END$$

CREATE TRIGGER `trg_after_insert_employee_payments` AFTER INSERT ON `employee_payments` FOR EACH ROW BEGIN
    INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
    VALUES (NEW.created_at, 'withdraw', NEW.total, 0, 'دینار', CONCAT('پارەدان بە کارمەند: ', NEW.employee_id), NULL);
END$$

CREATE TRIGGER `trg_before_delete_employee_payment_cash_box` BEFORE DELETE ON `employee_payments` FOR EACH ROW BEGIN
    DELETE FROM cash_box
    WHERE `date` = OLD.created_at
      AND `type` = 'withdraw'
      AND amount_iqd = OLD.total
      AND currency = 'دینار'
      AND note = CONCAT('پارەدان بۆ کارمەند: ', OLD.employee_id);
END$$

CREATE TRIGGER `trg_before_delete_employee_payments` BEFORE DELETE ON `employee_payments` FOR EACH ROW BEGIN
    INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
    VALUES (NOW(), 'deposit', OLD.total, 0, 'دینار', CONCAT('گەڕانەوەی پارەدان بە کارمەند: ', OLD.employee_id), NULL);
END$$

CREATE TRIGGER `trg_before_update_employee_payment_cash_box` BEFORE UPDATE ON `employee_payments` FOR EACH ROW BEGIN
    DECLARE difference DECIMAL(15,2);
    SET difference = NEW.total - OLD.total;
    IF difference != 0 THEN
        IF difference > 0 THEN
            INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
            VALUES (NOW(), 'withdraw', difference, 0, 'دینار', CONCAT('زیادکردنی پارەدان بە کارمەند: ', NEW.employee_id), NULL);
        ELSE
            INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
            VALUES (NOW(), 'deposit', ABS(difference), 0, 'دینار', CONCAT('کەمکردنی پارەدان بە کارمەند: ', NEW.employee_id), NULL);
        END IF;
    END IF;
END$$

CREATE TRIGGER `trg_before_update_employee_payments` BEFORE UPDATE ON `employee_payments` FOR EACH ROW BEGIN
    DECLARE difference DECIMAL(15,2);
    SET difference = NEW.total - OLD.total;
    IF difference != 0 THEN
        IF difference > 0 THEN
            INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
            VALUES (NOW(), 'withdraw', difference, 0, 'دینار', CONCAT('زیادکردنی پارەدان بە کارمەند: ', NEW.employee_id), NULL);
        ELSE
            INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
            VALUES (NOW(), 'deposit', ABS(difference), 0, 'دینار', CONCAT('کەمکردنی پارەدان بە کارمەند: ', NEW.employee_id), NULL);
        END IF;
    END IF;
END$$

-- Other expenses triggers
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
                    OR (note LIKE CONCAT('%invoice ', OLD.invoice_number, '%') AND amount_usd = v_paid_usd AND `date` = OLD.date)
              );
        END IF;
        IF v_paid_iqd > 0 THEN
            DELETE FROM cash_box
            WHERE `type` = 'withdraw'
              AND currency = 'دینار'
              AND (
                    note LIKE CONCAT('%[REF:OTHEREXP#', OLD.id, '#IQD]%')
                    OR (note LIKE CONCAT('%invoice ', OLD.invoice_number, '%') AND amount_iqd = v_paid_iqd AND `date` = OLD.date)
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
                    OR (note LIKE CONCAT('%invoice ', OLD.invoice_number, '%') AND amount_usd = v_paid_usd AND `date` = OLD.date)
              );
        END IF;
        IF v_paid_iqd > 0 THEN
            DELETE FROM cash_box
            WHERE `type` = 'withdraw'
              AND currency = 'دینار'
              AND (
                    note LIKE CONCAT('%[REF:OTHEREXP#', OLD.id, '#IQD]%')
                    OR (note LIKE CONCAT('%invoice ', OLD.invoice_number, '%') AND amount_iqd = v_paid_iqd AND `date` = OLD.date)
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

-- Person other expenses debt payment triggers
CREATE TRIGGER `trg_after_insert_person_other_expenses_debt_payments` AFTER INSERT ON `person_other_expenses_debt_payments` FOR EACH ROW BEGIN
    DECLARE v_person_name VARCHAR(255) DEFAULT '';
    DECLARE v_note_text TEXT DEFAULT '';

    IF NEW.person_id IS NOT NULL THEN
        SELECT name INTO v_person_name FROM other_expense_persons WHERE id = NEW.person_id;
    END IF;

    IF NEW.amount_usd > 0 THEN
        SET v_note_text = CONCAT(
            'گەڕاندنەوەی قەرزی کەسانی تر | ',
            'کەس: ', IFNULL(v_person_name, CONCAT('ID: ', NEW.person_id)), ' | ',
            'پارەی دراو: $', NEW.amount_usd
        );
        IF NEW.discount_usd > 0 THEN
            SET v_note_text = CONCAT(v_note_text, ' | داشکاندن: $', NEW.discount_usd);
        END IF;
        IF NEW.note IS NOT NULL AND NEW.note != '' THEN
            SET v_note_text = CONCAT(v_note_text, ' | تێبینی: ', NEW.note);
        END IF;

        INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
        VALUES (NEW.date, 'withdraw', 0, NEW.amount_usd, 'دۆلار', v_note_text, NULL);
    END IF;

    IF NEW.amount_iqd > 0 THEN
        SET v_note_text = CONCAT(
            'گەڕاندنەوەی قەرزی کەسانی تر | ',
            'کەس: ', IFNULL(v_person_name, CONCAT('ID: ', NEW.person_id)), ' | ',
            'پارەی دراو: ', NEW.amount_iqd, ' د.ع'
        );
        IF NEW.discount_iqd > 0 THEN
            SET v_note_text = CONCAT(v_note_text, ' | داشکاندن: ', NEW.discount_iqd, ' د.ع');
        END IF;
        IF NEW.note IS NOT NULL AND NEW.note != '' THEN
            SET v_note_text = CONCAT(v_note_text, ' | تێبینی: ', NEW.note);
        END IF;

        INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
        VALUES (NEW.date, 'withdraw', NEW.amount_iqd, 0, 'دینار', v_note_text, NULL);
    END IF;
END$$

CREATE TRIGGER `trg_before_delete_person_other_expenses_debt_payments` BEFORE DELETE ON `person_other_expenses_debt_payments` FOR EACH ROW BEGIN
    IF OLD.amount_usd > 0 THEN
        DELETE FROM cash_box
        WHERE `date` = OLD.date AND `type` = 'withdraw' AND amount_usd = OLD.amount_usd AND currency = 'دۆلار' AND note LIKE CONCAT('%گەڕاندنەوەی قەرزی کەسانی تر%', OLD.person_id, '%');
    END IF;
    IF OLD.amount_iqd > 0 THEN
        DELETE FROM cash_box
        WHERE `date` = OLD.date AND `type` = 'withdraw' AND amount_iqd = OLD.amount_iqd AND currency = 'دینار' AND note LIKE CONCAT('%گەڕاندنەوەی قەرزی کەسانی تر%', OLD.person_id, '%');
    END IF;
END$$

CREATE TRIGGER `trg_before_update_person_other_expenses_debt_payments` BEFORE UPDATE ON `person_other_expenses_debt_payments` FOR EACH ROW BEGIN
    IF OLD.amount_usd > 0 THEN
        DELETE FROM cash_box
        WHERE `date` = OLD.date AND `type` = 'withdraw' AND amount_usd = OLD.amount_usd AND currency = 'دۆلار' AND note = CONCAT('گەڕاندنەوەی قەرزی کەسانی تر: ', OLD.person_id);
    END IF;
    IF OLD.amount_iqd > 0 THEN
        DELETE FROM cash_box
        WHERE `date` = OLD.date AND `type` = 'withdraw' AND amount_iqd = OLD.amount_iqd AND currency = 'دینار' AND note = CONCAT('گەڕاندنەوەی قەرزی کەسانی تر: ', OLD.person_id);
    END IF;
    IF NEW.amount_usd > 0 THEN
        INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
        VALUES (NEW.date, 'withdraw', 0, NEW.amount_usd, 'دۆلار', CONCAT('گەڕاندنەوەی قەرزی کەسانی تر: ', NEW.person_id), NULL);
    END IF;
    IF NEW.amount_iqd > 0 THEN
        INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
        VALUES (NEW.date, 'withdraw', NEW.amount_iqd, 0, 'دینار', CONCAT('گەڕاندنەوەی قەرزی کەسانی تر: ', NEW.person_id), NULL);
    END IF;
END$$

-- Purchase materials triggers (cash adjustments)
CREATE TRIGGER `after_purchase_materials_delete` AFTER DELETE ON `purchase_materials` FOR EACH ROW BEGIN
    UPDATE list_materials 
    SET quantity = quantity - OLD.base_quantity
    WHERE id = OLD.material_id;

    IF OLD.payment_type = 'نەقد' THEN
        INSERT INTO cash_box (date, type, amount_usd, amount_iqd, currency, note, created_by)
        VALUES (
            OLD.purchase_date,
            'deposit',
            OLD.total_price_usd,
            OLD.total_price_iqd,
            OLD.currency_type,
            CONCAT('گەڕاندنەوەی پارەی کڕینی ', OLD.receipt_number),
            OLD.created_by
        );
    END IF;
END$$

CREATE TRIGGER `after_purchase_materials_insert` AFTER INSERT ON `purchase_materials` FOR EACH ROW BEGIN
    DECLARE current_balance_usd DECIMAL(20,2) DEFAULT 0;
    DECLARE dollar_rate DECIMAL(20,2) DEFAULT 150000;
    DECLARE withdrawal_usd DECIMAL(20,2) DEFAULT 0;

    UPDATE list_materials 
    SET quantity = quantity + NEW.base_quantity
    WHERE id = NEW.material_id;

    IF NEW.payment_type = 'نەقد' THEN
        SELECT CAST(value AS DECIMAL(20,2)) INTO dollar_rate FROM settings WHERE name = 'usd_iqd_rate' LIMIT 1;
        SELECT
            IFNULL(SUM(
                CASE
                    WHEN currency = 'دۆلار' AND type = 'deposit' THEN amount_usd
                    WHEN currency = 'دۆلار' AND type = 'withdraw' THEN -amount_usd
                    WHEN currency = 'دینار' AND type = 'deposit' THEN amount_iqd / dollar_rate
                    WHEN currency = 'دینار' AND type = 'withdraw' THEN -amount_iqd / dollar_rate
                    ELSE 0
                END
            ), 0)
        INTO current_balance_usd
        FROM cash_box;

        IF NEW.currency_type = 'دۆلار' THEN
            SET withdrawal_usd = NEW.total_price_usd;
        ELSE
            SET withdrawal_usd = NEW.total_price_iqd / dollar_rate;
        END IF;

        INSERT INTO cash_box (date, type, amount_usd, amount_iqd, currency, note, created_by)
        VALUES (
            NEW.purchase_date,
            'withdraw',
            NEW.total_price_usd,
            NEW.total_price_iqd,
            NEW.currency_type,
            CONCAT('پارەدانی کڕینی ', NEW.receipt_number),
            NEW.created_by
        );
    END IF;
END$$

CREATE TRIGGER `after_purchase_materials_update` AFTER UPDATE ON `purchase_materials` FOR EACH ROW BEGIN
    DECLARE current_balance_usd DECIMAL(20,2) DEFAULT 0;
    DECLARE dollar_rate DECIMAL(20,2) DEFAULT 150000;
    DECLARE withdrawal_usd DECIMAL(20,2) DEFAULT 0;

    UPDATE list_materials 
    SET quantity = quantity - OLD.base_quantity
    WHERE id = OLD.material_id;

    UPDATE list_materials 
    SET quantity = quantity + NEW.base_quantity
    WHERE id = NEW.material_id;

    IF OLD.payment_type = 'نەقد' AND NEW.payment_type = 'قەرز' THEN
        INSERT INTO cash_box (date, type, amount_usd, amount_iqd, currency, note, created_by)
        VALUES (
            NEW.purchase_date,
            'deposit',
            OLD.total_price_usd,
            OLD.total_price_iqd,
            OLD.currency_type,
            CONCAT('گەڕاندنەوەی پارەی کڕینی ', NEW.receipt_number, ' (گۆڕانکاری بۆ قەرز)'),
            NEW.created_by
        );
    ELSEIF OLD.payment_type = 'قەرز' AND NEW.payment_type = 'نەقد' THEN
        SELECT CAST(value AS DECIMAL(20,2)) INTO dollar_rate FROM settings WHERE name = 'usd_iqd_rate' LIMIT 1;
        SELECT
            IFNULL(SUM(
                CASE
                    WHEN currency = 'دۆلار' AND type = 'deposit' THEN amount_usd
                    WHEN currency = 'دۆلار' AND type = 'withdraw' THEN -amount_usd
                    WHEN currency = 'دینار' AND type = 'deposit' THEN amount_iqd / dollar_rate
                    WHEN currency = 'دینار' AND type = 'withdraw' THEN -amount_iqd / dollar_rate
                    ELSE 0
                END
            ), 0)
        INTO current_balance_usd
        FROM cash_box;

        IF NEW.currency_type = 'دۆلار' THEN
            SET withdrawal_usd = NEW.total_price_usd;
        ELSE
            SET withdrawal_usd = NEW.total_price_iqd / dollar_rate;
        END IF;

        INSERT INTO cash_box (date, type, amount_usd, amount_iqd, currency, note, created_by)
        VALUES (
            NEW.purchase_date,
            'withdraw',
            NEW.total_price_usd,
            NEW.total_price_iqd,
            NEW.currency_type,
            CONCAT('پارەدانی کڕینی ', NEW.receipt_number, ' (گۆڕانکاری بۆ نەقد)'),
            NEW.created_by
        );
    ELSEIF OLD.payment_type = 'نەقد' AND NEW.payment_type = 'نەقد' THEN
        IF OLD.paid_amount_usd != NEW.paid_amount_usd OR OLD.paid_amount_iqd != NEW.paid_amount_iqd THEN
            IF (NEW.paid_amount_usd - OLD.paid_amount_usd) > 0 OR (NEW.paid_amount_iqd - OLD.paid_amount_iqd) > 0 THEN
                SELECT CAST(value AS DECIMAL(20,2)) INTO dollar_rate FROM settings WHERE name = 'usd_iqd_rate' LIMIT 1;
                SELECT
                    IFNULL(SUM(
                        CASE
                            WHEN currency = 'دۆلار' AND type = 'deposit' THEN amount_usd
                            WHEN currency = 'دۆلار' AND type = 'withdraw' THEN -amount_usd
                            WHEN currency = 'دینار' AND type = 'deposit' THEN amount_iqd / dollar_rate
                            WHEN currency = 'دینار' AND type = 'withdraw' THEN -amount_iqd / dollar_rate
                            ELSE 0
                        END
                    ), 0)
                INTO current_balance_usd
                FROM cash_box;

                IF NEW.currency_type = 'دۆلار' THEN
                    SET withdrawal_usd = (NEW.paid_amount_usd - OLD.paid_amount_usd);
                ELSE
                    SET withdrawal_usd = (NEW.paid_amount_iqd - OLD.paid_amount_iqd) / dollar_rate;
                END IF;

                INSERT INTO cash_box (date, type, amount_usd, amount_iqd, currency, note, created_by)
                VALUES (
                    NEW.purchase_date,
                    'withdraw',
                    (NEW.paid_amount_usd - OLD.paid_amount_usd),
                    (NEW.paid_amount_iqd - OLD.paid_amount_iqd),
                    NEW.currency_type,
                    CONCAT('زیادکردنی پارەدانی کڕینی ', NEW.receipt_number),
                    NEW.created_by
                );
            ELSEIF (NEW.paid_amount_usd - OLD.paid_amount_usd) < 0 OR (NEW.paid_amount_iqd - OLD.paid_amount_iqd) < 0 THEN
                INSERT INTO cash_box (date, type, amount_usd, amount_iqd, currency, note, created_by)
                VALUES (
                    NEW.purchase_date,
                    'deposit',
                    ABS(NEW.paid_amount_usd - OLD.paid_amount_usd),
                    ABS(NEW.paid_amount_iqd - OLD.paid_amount_iqd),
                    NEW.currency_type,
                    CONCAT('کەمکردنی پارەدانی کڕینی ', NEW.receipt_number),
                    NEW.created_by
                );
            END IF;
        END IF;
    END IF;
END$$

DELIMITER ;

