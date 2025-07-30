-- Update notifications table structure to include detailed information
-- Run this SQL to update your existing database

-- Add new columns to notifications table
ALTER TABLE `notifications` 
ADD COLUMN `old_values` text DEFAULT NULL COMMENT 'JSON format of old values before change' AFTER `description`,
ADD COLUMN `new_values` text DEFAULT NULL COMMENT 'JSON format of new values after change' AFTER `old_values`,
ADD COLUMN `additional_info` text DEFAULT NULL COMMENT 'Additional context information' AFTER `new_values`,
ADD COLUMN `ip_address` varchar(45) DEFAULT NULL AFTER `additional_info`;

-- Update existing notifications to have empty values for new columns
UPDATE `notifications` SET 
`old_values` = NULL,
`new_values` = NULL,
`additional_info` = NULL,
`ip_address` = NULL;

-- Create function to create detailed notifications
DELIMITER $$
CREATE DEFINER=`root`@`localhost` FUNCTION `create_detailed_notification` (
    `p_user_id` INT, 
    `p_action` VARCHAR(50), 
    `p_table_name` VARCHAR(50), 
    `p_record_id` INT, 
    `p_description` TEXT, 
    `p_old_values` TEXT, 
    `p_new_values` TEXT, 
    `p_additional_info` TEXT, 
    `p_ip_address` VARCHAR(45)
) RETURNS INT(11) DETERMINISTIC READS SQL DATA 
BEGIN
    INSERT INTO notifications (
        user_id, action, table_name, record_id, description, 
        old_values, new_values, additional_info, ip_address
    ) VALUES (
        p_user_id, p_action, p_table_name, p_record_id, p_description,
        p_old_values, p_new_values, p_additional_info, p_ip_address
    );
    
    RETURN LAST_INSERT_ID();
END$$
DELIMITER ; 