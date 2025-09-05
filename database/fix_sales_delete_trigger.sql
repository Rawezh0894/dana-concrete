-- Fix for sales delete trigger - correct black_sand and brown_sand IDs
-- black_sand should be id = 2, brown_sand should be id = 1

DELIMITER $$

-- Drop the existing trigger
DROP TRIGGER IF EXISTS `trg_after_delete_sale`;

-- Recreate the trigger with correct IDs
CREATE TRIGGER `trg_after_delete_sale` AFTER DELETE ON `sales` FOR EACH ROW BEGIN
    DECLARE v_black_sand_kg DECIMAL(10,2);
    DECLARE v_brown_sand_kg DECIMAL(10,2);
    DECLARE v_gravel_bin3_kg DECIMAL(10,2);
    DECLARE v_gravel_bin4_kg DECIMAL(10,2);
    DECLARE v_cement_kg DECIMAL(10,2);
    DECLARE v_cement_cem2_kg DECIMAL(10,2);
    DECLARE v_additive_kg DECIMAL(10,2);
    DECLARE v_total_volume DECIMAL(10,2);

    -- هەژمارکردنی مەتری سێجا
    SET v_total_volume = OLD.quantity;

    -- وەرگرتنی ڕێژەکانی فۆرمۆلاکە
    SELECT black_sand_kg, brown_sand_kg, gravel_bin3_kg, gravel_bin4_kg, cement_cem1_kg, cement_cem2_kg, additive_kg
    INTO v_black_sand_kg, v_brown_sand_kg, v_gravel_bin3_kg, v_gravel_bin4_kg, v_cement_kg, v_cement_cem2_kg, v_additive_kg
    FROM concrete_formulas 
    WHERE id = OLD.formula_id;

    -- لێکدانی ڕێژەکان بۆ قەبارەی فرۆشراو
    SET v_black_sand_kg = v_black_sand_kg * v_total_volume;
    SET v_brown_sand_kg = v_brown_sand_kg * v_total_volume;
    SET v_gravel_bin3_kg = v_gravel_bin3_kg * v_total_volume;
    SET v_gravel_bin4_kg = v_gravel_bin4_kg * v_total_volume;
    SET v_cement_kg = v_cement_kg * v_total_volume;
    SET v_cement_cem2_kg = v_cement_cem2_kg * v_total_volume;
    SET v_additive_kg = v_additive_kg * v_total_volume;

    -- گەڕاندنەوەی ماتریاڵەکان (stock) بۆ bins_silos
    -- FIXED: black_sand = id 2, brown_sand = id 1
    IF v_black_sand_kg > 0 THEN
        UPDATE bins_silos 
        SET amount = amount + v_black_sand_kg, 
            total_value = (amount * average_price)
        WHERE id = 2;
    END IF;

    IF v_brown_sand_kg > 0 THEN
        UPDATE bins_silos 
        SET amount = amount + v_brown_sand_kg, 
            total_value = (amount * average_price)
        WHERE id = 1;
    END IF;

    IF v_gravel_bin3_kg > 0 THEN
        UPDATE bins_silos 
        SET amount = amount + v_gravel_bin3_kg, 
            total_value = (amount * average_price)
        WHERE id = 3;
    END IF;

    IF v_gravel_bin4_kg > 0 THEN
        UPDATE bins_silos 
        SET amount = amount + v_gravel_bin4_kg, 
            total_value = (amount * average_price)
        WHERE id = 4;
    END IF;

    IF v_cement_kg > 0 THEN
        UPDATE bins_silos 
        SET amount = amount + v_cement_kg, 
            total_value = (amount * average_price)
        WHERE id = 5;
    END IF;

    IF v_cement_cem2_kg > 0 THEN
        UPDATE bins_silos 
        SET amount = amount + v_cement_cem2_kg, 
            total_value = (amount * average_price)
        WHERE id = 6;
    END IF;

    IF v_additive_kg > 0 THEN
        UPDATE bins_silos 
        SET amount = amount + v_additive_kg, 
            total_value = (amount * average_price)
        WHERE id = 7;
    END IF;
END
$$

DELIMITER ;

-- Verify the fix by checking bins_silos table structure
-- black_sand should be id = 2, brown_sand should be id = 1
SELECT id, name, material_type FROM bins_silos WHERE material_type IN ('لمی ڕەش', 'لمی کەسارە') ORDER BY id;
