<?php
require_once '../../config/db_conected.php';
$materials = $pdo->query("SELECT id, name, unit_type, quantity, currency_type, purchase_price_usd, purchase_price_iqd, 
                         pieces_per_carton, buckets_per_barrel, liters_per_bucket, liters_per_barrel,
                         price_per_piece_usd, price_per_piece_iqd, price_per_bucket_usd, price_per_bucket_iqd,
                         price_per_liter_usd, price_per_liter_iqd 
                         FROM list_materials")->fetchAll(PDO::FETCH_ASSOC);
if (count($materials) === 0) {
    echo '<tr><td colspan="8" style="text-align:center">هیچ داتایەک نییە</td></tr>';
    exit;
}
foreach ($materials as $i => $mat) {
    echo '<tr>';
    echo '<td>' . ($i+1) . '</td>';
    echo '<td>' . htmlspecialchars($mat['name']) . '</td>';
    echo '<td>' . htmlspecialchars($mat['unit_type']) . '</td>';
    echo '<td>' . htmlspecialchars($mat['quantity']) . '</td>';
    echo '<td>' . htmlspecialchars($mat['currency_type']) . '</td>';
    echo '<td>' . htmlspecialchars($mat['purchase_price_usd']) . '</td>';
    echo '<td>' . htmlspecialchars($mat['purchase_price_iqd']) . '</td>';
    echo '<td>';
    echo '<button class="btn btn-sm btn-primary edit-btn" 
            data-id="' . $mat['id'] . '" 
            data-name="' . htmlspecialchars($mat['name']) . '" 
            data-unit_type="' . htmlspecialchars($mat['unit_type']) . '"
            data-quantity="' . htmlspecialchars($mat['quantity']) . '" 
            data-currency_type="' . htmlspecialchars($mat['currency_type']) . '" 
            data-purchase_price_usd="' . htmlspecialchars($mat['purchase_price_usd']) . '" 
            data-purchase_price_iqd="' . htmlspecialchars($mat['purchase_price_iqd']) . '"
            data-pieces_per_carton="' . htmlspecialchars($mat['pieces_per_carton']) . '"
            data-buckets_per_barrel="' . htmlspecialchars($mat['buckets_per_barrel']) . '"
            data-liters_per_bucket="' . htmlspecialchars($mat['liters_per_bucket']) . '"
            data-liters_per_barrel="' . htmlspecialchars($mat['liters_per_barrel']) . '"
            aria-label="نوێکردنەوە"><i class="bi bi-pencil"></i></button> ';
    echo '<button class="btn btn-sm btn-danger delete-btn" data-id="' . $mat['id'] . '" aria-label="سڕینەوە"><i class="bi bi-trash"></i></button>';
    echo '</td>';
    echo '</tr>';
}
