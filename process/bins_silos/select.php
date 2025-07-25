<?php
require_once '../../config/db_conected.php';
$bins = $pdo->query("SELECT * FROM bins_silos")->fetchAll(PDO::FETCH_ASSOC);
if (count($bins) === 0) {
    echo '<tr><td colspan="8" style="text-align:center">هیچ داتایەک نییە</td></tr>';
    exit;
}
foreach ($bins as $i => $bin) {
    echo '<tr>';
    echo '<td>' . ($i+1) . '</td>';
    echo '<td>' . htmlspecialchars($bin['name']) . '</td>';
    echo '<td>' . htmlspecialchars($bin['type']) . '</td>';
    echo '<td>' . htmlspecialchars($bin['material_type']) . '</td>';
    echo '<td>' . number_format($bin['amount'], 2) . '</td>';
    echo '<td>' . number_format($bin['total_value'], 2) . '</td>';
    echo '<td>' . number_format($bin['average_price'], 2) . '</td>';
    echo '<td>';
    echo '<button class="btn btn-sm btn-primary edit-btn" data-id="' . $bin['id'] . '" data-name="' . htmlspecialchars($bin['name']) . '" data-type="' . htmlspecialchars($bin['type']) . '" data-material_type="' . htmlspecialchars($bin['material_type']) . '" data-amount="' . htmlspecialchars($bin['amount']) . '" data-total_value="' . htmlspecialchars($bin['total_value']) . '" data-average_price="' . htmlspecialchars($bin['average_price']) . '" aria-label="نوێکردنەوە"><i class="bi bi-pencil"></i></button>';
    echo '</td>';
    echo '</tr>';
}
