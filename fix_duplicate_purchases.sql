-- کوێری بۆ چاککردنەوەی ژمارە پسووڵە دووبارەکان
-- ئەم کوێریە ژمارە پسووڵە دووبارەکان دەدۆزێتەوە و تەنها یەک دانەیان دەهێڵێتەوە

-- 0. کوێری بۆ گۆڕینی شوێنەکانی 'کانی بی' بۆ 'کانی بیی'
UPDATE purchases 
SET location = 'کانی بیی'
WHERE location = 'کانی بی';

-- 0.1 کوێری بۆ پشتڕاستکردنەوەی گۆڕینەکە
SELECT 
    id,
    invoice_number,
    location,
    driver,
    company_id
FROM purchases 
WHERE location = 'کانی بیی'
ORDER BY invoice_number;

-- 1. سەرەتا سەیری ژمارە پسووڵە دووبارەکان بکە
-- ئەم کوێریە کڕینە دووبارەکان دەدۆزێتەوە بە پشتبەستن بە هەموو فیلدەکان
SELECT 
    invoice_number,
    date,
    driver,
    location,
    material_id,
    company_id,
    COUNT(*) as duplicate_count,
    GROUP_CONCAT(id ORDER BY id) as duplicate_ids
FROM purchases 
GROUP BY invoice_number, date, driver, location, material_id, company_id
HAVING COUNT(*) > 1
ORDER BY invoice_number, date;

-- 1.0.1 سەیری کڕینە دووبارەکان بکە بە شێوەیەکی سادەتر
SELECT 
    invoice_number,
    COUNT(*) as count
FROM purchases 
GROUP BY invoice_number
HAVING COUNT(*) > 1
ORDER BY invoice_number;

-- 1.0 سەیری هەموو کڕینەکان بکە بۆ بینینی داتاکان
SELECT 
    id,
    invoice_number,
    date,
    driver,
    location,
    material_id,
    company_id
FROM purchases 
ORDER BY invoice_number, date
LIMIT 20;

-- 1.1 سەیری هەموو کڕینەکان بکە بۆ بینینی شوێنەکان
SELECT 
    id,
    invoice_number,
    date,
    driver,
    location,
    material_id,
    company_id
FROM purchases 
WHERE location LIKE '%کانی بی%'
ORDER BY invoice_number, date;

-- 1.2 سەیری کڕینە دووبارەکان بکە بە شێوەیەکی سادەتر
SELECT 
    invoice_number,
    COUNT(*) as count
FROM purchases 
GROUP BY invoice_number
HAVING COUNT(*) > 1
ORDER BY invoice_number;

-- 2. کوێری بۆ سڕینەوەی ژمارە پسووڵە دووبارەکان (تەنها یەک دانە دەهێڵێتەوە)
-- ئەم کوێریە ژمارە پسووڵە دووبارەکان دەسڕێتەوە و تەنها کڕینەکەی کە ID ی بچووکترە دەهێڵێتەوە

-- 2.1 سڕینەوەی دووبارەکان بە پشتبەستن بە ژمارەی پسووڵە
DELETE p1 FROM purchases p1
INNER JOIN purchases p2 
WHERE 
    p1.id > p2.id 
    AND p1.invoice_number = p2.invoice_number;

-- 2.2 سڕینەوەی دووبارەکان بە پشتبەستن بە هەموو فیلدەکان
DELETE p1 FROM purchases p1
INNER JOIN purchases p2 
WHERE 
    p1.id > p2.id 
    AND p1.invoice_number = p2.invoice_number
    AND p1.date = p2.date
    AND p1.driver = p2.driver
    AND p1.location = p2.location
    AND p1.material_id = p2.material_id
    AND p1.company_id = p2.company_id;

-- 3. کوێری بۆ پشتڕاستکردنەوەی ئەنجامەکە
SELECT 
    invoice_number,
    date,
    driver,
    location,
    material_id,
    company_id,
    COUNT(*) as remaining_count
FROM purchases 
GROUP BY invoice_number, date, driver, location, material_id, company_id
HAVING COUNT(*) > 1
ORDER BY invoice_number, date;

-- 4. کوێری بۆ بینینی کڕینەکان دوای چاککردنەوە
SELECT 
    id,
    invoice_number,
    date,
    driver,
    location,
    material_id,
    kg,
    price,
    company_id,
    amount_iqd
FROM purchases 
ORDER BY invoice_number, date;

-- 5. کوێریەکانی زیاتر بۆ دۆزینەوەی کڕینە دووبارەکان
-- 5.1 سەیری کڕینە دووبارەکان بکە بە پشتبەستن بە ژمارەی پسووڵە و کۆمپانیا
SELECT 
    invoice_number,
    company_id,
    COUNT(*) as count
FROM purchases 
GROUP BY invoice_number, company_id
HAVING COUNT(*) > 1
ORDER BY invoice_number;

-- 5.2 سەیری کڕینە دووبارەکان بکە بە پشتبەستن بە ژمارەی پسووڵە و شوێن
SELECT 
    invoice_number,
    location,
    COUNT(*) as count
FROM purchases 
GROUP BY invoice_number, location
HAVING COUNT(*) > 1
ORDER BY invoice_number;

-- 5.3 سەیری کڕینە دووبارەکان بکە بە پشتبەستن بە ژمارەی پسووڵە و شۆفێر
SELECT 
    invoice_number,
    driver,
    COUNT(*) as count
FROM purchases 
GROUP BY invoice_number, driver
HAVING COUNT(*) > 1
ORDER BY invoice_number;

-- 5.4 سەیری کڕینە دووبارەکان بکە بە پشتبەستن بە ژمارەی پسووڵە و ماددە
SELECT 
    invoice_number,
    material_id,
    COUNT(*) as count
FROM purchases 
GROUP BY invoice_number, material_id
HAVING COUNT(*) > 1
ORDER BY invoice_number;

-- 6. کوێری بۆ سڕینەوەی کڕینە دووبارەکان بە پشتبەستن بە ژمارەی پسووڵە و کۆمپانیا
DELETE p1 FROM purchases p1
INNER JOIN purchases p2 
WHERE 
    p1.id > p2.id 
    AND p1.invoice_number = p2.invoice_number
    AND p1.company_id = p2.company_id;
