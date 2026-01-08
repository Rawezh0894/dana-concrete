-- کوێری بۆ زیادکردنی نرخی کاروانحیسابی بۆ تەیبڵی settings
-- Run this query in your dana_concrete_db database

USE dana_concrete_db;

-- زیادکردنی نرخی کاروانحیسابی (ئەگەر نەبێت)
INSERT INTO settings (name, value)
SELECT 'overtime_rate', '0'
WHERE NOT EXISTS (
    SELECT 1 FROM settings WHERE name = 'overtime_rate'
);

-- پشکنینی settings
SELECT * FROM settings WHERE name IN ('usd_iqd_rate', 'overtime_rate');
