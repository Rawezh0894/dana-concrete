<?php
// Fix for GROUP BY queries that are causing SQL mode errors
// This file shows how to modify queries to be compatible with ONLY_FULL_GROUP_BY mode

echo "<h2>GROUP BY Query Fixes</h2>";

// Example 1: Original problematic query
echo "<h3>Problematic Query:</h3>";
echo "<pre>";
echo "SELECT pm.id, pm.receipt_number, pm.purchase_date, pm.total_price_usd, pm.total_price_iqd, pm.currency_type, pm.notes, p.name as person_name, m.name as material_name
FROM purchase_materials pm
LEFT JOIN other_expense_persons p ON pm.person_id = p.id
LEFT JOIN list_materials m ON pm.material_id = m.id
GROUP BY pm.receipt_number";
echo "</pre>";

echo "<h3>Fixed Query (Option 1 - Add all non-aggregated columns to GROUP BY):</h3>";
echo "<pre>";
echo "SELECT pm.id, pm.receipt_number, pm.purchase_date, pm.total_price_usd, pm.total_price_iqd, pm.currency_type, pm.notes, p.name as person_name, m.name as material_name
FROM purchase_materials pm
LEFT JOIN other_expense_persons p ON pm.person_id = p.id
LEFT JOIN list_materials m ON pm.material_id = m.id
GROUP BY pm.id, pm.receipt_number, pm.purchase_date, pm.total_price_usd, pm.total_price_iqd, pm.currency_type, pm.notes, p.name, m.name";
echo "</pre>";

echo "<h3>Fixed Query (Option 2 - Use ANY_VALUE for non-aggregated columns):</h3>";
echo "<pre>";
echo "SELECT ANY_VALUE(pm.id) as id, pm.receipt_number, ANY_VALUE(pm.purchase_date) as purchase_date, 
       ANY_VALUE(pm.total_price_usd) as total_price_usd, ANY_VALUE(pm.total_price_iqd) as total_price_iqd, 
       ANY_VALUE(pm.currency_type) as currency_type, ANY_VALUE(pm.notes) as notes, 
       ANY_VALUE(p.name) as person_name, ANY_VALUE(m.name) as material_name
FROM purchase_materials pm
LEFT JOIN other_expense_persons p ON pm.person_id = p.id
LEFT JOIN list_materials m ON pm.material_id = m.id
GROUP BY pm.receipt_number";
echo "</pre>";

echo "<h3>Fixed Query (Option 3 - Use DISTINCT instead of GROUP BY):</h3>";
echo "<pre>";
echo "SELECT DISTINCT pm.id, pm.receipt_number, pm.purchase_date, pm.total_price_usd, pm.total_price_iqd, pm.currency_type, pm.notes, p.name as person_name, m.name as material_name
FROM purchase_materials pm
LEFT JOIN other_expense_persons p ON pm.person_id = p.id
LEFT JOIN list_materials m ON pm.material_id = m.id";
echo "</pre>";

echo "<h3>Fixed Query (Option 4 - Use subquery to get unique receipt numbers):</h3>";
echo "<pre>";
echo "SELECT pm.id, pm.receipt_number, pm.purchase_date, pm.total_price_usd, pm.total_price_iqd, pm.currency_type, pm.notes, p.name as person_name, m.name as material_name
FROM purchase_materials pm
LEFT JOIN other_expense_persons p ON pm.person_id = p.id
LEFT JOIN list_materials m ON pm.material_id = m.id
WHERE pm.id IN (
    SELECT MIN(id) 
    FROM purchase_materials 
    GROUP BY receipt_number
)";
echo "</pre>";

echo "<h3>Recommended Solution:</h3>";
echo "<p>Use Option 1 (add all columns to GROUP BY) or Option 3 (use DISTINCT) for the best compatibility.</p>";

echo "<h3>To apply the SQL mode fix permanently:</h3>";
echo "<pre>";
echo "-- Run this on the server:
SET GLOBAL sql_mode = (SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''));
SET SESSION sql_mode = (SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''));

-- Or add to MySQL configuration file (my.cnf):
-- sql_mode = STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION";
echo "</pre>";
?> 