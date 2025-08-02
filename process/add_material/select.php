<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

if (!isset($_SESSION['user_id']) || !hasPermission('view_materials')) {
    http_response_code(403);
    echo '<tr><td colspan="7" style="text-align:center">توانای دەست گەیشتنت نییە</td></tr>';
    exit;
}

try {
    $materials = $pdo->query("SELECT id, name, quantity, currency_type, purchase_price_usd, purchase_price_iqd FROM list_materials ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($materials) === 0) {
        echo '<tr><td colspan="7" style="text-align:center">هیچ داتایەک نییە</td></tr>';
    } else {
        foreach ($materials as $i => $mat): ?>
            <tr>
                <td><?= $i+1 ?></td>
                <td><?= htmlspecialchars($mat['name']) ?></td>
                <td><?= htmlspecialchars($mat['quantity']) ?></td>
                <td><?= htmlspecialchars($mat['currency_type']) ?></td>
                <td><?= htmlspecialchars($mat['purchase_price_usd']) ?></td>
                <td><?= htmlspecialchars($mat['purchase_price_iqd']) ?></td>
                <td>
                    <?php if (hasPermission('edit_material')): ?>
                    <button class="btn btn-sm btn-primary edit-btn" 
                            data-id="<?= $mat['id'] ?>" 
                            data-name="<?= htmlspecialchars($mat['name']) ?>" 
                            data-quantity="<?= htmlspecialchars($mat['quantity']) ?>" 
                            data-currency_type="<?= htmlspecialchars($mat['currency_type']) ?>" 
                            data-purchase_price_usd="<?= htmlspecialchars($mat['purchase_price_usd']) ?>" 
                            data-purchase_price_iqd="<?= htmlspecialchars($mat['purchase_price_iqd']) ?>" 
                            aria-label="نوێکردنەوە">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <?php endif; ?>
                    <?php if (hasPermission('delete_material')): ?>
                    <button class="btn btn-sm btn-danger delete-btn" 
                            data-id="<?= $mat['id'] ?>" 
                            aria-label="سڕینەوە">
                        <i class="bi bi-trash"></i>
                    </button>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach;
    }
} catch (Exception $e) {
    error_log("Error selecting materials: " . $e->getMessage());
    echo '<tr><td colspan="7" style="text-align:center">هەڵە لە وەرگرتنی داتا</td></tr>';
}
?>
