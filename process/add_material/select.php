<?php
session_start();
// Only log errors, don't display them in JSON response
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../php-error.log');

require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

// Log session data for debugging
error_log('SESSION: ' . print_r($_SESSION, true));

if (!isset($_SESSION['user_id'])) {
    error_log('User not logged in for materials retrieval');
    echo '<tr><td colspan="7" style="text-align:center">سێشن نییە! تکایە بچۆ ژوورەوە.</td></tr>';
    exit;
}

if (!hasPermission('view_material')) {
    error_log('Permission denied for user: ' . $_SESSION['user_id'] . ' to view materials');
    echo '<tr><td colspan="7" style="text-align:center">ڕێگەت پێنەدراوە!</td></tr>';
    exit;
}

try {
    $materials = $pdo->query("SELECT id, name, quantity, currency_type, purchase_price_usd, purchase_price_iqd FROM list_materials ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
    
    error_log('Materials retrieved successfully: Count=' . count($materials));
    
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
    
} catch (PDOException $e) {
    error_log('PDOException in add_material/select.php: ' . $e->getMessage());
    echo '<tr><td colspan="7" style="text-align:center">هەڵەی داتابەیس: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
} catch (Exception $e) {
    error_log('Exception in add_material/select.php: ' . $e->getMessage());
    echo '<tr><td colspan="7" style="text-align:center">هەڵەی سیستەم: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
}
