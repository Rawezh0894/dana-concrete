-- Migration: Add permission for viewing companies
-- Date: 2025-08-29
-- Description: Add permission to view companies list and pages

SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO';
SET FOREIGN_KEY_CHECKS=0;
SET UNIQUE_CHECKS=0;

-- Step 1: Add permission for viewing companies
INSERT INTO `permissions` (`name`, `description`)
SELECT 'view_company', 'بینینی لیستی کۆمپانیاکان'
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'view_company');

-- Step 2: Grant permission to admin role by default
INSERT INTO `role_permissions` (`role`, `permission_id`)
SELECT 'admin', p.id
FROM `permissions` p
WHERE p.`name` = 'view_company'
  AND NOT EXISTS (
    SELECT 1 FROM `role_permissions` rp 
    WHERE rp.`role` = 'admin' AND rp.`permission_id` = p.`id`
  );

SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=1;
SET UNIQUE_CHECKS=1;

