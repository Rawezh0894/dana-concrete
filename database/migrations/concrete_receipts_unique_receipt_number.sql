-- Prevent duplicate concrete receipt numbers (protects against concurrent inserts).
--
-- STEP 1 (optional): find existing duplicates before adding the constraint.
--   SELECT receipt_number, COUNT(*) AS c
--   FROM concrete_receipts
--   GROUP BY receipt_number
--   HAVING c > 1;
--
-- Resolve any duplicates first (edit the offending rows to unique numbers),
-- otherwise STEP 2 will fail with error 1062.
--
-- STEP 2: add the UNIQUE index.
ALTER TABLE `concrete_receipts`
  ADD UNIQUE KEY `uq_receipt_number` (`receipt_number`);
