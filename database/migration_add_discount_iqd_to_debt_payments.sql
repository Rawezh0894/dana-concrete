ALTER TABLE `debt_payments`
    ADD COLUMN `discount_iqd` DECIMAL(20,2) NOT NULL DEFAULT 0.00 AFTER `discount_usd`;

