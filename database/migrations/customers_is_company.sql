-- Mark customer as company vs regular. Run in MySQL client, e.g.:
--   mysql -u USER -p DATABASE < database/migrations/customers_is_company.sql
--
-- is_company = 1 → کۆمپانیا
-- is_company = 0 → کڕیاری ئاسایی

ALTER TABLE `customers`
  ADD COLUMN `is_company` TINYINT(1) NOT NULL DEFAULT 0
  AFTER `is_recipient`;
