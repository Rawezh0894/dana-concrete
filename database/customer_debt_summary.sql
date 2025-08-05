-- Customer Debt Summary - Total Summary Only
-- This query shows the total remaining amounts from sales and total opening debts

SELECT 
    'کۆی پارەی ماوەی مامەڵەکان' AS debt_type,
    COALESCE(SUM(s.remaining_amount), 0) AS total_amount,
    COUNT(DISTINCT s.customer_id) AS affected_customers
FROM sales s
WHERE s.remaining_amount > 0

UNION ALL

SELECT 
    'کۆی قەرزی سەرەتایی کڕیاران' AS debt_type,
    (COALESCE(SUM(c.opening_debt_usd), 0) + COALESCE(SUM(c.opening_debt_iqd), 0)) AS total_amount,
    COUNT(*) AS affected_customers
FROM customers c
WHERE c.opening_debt_usd > 0 OR c.opening_debt_iqd > 0

UNION ALL

SELECT 
    'کۆی گشتی' AS debt_type,
    (COALESCE((SELECT SUM(remaining_amount) FROM sales WHERE remaining_amount > 0), 0) + 
     COALESCE((SELECT SUM(opening_debt_usd) FROM customers WHERE opening_debt_usd > 0), 0) + 
     COALESCE((SELECT SUM(opening_debt_iqd) FROM customers WHERE opening_debt_iqd > 0), 0)) AS total_amount,
    (SELECT COUNT(DISTINCT customer_id) FROM sales WHERE remaining_amount > 0) + 
    (SELECT COUNT(*) FROM customers WHERE opening_debt_usd > 0 OR opening_debt_iqd > 0) AS affected_customers;

-- ==========================================
-- VERIFICATION QUERY - بۆ دۆزینەوەی هەڵە
-- ==========================================

-- 1. هەموو فرۆشتنەکان بە وردەکاری + کۆی گشتی
SELECT 
    'فرۆشتنەکان' AS record_type,
    s.id AS record_id,
    c.name AS customer_name,
    s.invoice_number,
    s.total_price,
    s.amount_paid_usd,
    s.amount_paid_iq,
    s.remaining_amount,
    s.order_date,
    s.payment_type
FROM sales s
LEFT JOIN customers c ON s.customer_id = c.id
WHERE s.remaining_amount > 0

UNION ALL

SELECT 
    '=== کۆی گشتی فرۆشتنەکان ===' AS record_type,
    NULL AS record_id,
    NULL AS customer_name,
    NULL AS invoice_number,
    SUM(s.total_price) AS total_price,
    SUM(s.amount_paid_usd) AS amount_paid_usd,
    SUM(s.amount_paid_iq) AS amount_paid_iq,
    SUM(s.remaining_amount) AS remaining_amount,
    NULL AS order_date,
    NULL AS payment_type
FROM sales s
WHERE s.remaining_amount > 0

ORDER BY record_type DESC, remaining_amount DESC;

-- 2. هەموو کڕیارەکان بە قەرزی سەرەتایی + کۆی گشتی
SELECT 
    'قەرزی سەرەتایی' AS record_type,
    c.id AS record_id,
    c.name AS customer_name,
    c.mobile1,
    c.opening_debt_usd,
    c.opening_debt_iqd,
    (c.opening_debt_usd + c.opening_debt_iqd) AS total_opening_debt
FROM customers c
WHERE c.opening_debt_usd > 0 OR c.opening_debt_iqd > 0

UNION ALL

SELECT 
    '=== کۆی گشتی قەرزی سەرەتایی ===' AS record_type,
    NULL AS record_id,
    NULL AS customer_name,
    NULL AS mobile1,
    SUM(c.opening_debt_usd) AS opening_debt_usd,
    SUM(c.opening_debt_iqd) AS opening_debt_iqd,
    SUM(c.opening_debt_usd + c.opening_debt_iqd) AS total_opening_debt
FROM customers c
WHERE c.opening_debt_usd > 0 OR c.opening_debt_iqd > 0

ORDER BY record_type DESC, total_opening_debt DESC;

-- 3. کۆی گشتی بۆ هەر کڕیارێک + کۆی گشتی
SELECT 
    c.id AS customer_id,
    c.name AS customer_name,
    -- Sales remaining
    COALESCE(SUM(s.remaining_amount), 0) AS sales_remaining,
    -- Opening debt
    c.opening_debt_usd,
    c.opening_debt_iqd,
    (c.opening_debt_usd + c.opening_debt_iqd) AS total_opening_debt,
    -- Combined total
    (COALESCE(SUM(s.remaining_amount), 0) + c.opening_debt_usd + c.opening_debt_iqd) AS total_debt,
    -- Count of unpaid sales
    COUNT(CASE WHEN s.remaining_amount > 0 THEN 1 END) AS unpaid_sales_count
FROM customers c
LEFT JOIN sales s ON c.id = s.customer_id
GROUP BY c.id, c.name, c.opening_debt_usd, c.opening_debt_iqd
HAVING (COALESCE(SUM(s.remaining_amount), 0) + c.opening_debt_usd + c.opening_debt_iqd) > 0

UNION ALL

SELECT 
    NULL AS customer_id,
    '=== کۆی گشتی هەموو کڕیارەکان ===' AS customer_name,
    -- Sales remaining
    COALESCE(SUM(s.remaining_amount), 0) AS sales_remaining,
    -- Opening debt
    SUM(c.opening_debt_usd) AS opening_debt_usd,
    SUM(c.opening_debt_iqd) AS opening_debt_iqd,
    SUM(c.opening_debt_usd + c.opening_debt_iqd) AS total_opening_debt,
    -- Combined total
    (COALESCE(SUM(s.remaining_amount), 0) + SUM(c.opening_debt_usd) + SUM(c.opening_debt_iqd)) AS total_debt,
    -- Count of unpaid sales
    COUNT(CASE WHEN s.remaining_amount > 0 THEN 1 END) AS unpaid_sales_count
FROM customers c
LEFT JOIN sales s ON c.id = s.customer_id

ORDER BY customer_name DESC, total_debt DESC; 