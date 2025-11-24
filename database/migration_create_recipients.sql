-- Migration: create recipients table and permissions (2025-11-20)
SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

CREATE TABLE IF NOT EXISTS `recipients` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) DEFAULT NULL,
  `phone1` VARCHAR(20) DEFAULT NULL,
  `phone2` VARCHAR(20) DEFAULT NULL,
  `opening_meter_total` DECIMAL(12,2) DEFAULT 0.00,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_phone1` (`phone1`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed permissions for recipients management
INSERT INTO `permissions` (`name`, `description`)
SELECT 'view_recipient', 'بینینی لیستی وەرگرەکان'
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'view_recipient');

INSERT INTO `permissions` (`name`, `description`)
SELECT 'add_recipient', 'زیادکردنی وەرگرێکی نوێ'
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'add_recipient');

INSERT INTO `permissions` (`name`, `description`)
SELECT 'edit_recipient', 'دەستکاری وەرگر'
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'edit_recipient');

INSERT INTO `permissions` (`name`, `description`)
SELECT 'delete_recipient', 'سڕینەوەی وەرگر'
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'delete_recipient');

-- Grant permissions to admin role by default
INSERT INTO `role_permissions` (`role`, `permission_id`)
SELECT 'admin', p.id
FROM `permissions` p
WHERE p.`name` = 'view_recipient'
  AND NOT EXISTS (
    SELECT 1 FROM `role_permissions` rp 
    WHERE rp.`role` = 'admin' AND rp.`permission_id` = p.`id`
  );

INSERT INTO `role_permissions` (`role`, `permission_id`)
SELECT 'admin', p.id
FROM `permissions` p
WHERE p.`name` = 'add_recipient'
  AND NOT EXISTS (
    SELECT 1 FROM `role_permissions` rp 
    WHERE rp.`role` = 'admin' AND rp.`permission_id` = p.`id`
  );

INSERT INTO `role_permissions` (`role`, `permission_id`)
SELECT 'admin', p.id
FROM `permissions` p
WHERE p.`name` = 'edit_recipient'
  AND NOT EXISTS (
    SELECT 1 FROM `role_permissions` rp 
    WHERE rp.`role` = 'admin' AND rp.`permission_id` = p.`id`
  );

INSERT INTO `role_permissions` (`role`, `permission_id`)
SELECT 'admin', p.id
FROM `permissions` p
WHERE p.`name` = 'delete_recipient'
  AND NOT EXISTS (
    SELECT 1 FROM `role_permissions` rp 
    WHERE rp.`role` = 'admin' AND rp.`permission_id` = p.`id`
  );

SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;

