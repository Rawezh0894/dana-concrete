<?php
require_once 'config/db_conected.php';

header('Content-Type: text/html; charset=utf-8');
echo '<body dir="rtl" style="font-family: sans-serif;">';

echo "<h2>شیکردنەوەی وردی نرخی چیمەنتۆ (2026)</h2>";

try {
    // 1. Check raw rows
    $query = "
        SELECT 
            p.id,
            p.date,
            p.invoice_number,
            p.type as currency,
            p.exchange_rate,
            p.kg,
            p.price as raw_price_field,
            p.amount_iqd,
            p.paid_usd,
            p.paid_iqd,
            -- Calculate Normalized Total USD
            CASE 
                WHEN p.type = 'دۆلار' THEN p.price 
                ELSE p.amount_iqd / NULLIF(p.exchange_rate / 100, 0) 
            END as calculated_total_usd
        FROM purchases p
        JOIN materials m ON p.material_id = m.id
        WHERE m.name = 'چیمەنتۆ' 
        AND p.date >= '2026-01-01'
    ";

    $stmt = $pdo->query($query);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($rows) == 0) {
        echo "<p style='color:red'>هیچ داتایەک نەدۆزرایەوە بۆ چیمەنتۆ لە دوای 2026-01-01</p>";
    } else {
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background:#f0f0f0;'>
                <th>ID</th>
                <th>Beuwar</th>
                <th>Pasula</th>
                <th>Currency</th>
                <th>Rate</th>
                <th>KG</th>
                <th>Ton (KG/1000)</th>
                <th>Total Price (DB)</th>
                <th>Calc Total USD</th>
                <th>Price Per Ton (Calc)</th>
              </tr>";

        $sum_usd = 0;
        $sum_kg = 0;

        foreach ($rows as $row) {
            $usd = floatval($row['calculated_total_usd']);
            $kg = floatval($row['kg']);
            $ton = $kg / 1000;
            $price_per_ton = ($ton > 0) ? ($usd / $ton) : 0;

            $sum_usd += $usd;
            $sum_kg += $kg;

            // Highlight weird prices
            $style = "";
            if (abs($price_per_ton - 57) > 1) {
                $style = "background-color: #ffcccc;"; // Reddish if not 57
            }

            echo "<tr style='$style'>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['date']}</td>";
            echo "<td>{$row['invoice_number']}</td>";
            echo "<td>{$row['currency']}</td>";
            echo "<td>{$row['exchange_rate']}</td>";
            echo "<td>".number_format($kg, 2)."</td>";
            echo "<td>".number_format($ton, 3)."</td>";
            echo "<td>".number_format($row['raw_price_field'], 2)."</td>";
            echo "<td>".number_format($usd, 2)."$</td>";
            echo "<td><strong>".number_format($price_per_ton, 2)."$</strong></td>";
            echo "</tr>";
        }

        echo "</table>";

        $total_tons = $sum_kg / 1000;
        $final_avg = ($total_tons > 0) ? ($sum_usd / $total_tons) : 0;

        echo "<h3>ئەنجامی کۆتایی:</h3>";
        echo "<ul>";
        echo "<li>کۆی گشتی کێش: " . number_format($total_tons, 3) . " Ton</li>";
        echo "<li>کۆی گشتی پارە (USD): " . number_format($sum_usd, 2) . " $</li>";
        echo "<li><strong>تێکڕای نرخ (Average): " . number_format($final_avg, 4) . " $</strong></li>";
        echo "</ul>";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
