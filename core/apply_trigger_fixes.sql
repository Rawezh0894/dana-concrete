-- Apply Trigger Fixes for Other Expenses Table
-- This script updates the triggers to properly handle gas and material consumption

-- Drop existing triggers first
DROP TRIGGER IF EXISTS `trg_after_insert_other_expenses`;
DROP TRIGGER IF EXISTS `trg_before_delete_other_expenses`;
DROP TRIGGER IF EXISTS `trg_before_update_other_expenses`;
DROP TRIGGER IF EXISTS `trg_restore_gas_on_delete_other_expenses`;

-- Create new triggers

DELIMITER $$

-- Trigger for INSERT: Handle cash box and gas/material consumption
CREATE TRIGGER `trg_after_insert_other_expenses` AFTER INSERT ON `other_expenses` FOR EACH ROW 
BEGIN
    -- Handle cash box operations for cash payments
    IF NEW.payment_type = 'نەقد' THEN
        IF NEW.currency_type = 'دۆلار' AND NEW.paid_usd > 0 THEN
            INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
            VALUES (NEW.date, 'withdraw', 0, NEW.paid_usd, 'دۆلار', CONCAT('خەرجی تر: invoice ', NEW.invoice_number), NULL);
        END IF;
        IF NEW.currency_type = 'دینار' AND NEW.paid_iqd > 0 THEN
            INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
            VALUES (NEW.date, 'withdraw', NEW.paid_iqd, 0, 'دینار', CONCAT('خەرجی تر: invoice ', NEW.invoice_number), NULL);
        END IF;
    END IF;
    
    -- Handle gas consumption for gas usage expenses
    IF NEW.expense_type = 'بەکارهێنانی گاز' AND NEW.gas_liters IS NOT NULL AND NEW.gas_liters > 0 THEN
        UPDATE bins_silos
        SET amount = amount - NEW.gas_liters
        WHERE type = 'تەنکی' AND material_type = 'گاز'
        LIMIT 1;
    END IF;
    
    -- Handle material consumption for warehouse material usage
    IF NEW.expense_type = 'بەکارهێنانی کاڵای کۆگا' AND NEW.material_id IS NOT NULL AND NEW.material_quantity IS NOT NULL AND NEW.material_quantity > 0 THEN
        UPDATE list_materials
        SET quantity = quantity - NEW.material_quantity
        WHERE id = NEW.material_id;
    END IF;
END$$

-- Trigger for DELETE: Handle cash box reversal and gas/material restoration
CREATE TRIGGER `trg_before_delete_other_expenses` BEFORE DELETE ON `other_expenses` FOR EACH ROW 
BEGIN
    -- Handle cash box operations reversal
    IF OLD.payment_type = 'نەقد' THEN
        IF OLD.currency_type = 'دۆلار' AND OLD.paid_usd > 0 THEN
            DELETE FROM cash_box
            WHERE `date` = OLD.date AND `type` = 'withdraw' AND amount_usd = OLD.paid_usd AND currency = 'دۆلار' AND note = CONCAT('خەرجی تر: invoice ', OLD.invoice_number);
        END IF;
        IF OLD.currency_type = 'دینار' AND OLD.paid_iqd > 0 THEN
            DELETE FROM cash_box
            WHERE `date` = OLD.date AND `type` = 'withdraw' AND amount_iqd = OLD.paid_iqd AND currency = 'دینار' AND note = CONCAT('خەرجی تر: invoice ', OLD.invoice_number);
        END IF;
    END IF;
    
    -- Handle gas restoration
    IF OLD.expense_type = 'بەکارهێنانی گاز' AND OLD.gas_liters IS NOT NULL AND OLD.gas_liters > 0 THEN
        UPDATE bins_silos
        SET amount = amount + OLD.gas_liters
        WHERE type = 'تەنکی' AND material_type = 'گاز'
        LIMIT 1;
    END IF;
    
    -- Handle material restoration
    IF OLD.expense_type = 'بەکارهێنانی کاڵای کۆگا' AND OLD.material_id IS NOT NULL AND OLD.material_quantity IS NOT NULL AND OLD.material_quantity > 0 THEN
        UPDATE list_materials
        SET quantity = quantity + OLD.material_quantity
        WHERE id = OLD.material_id;
    END IF;
END$$

-- Trigger for UPDATE: Handle cash box, gas, and material changes (BEFORE)
CREATE TRIGGER `trg_before_update_other_expenses` BEFORE UPDATE ON `other_expenses` FOR EACH ROW 
BEGIN
    -- Handle cash box operations for old record
    IF OLD.payment_type = 'نەقد' THEN
        IF OLD.currency_type = 'دۆلار' AND OLD.paid_usd > 0 THEN
            DELETE FROM cash_box
            WHERE `date` = OLD.date AND `type` = 'withdraw' AND amount_usd = OLD.paid_usd AND currency = 'دۆلار' AND note = CONCAT('خەرجی تر: invoice ', OLD.invoice_number);
        END IF;
        IF OLD.currency_type = 'دینار' AND OLD.paid_iqd > 0 THEN
            DELETE FROM cash_box
            WHERE `date` = OLD.date AND `type` = 'withdraw' AND amount_iqd = OLD.paid_iqd AND currency = 'دینار' AND note = CONCAT('خەرجی تر: invoice ', OLD.invoice_number);
        END IF;
    END IF;
    
    -- Handle gas changes
    IF OLD.expense_type = 'بەکارهێنانی گاز' AND OLD.gas_liters IS NOT NULL AND OLD.gas_liters > 0 THEN
        -- Restore old gas amount
        UPDATE bins_silos
        SET amount = amount + OLD.gas_liters
        WHERE type = 'تەنکی' AND material_type = 'گاز'
        LIMIT 1;
    END IF;
    
    -- Handle material changes
    IF OLD.expense_type = 'بەکارهێنانی کاڵای کۆگا' AND OLD.material_id IS NOT NULL AND OLD.material_quantity IS NOT NULL AND OLD.material_quantity > 0 THEN
        -- Restore old material quantity
        UPDATE list_materials
        SET quantity = quantity + OLD.material_quantity
        WHERE id = OLD.material_id;
    END IF;
END$$

-- Trigger for UPDATE: Handle new values after old values are processed (AFTER)
CREATE TRIGGER `trg_after_update_other_expenses` AFTER UPDATE ON `other_expenses` FOR EACH ROW 
BEGIN
    -- Handle cash box operations for new record
    IF NEW.payment_type = 'نەقد' THEN
        IF NEW.currency_type = 'دۆلار' AND NEW.paid_usd > 0 THEN
            INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
            VALUES (NEW.date, 'withdraw', 0, NEW.paid_usd, 'دۆلار', CONCAT('خەرجی تر: invoice ', NEW.invoice_number), NULL);
        END IF;
        IF NEW.currency_type = 'دینار' AND NEW.paid_iqd > 0 THEN
            INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
            VALUES (NEW.date, 'withdraw', NEW.paid_iqd, 0, 'دینار', CONCAT('خەرجی تر: invoice ', NEW.invoice_number), NULL);
        END IF;
    END IF;
    
    -- Handle new gas consumption
    IF NEW.expense_type = 'بەکارهێنانی گاز' AND NEW.gas_liters IS NOT NULL AND NEW.gas_liters > 0 THEN
        UPDATE bins_silos
        SET amount = amount - NEW.gas_liters
        WHERE type = 'تەنکی' AND material_type = 'گاز'
        LIMIT 1;
    END IF;
    
    -- Handle new material consumption
    IF NEW.expense_type = 'بەکارهێنانی کاڵای کۆگا' AND NEW.material_id IS NOT NULL AND NEW.material_quantity IS NOT NULL AND NEW.material_quantity > 0 THEN
        UPDATE list_materials
        SET quantity = quantity - NEW.material_quantity
        WHERE id = NEW.material_id;
    END IF;
END$$

DELIMITER ;

-- Show confirmation
SELECT 'Trigger fixes applied successfully!' AS status;
SELECT 'Triggers created:' AS info;
SHOW TRIGGERS WHERE `Table` = 'other_expenses'; 