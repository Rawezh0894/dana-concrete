-- Database Triggers for Unit System
-- Professional triggers for handling unit-specific inventory management
-- These triggers ensure that inventory is properly tracked by unit type

-- Drop existing triggers if they exist
DROP TRIGGER IF EXISTS `after_purchase_materials_insert`;
DROP TRIGGER IF EXISTS `after_purchase_materials_update`;
DROP TRIGGER IF EXISTS `after_purchase_materials_delete`;

-- =====================================================
-- TRIGGER: after_purchase_materials_insert
-- Purpose: Handle inventory updates when new purchases are added
-- =====================================================
DELIMITER $$
CREATE TRIGGER `after_purchase_materials_insert` 
AFTER INSERT ON `purchase_materials` 
FOR EACH ROW 
BEGIN
    DECLARE existing_quantity DECIMAL(15,2) DEFAULT 0;
    DECLARE material_exists INT DEFAULT 0;
    
    -- Check if material exists in inventory_materials
    SELECT COUNT(*) INTO material_exists 
    FROM inventory_materials 
    WHERE id = NEW.material_id;
    
    -- If material doesn't exist, create it
    IF material_exists = 0 THEN
        INSERT INTO inventory_materials (
            id, name, unit_type, pieces_per_carton, bags_per_barrel, 
            liters_per_bag, liters_per_barrel, currency_type, 
            purchase_price_usd, purchase_price_iqd, price_per_piece, 
            price_per_liter, price_per_bag
        ) VALUES (
            NEW.material_id, 
            (SELECT name FROM materials WHERE id = NEW.material_id LIMIT 1),
            NEW.unit_type,
            NEW.pieces_per_carton,
            NEW.bags_per_barrel,
            NEW.liters_per_bag,
            NEW.liters_per_barrel,
            'دینار',
            NEW.purchase_price_usd,
            NEW.purchase_price_iqd,
            NEW.price_per_piece,
            NEW.price_per_liter,
            NEW.price_per_bag
        );
    END IF;
    
    -- Check if inventory record exists for this unit type
    SELECT quantity INTO existing_quantity
    FROM inventory_by_unit 
    WHERE material_id = NEW.material_id AND unit_type = NEW.unit_type;
    
    IF existing_quantity IS NULL THEN
        -- Insert new record for this unit type
        INSERT INTO inventory_by_unit (material_id, unit_type, quantity)
        VALUES (NEW.material_id, NEW.unit_type, NEW.quantity);
    ELSE
        -- Update existing record
        UPDATE inventory_by_unit 
        SET quantity = quantity + NEW.quantity,
            updated_at = CURRENT_TIMESTAMP
        WHERE material_id = NEW.material_id AND unit_type = NEW.unit_type;
    END IF;
    
    -- Log the operation
    INSERT INTO inventory_log (
        material_id, operation_type, quantity, unit_type, 
        reference_id, reference_table, notes, created_at
    ) VALUES (
        NEW.material_id, 'INSERT', NEW.quantity, NEW.unit_type, 
        NEW.id, 'purchase_materials', 
        CONCAT('Added ', NEW.quantity, ' ', NEW.unit_type, ' to inventory'), 
        CURRENT_TIMESTAMP
    );
END$$
DELIMITER ;

-- =====================================================
-- TRIGGER: after_purchase_materials_update
-- Purpose: Handle inventory updates when purchases are modified
-- =====================================================
DELIMITER $$
CREATE TRIGGER `after_purchase_materials_update` 
AFTER UPDATE ON `purchase_materials` 
FOR EACH ROW 
BEGIN
    DECLARE quantity_difference DECIMAL(15,2);
    DECLARE old_unit_type VARCHAR(20);
    DECLARE new_unit_type VARCHAR(20);
    
    -- Store unit types for comparison
    SET old_unit_type = OLD.unit_type;
    SET new_unit_type = NEW.unit_type;
    
    -- If unit type changed, we need to handle both old and new units
    IF old_unit_type != new_unit_type THEN
        -- Remove quantity from old unit type
        UPDATE inventory_by_unit 
        SET quantity = quantity - OLD.quantity,
            updated_at = CURRENT_TIMESTAMP
        WHERE material_id = OLD.material_id AND unit_type = OLD.unit_type;
        
        -- Delete record if quantity becomes zero or negative
        DELETE FROM inventory_by_unit 
        WHERE material_id = OLD.material_id AND unit_type = OLD.unit_type AND quantity <= 0;
        
        -- Add quantity to new unit type
        INSERT INTO inventory_by_unit (material_id, unit_type, quantity)
        VALUES (NEW.material_id, NEW.unit_type, NEW.quantity)
        ON DUPLICATE KEY UPDATE 
            quantity = quantity + NEW.quantity,
            updated_at = CURRENT_TIMESTAMP;
            
        -- Log unit type change
        INSERT INTO inventory_log (
            material_id, operation_type, quantity, unit_type, 
            reference_id, reference_table, notes, created_at
        ) VALUES (
            NEW.material_id, 'UPDATE', OLD.quantity, OLD.unit_type, 
            NEW.id, 'purchase_materials', 
            CONCAT('Removed ', OLD.quantity, ' ', OLD.unit_type, ' due to unit type change'), 
            CURRENT_TIMESTAMP
        );
        
        INSERT INTO inventory_log (
            material_id, operation_type, quantity, unit_type, 
            reference_id, reference_table, notes, created_at
        ) VALUES (
            NEW.material_id, 'UPDATE', NEW.quantity, NEW.unit_type, 
            NEW.id, 'purchase_materials', 
            CONCAT('Added ', NEW.quantity, ' ', NEW.unit_type, ' due to unit type change'), 
            CURRENT_TIMESTAMP
        );
    ELSE
        -- Same unit type, just update quantity difference
        SET quantity_difference = NEW.quantity - OLD.quantity;
        
        -- Update inventory for the unit type
        UPDATE inventory_by_unit 
        SET quantity = quantity + quantity_difference,
            updated_at = CURRENT_TIMESTAMP
        WHERE material_id = NEW.material_id AND unit_type = NEW.unit_type;
        
        -- If no rows were affected, insert a new record
        IF ROW_COUNT() = 0 THEN
            INSERT INTO inventory_by_unit (material_id, unit_type, quantity)
            VALUES (NEW.material_id, NEW.unit_type, quantity_difference);
        END IF;
        
        -- Log the operation
        INSERT INTO inventory_log (
            material_id, operation_type, quantity, unit_type, 
            reference_id, reference_table, notes, created_at
        ) VALUES (
            NEW.material_id, 'UPDATE', quantity_difference, NEW.unit_type, 
            NEW.id, 'purchase_materials', 
            CONCAT('Updated from ', OLD.quantity, ' to ', NEW.quantity, ' ', NEW.unit_type), 
            CURRENT_TIMESTAMP
        );
    END IF;
    
    -- Update material information if needed
    UPDATE inventory_materials 
    SET unit_type = NEW.unit_type,
        pieces_per_carton = NEW.pieces_per_carton,
        bags_per_barrel = NEW.bags_per_barrel,
        liters_per_bag = NEW.liters_per_bag,
        liters_per_barrel = NEW.liters_per_barrel,
        purchase_price_usd = NEW.purchase_price_usd,
        purchase_price_iqd = NEW.purchase_price_iqd,
        price_per_piece = NEW.price_per_piece,
        price_per_liter = NEW.price_per_liter,
        price_per_bag = NEW.price_per_bag,
        updated_at = CURRENT_TIMESTAMP
    WHERE id = NEW.material_id;
END$$
DELIMITER ;

-- =====================================================
-- TRIGGER: after_purchase_materials_delete
-- Purpose: Handle inventory updates when purchases are deleted
-- =====================================================
DELIMITER $$
CREATE TRIGGER `after_purchase_materials_delete` 
AFTER DELETE ON `purchase_materials` 
FOR EACH ROW 
BEGIN
    DECLARE current_quantity DECIMAL(15,2) DEFAULT 0;
    
    -- Get current quantity for this unit type
    SELECT quantity INTO current_quantity
    FROM inventory_by_unit 
    WHERE material_id = OLD.material_id AND unit_type = OLD.unit_type;
    
    -- Update inventory for the unit type
    UPDATE inventory_by_unit 
    SET quantity = quantity - OLD.quantity,
        updated_at = CURRENT_TIMESTAMP
    WHERE material_id = OLD.material_id AND unit_type = OLD.unit_type;
    
    -- If quantity becomes negative or zero, delete the record
    DELETE FROM inventory_by_unit 
    WHERE material_id = OLD.material_id AND unit_type = OLD.unit_type AND quantity <= 0;
    
    -- Log the operation
    INSERT INTO inventory_log (
        material_id, operation_type, quantity, unit_type, 
        reference_id, reference_table, notes, created_at
    ) VALUES (
        OLD.material_id, 'DELETE', OLD.quantity, OLD.unit_type, 
        OLD.id, 'purchase_materials', 
        CONCAT('Removed ', OLD.quantity, ' ', OLD.unit_type, ' from inventory'), 
        CURRENT_TIMESTAMP
    );
    
    -- Check if material has any remaining inventory
    SELECT COUNT(*) INTO @remaining_inventory
    FROM inventory_by_unit 
    WHERE material_id = OLD.material_id AND quantity > 0;
    
    -- If no remaining inventory, you might want to mark the material as inactive
    -- This is optional and depends on your business logic
    IF @remaining_inventory = 0 THEN
        -- You can add logic here to mark material as inactive if needed
        -- UPDATE inventory_materials SET status = 'inactive' WHERE id = OLD.material_id;
        NULL; -- Placeholder for future logic
    END IF;
END$$
DELIMITER ;

-- =====================================================
-- ADDITIONAL TRIGGERS FOR SALES (if needed)
-- =====================================================

-- Drop existing sales triggers if they exist
DROP TRIGGER IF EXISTS `after_sales_insert`;
DROP TRIGGER IF EXISTS `after_sales_update`;
DROP TRIGGER IF EXISTS `after_sales_delete`;

-- =====================================================
-- TRIGGER: after_sales_insert
-- Purpose: Handle inventory reduction when sales are made
-- =====================================================
DELIMITER $$
CREATE TRIGGER `after_sales_insert` 
AFTER INSERT ON `sales` 
FOR EACH ROW 
BEGIN
    DECLARE available_quantity DECIMAL(15,2) DEFAULT 0;
    DECLARE material_unit_type VARCHAR(20);
    
    -- Get the unit type for this material
    SELECT unit_type INTO material_unit_type
    FROM inventory_materials 
    WHERE id = NEW.material_id;
    
    -- Get available quantity for this unit type
    SELECT quantity INTO available_quantity
    FROM inventory_by_unit 
    WHERE material_id = NEW.material_id AND unit_type = material_unit_type;
    
    -- Check if we have enough inventory
    IF available_quantity >= NEW.quantity THEN
        -- Reduce inventory
        UPDATE inventory_by_unit 
        SET quantity = quantity - NEW.quantity,
            updated_at = CURRENT_TIMESTAMP
        WHERE material_id = NEW.material_id AND unit_type = material_unit_type;
        
        -- Log the operation
        INSERT INTO inventory_log (
            material_id, operation_type, quantity, unit_type, 
            reference_id, reference_table, notes, created_at
        ) VALUES (
            NEW.material_id, 'SALE', NEW.quantity, material_unit_type, 
            NEW.id, 'sales', 
            CONCAT('Sold ', NEW.quantity, ' ', material_unit_type), 
            CURRENT_TIMESTAMP
        );
    ELSE
        -- Raise an error if insufficient inventory
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Insufficient inventory for this sale';
    END IF;
END$$
DELIMITER ;

-- =====================================================
-- TRIGGER: after_sales_update
-- Purpose: Handle inventory adjustments when sales are modified
-- =====================================================
DELIMITER $$
CREATE TRIGGER `after_sales_update` 
AFTER UPDATE ON `sales` 
FOR EACH ROW 
BEGIN
    DECLARE quantity_difference DECIMAL(15,2);
    DECLARE material_unit_type VARCHAR(20);
    
    -- Get the unit type for this material
    SELECT unit_type INTO material_unit_type
    FROM inventory_materials 
    WHERE id = NEW.material_id;
    
    -- Calculate quantity difference
    SET quantity_difference = OLD.quantity - NEW.quantity;
    
    -- If quantity increased (sale reduced), add back to inventory
    IF quantity_difference > 0 THEN
        UPDATE inventory_by_unit 
        SET quantity = quantity + quantity_difference,
            updated_at = CURRENT_TIMESTAMP
        WHERE material_id = NEW.material_id AND unit_type = material_unit_type;
        
        -- Log the operation
        INSERT INTO inventory_log (
            material_id, operation_type, quantity, unit_type, 
            reference_id, reference_table, notes, created_at
        ) VALUES (
            NEW.material_id, 'SALE_UPDATE', quantity_difference, material_unit_type, 
            NEW.id, 'sales', 
            CONCAT('Sale reduced by ', quantity_difference, ' ', material_unit_type), 
            CURRENT_TIMESTAMP
        );
    -- If quantity decreased (sale increased), check if we have enough inventory
    ELSEIF quantity_difference < 0 THEN
        DECLARE available_quantity DECIMAL(15,2) DEFAULT 0;
        
        SELECT quantity INTO available_quantity
        FROM inventory_by_unit 
        WHERE material_id = NEW.material_id AND unit_type = material_unit_type;
        
        IF available_quantity >= ABS(quantity_difference) THEN
            UPDATE inventory_by_unit 
            SET quantity = quantity - ABS(quantity_difference),
                updated_at = CURRENT_TIMESTAMP
            WHERE material_id = NEW.material_id AND unit_type = material_unit_type;
            
            -- Log the operation
            INSERT INTO inventory_log (
                material_id, operation_type, quantity, unit_type, 
                reference_id, reference_table, notes, created_at
            ) VALUES (
                NEW.material_id, 'SALE_UPDATE', ABS(quantity_difference), material_unit_type, 
                NEW.id, 'sales', 
                CONCAT('Sale increased by ', ABS(quantity_difference), ' ', material_unit_type), 
                CURRENT_TIMESTAMP
            );
        ELSE
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Insufficient inventory for this sale increase';
        END IF;
    END IF;
END$$
DELIMITER ;

-- =====================================================
-- TRIGGER: after_sales_delete
-- Purpose: Handle inventory restoration when sales are deleted
-- =====================================================
DELIMITER $$
CREATE TRIGGER `after_sales_delete` 
AFTER DELETE ON `sales` 
FOR EACH ROW 
BEGIN
    DECLARE material_unit_type VARCHAR(20);
    
    -- Get the unit type for this material
    SELECT unit_type INTO material_unit_type
    FROM inventory_materials 
    WHERE id = OLD.material_id;
    
    -- Restore inventory
    UPDATE inventory_by_unit 
    SET quantity = quantity + OLD.quantity,
        updated_at = CURRENT_TIMESTAMP
    WHERE material_id = OLD.material_id AND unit_type = material_unit_type;
    
    -- Log the operation
    INSERT INTO inventory_log (
        material_id, operation_type, quantity, unit_type, 
        reference_id, reference_table, notes, created_at
    ) VALUES (
        OLD.material_id, 'SALE_DELETE', OLD.quantity, material_unit_type, 
        OLD.id, 'sales', 
        CONCAT('Sale deleted, restored ', OLD.quantity, ' ', material_unit_type), 
        CURRENT_TIMESTAMP
    );
END$$
DELIMITER ;

-- =====================================================
-- TRIGGER VERIFICATION QUERIES
-- =====================================================

-- Query to check if triggers were created successfully
-- SELECT TRIGGER_NAME, EVENT_MANIPULATION, EVENT_OBJECT_TABLE, ACTION_STATEMENT 
-- FROM INFORMATION_SCHEMA.TRIGGERS 
-- WHERE TRIGGER_SCHEMA = DATABASE() 
-- AND EVENT_OBJECT_TABLE IN ('purchase_materials', 'sales')
-- ORDER BY EVENT_OBJECT_TABLE, EVENT_MANIPULATION;

-- Query to test inventory tracking
-- SELECT 
--     im.name,
--     im.unit_type,
--     COALESCE(carton_qty.quantity, 0) as carton_quantity,
--     COALESCE(piece_qty.quantity, 0) as piece_quantity,
--     COALESCE(barrel_qty.quantity, 0) as barrel_quantity,
--     COALESCE(bag_qty.quantity, 0) as bag_quantity,
--     COALESCE(liter_qty.quantity, 0) as liter_quantity
-- FROM inventory_materials im
-- LEFT JOIN inventory_by_unit carton_qty ON im.id = carton_qty.material_id AND carton_qty.unit_type = 'carton'
-- LEFT JOIN inventory_by_unit piece_qty ON im.id = piece_qty.material_id AND piece_qty.unit_type = 'piece'
-- LEFT JOIN inventory_by_unit barrel_qty ON im.id = barrel_qty.material_id AND barrel_qty.unit_type = 'barrel'
-- LEFT JOIN inventory_by_unit bag_qty ON im.id = bag_qty.material_id AND bag_qty.unit_type = 'bag'
-- LEFT JOIN inventory_by_unit liter_qty ON im.id = liter_qty.material_id AND liter_qty.unit_type = 'liter'
-- WHERE im.id = [MATERIAL_ID]; 