-- Migration: allow nullable name/phone for recipients (2025-11-24)
ALTER TABLE `recipients`
    MODIFY `name` VARCHAR(255) NULL DEFAULT NULL,
    MODIFY `phone1` VARCHAR(20) NULL DEFAULT NULL;





