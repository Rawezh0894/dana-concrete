-- کوێری بۆ نوێکردنەوەی تەیبڵی purchases بۆ کۆمپانیای 'سۆران'
-- لە ڕۆژی 1/08/2025 بۆ 31/08/2025
-- amount_iqd و remaining_iqd دەبنە 175000

UPDATE purchases 
SET 
    amount_iqd = 175000,
    remaining_iqd = 175000
WHERE 
    company_id = (
        SELECT id 
        FROM company 
        WHERE name = 'سۆران'
    )
    AND date BETWEEN '2025-08-01' AND '2025-08-31';

-- بۆ چاودێری کردن، دەتوانیت ئەم کوێریە بەکاربهێنیت بۆ بینینی ئەنجامەکە:
-- SELECT 
--     p.id,
--     p.date,
--     p.invoice_number,
--     c.name as company_name,
--     p.amount_iqd,
--     p.remaining_iqd
-- FROM purchases p
-- JOIN company c ON p.company_id = c.id
-- WHERE c.name = 'سۆران'
-- AND p.date BETWEEN '2025-08-01' AND '2025-08-31';

