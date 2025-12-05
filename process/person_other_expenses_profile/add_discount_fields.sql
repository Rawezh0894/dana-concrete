-- Add discount_usd and discount_iqd fields to person_other_expenses_debt_payments table
ALTER TABLE `person_other_expenses_debt_payments`
ADD COLUMN `discount_usd` decimal(15,2) DEFAULT 0.00 AFTER `amount_iqd`,
ADD COLUMN `discount_iqd` decimal(15,2) DEFAULT 0.00 AFTER `discount_usd`;

