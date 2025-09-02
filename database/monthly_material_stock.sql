-- خشتەی هەڵگرتنی بڕی مەوادەکان لە کۆتا ڕۆژی مانگەکان
-- Monthly Material Stock History Table

CREATE TABLE `monthly_material_stock` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bin_id` int(11) NOT NULL,
  `bin_name` varchar(50) NOT NULL,
  `material_type` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_value` decimal(12,2) NOT NULL DEFAULT 0.00,
  `average_price` decimal(15,10) DEFAULT NULL,
  `month_year` varchar(7) NOT NULL COMMENT 'Format: YYYY-MM',
  `recorded_date` date NOT NULL COMMENT 'Date when this record was created',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` int(11) DEFAULT NULL COMMENT 'User ID who created this record',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_bin_month` (`bin_id`, `month_year`),
  KEY `idx_month_year` (`month_year`),
  KEY `idx_bin_id` (`bin_id`),
  KEY `idx_recorded_date` (`recorded_date`),
  CONSTRAINT `fk_monthly_stock_bin` FOREIGN KEY (`bin_id`) REFERENCES `bins_silos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_monthly_stock_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='هەڵگرتنی بڕی مەوادەکان لە کۆتا ڕۆژی مانگەکان';

-- فەنکشنی ئۆتۆماتیک بۆ هەڵگرتنی بڕی مەوادەکان لە کۆتا ڕۆژی مانگ
DELIMITER $$

CREATE PROCEDURE `RecordMonthlyMaterialStock`(
    IN p_month_year VARCHAR(7),
    IN p_user_id INT
)
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_bin_id INT;
    DECLARE v_bin_name VARCHAR(50);
    DECLARE v_material_type VARCHAR(50);
    DECLARE v_amount DECIMAL(10,2);
    DECLARE v_total_value DECIMAL(12,2);
    DECLARE v_average_price DECIMAL(15,10);
    
    -- Cursor to iterate through all bins
    DECLARE bin_cursor CURSOR FOR 
        SELECT id, name, material_type, amount, total_value, average_price 
        FROM bins_silos;
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    -- Start transaction
    START TRANSACTION;
    
    -- Open cursor
    OPEN bin_cursor;
    
    -- Loop through all bins
    read_loop: LOOP
        FETCH bin_cursor INTO v_bin_id, v_bin_name, v_material_type, v_amount, v_total_value, v_average_price;
        
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        -- Insert or update monthly stock record
        INSERT INTO monthly_material_stock (
            bin_id, bin_name, material_type, amount, total_value, average_price, 
            month_year, recorded_date, created_by
        ) VALUES (
            v_bin_id, v_bin_name, v_material_type, v_amount, v_total_value, v_average_price,
            p_month_year, CURDATE(), p_user_id
        ) ON DUPLICATE KEY UPDATE
            amount = VALUES(amount),
            total_value = VALUES(total_value),
            average_price = VALUES(average_price),
            recorded_date = VALUES(recorded_date),
            created_by = VALUES(created_by);
            
    END LOOP;
    
    -- Close cursor
    CLOSE bin_cursor;
    
    -- Commit transaction
    COMMIT;
    
    -- Return success message
    SELECT CONCAT('Monthly stock recorded successfully for ', p_month_year) as message;
    
END$$

DELIMITER ;

-- فەنکشنی گەڕاندنەوەی بڕی مەوادەکان بۆ ڕۆژێکی دیاریکراو
DELIMITER $$

CREATE FUNCTION `GetMaterialStockForDate`(
    p_bin_id INT,
    p_target_date DATE
) RETURNS DECIMAL(10,2)
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE v_stock_amount DECIMAL(10,2) DEFAULT 0.00;
    DECLARE v_month_year VARCHAR(7);
    
    -- Get the month-year for the target date
    SET v_month_year = DATE_FORMAT(p_target_date, '%Y-%m');
    
    -- Get the stock amount for that month
    SELECT amount INTO v_stock_amount
    FROM monthly_material_stock
    WHERE bin_id = p_bin_id 
    AND month_year = v_month_year
    LIMIT 1;
    
    -- If no record found, return 0
    IF v_stock_amount IS NULL THEN
        SET v_stock_amount = 0.00;
    END IF;
    
    RETURN v_stock_amount;
    
END$$

DELIMITER ;

-- فەنکشنی گەڕاندنەوەی مێژووی بڕی مەوادەکان
DELIMITER $$

CREATE PROCEDURE `GetMaterialStockHistory`(
    IN p_bin_id INT DEFAULT NULL,
    IN p_start_date DATE DEFAULT NULL,
    IN p_end_date DATE DEFAULT NULL
)
BEGIN
    SELECT 
        mms.id,
        mms.bin_id,
        mms.bin_name,
        mms.material_type,
        mms.amount,
        mms.total_value,
        mms.average_price,
        mms.month_year,
        mms.recorded_date,
        mms.created_at,
        u.username as created_by_username
    FROM monthly_material_stock mms
    LEFT JOIN users u ON mms.created_by = u.id
    WHERE (p_bin_id IS NULL OR mms.bin_id = p_bin_id)
    AND (p_start_date IS NULL OR mms.recorded_date >= p_start_date)
    AND (p_end_date IS NULL OR mms.recorded_date <= p_end_date)
    ORDER BY mms.month_year DESC, mms.bin_name ASC;
    
END$$

DELIMITER ;
