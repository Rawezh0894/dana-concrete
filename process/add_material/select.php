<?php
require_once '../../config/db_conected.php';
$materials = $pdo->query("SELECT id, name, quantity, currency_type, purchase_price_usd, purchase_price_iqd FROM list_materials")->fetchAll(PDO::FETCH_ASSOC);
if (count($materials) === 0) {
    echo '<tr><td colspan="7" style="text-align:center">هیچ داتایەک نییە</td></tr>';
    exit;
}
foreach ($materials as $i => $mat) {
    echo '<tr>';
    echo '<td>' . ($i+1) . '</td>';
    echo '<td>' . htmlspecialchars($mat['name']) . '</td>';
    echo '<td>' . htmlspecialchars($mat['quantity']) . '</td>';
    echo '<td>' . htmlspecialchars($mat['currency_type']) . '</td>';
    echo '<td>' . htmlspecialchars($mat['purchase_price_usd']) . '</td>';
    echo '<td>' . htmlspecialchars($mat['purchase_price_iqd']) . '</td>';
    echo '<td>';
    echo '<button class="btn btn-sm btn-primary edit-btn" data-id="' . $mat['id'] . '" data-name="' . htmlspecialchars($mat['name']) . '" data-quantity="' . htmlspecialchars($mat['quantity']) . '" data-currency_type="' . htmlspecialchars($mat['currency_type']) . '" data-purchase_price_usd="' . htmlspecialchars($mat['purchase_price_usd']) . '" data-purchase_price_iqd="' . htmlspecialchars($mat['purchase_price_iqd']) . '" aria-label="نوێکردنەوە"><i class="bi bi-pencil"></i></button> ';
    echo '<button class="btn btn-sm btn-danger delete-btn" data-id="' . $mat['id'] . '" aria-label="سڕینەوە"><i class="bi bi-trash"></i></button>';
    echo '</td>';
    echo '</tr>';
}
