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
    // Updated query to use the new unit-specific inventory system
    $materials = $pdo->query("
        SELECT 
            im.id, 
            im.name, 
            im.currency_type, 
            im.purchase_price_usd, 
            im.purchase_price_iqd, 
            im.unit_type, 
            im.pieces_per_carton, 
            im.bags_per_barrel, 
            im.liters_per_bag, 
            im.liters_per_barrel, 
            im.price_per_piece, 
            im.price_per_liter, 
            im.price_per_bag,
            -- Get quantities by unit type
            COALESCE(carton_qty.quantity, 0) as carton_quantity,
            COALESCE(piece_qty.quantity, 0) as piece_quantity,
            COALESCE(barrel_qty.quantity, 0) as barrel_quantity,
            COALESCE(bag_qty.quantity, 0) as bag_quantity,
            COALESCE(liter_qty.quantity, 0) as liter_quantity,
            -- Get total quantity across all units
            COALESCE(carton_qty.quantity, 0) + COALESCE(piece_qty.quantity, 0) + 
            COALESCE(barrel_qty.quantity, 0) + COALESCE(bag_qty.quantity, 0) + 
            COALESCE(liter_qty.quantity, 0) as total_quantity
        FROM inventory_materials im
        LEFT JOIN inventory_by_unit carton_qty ON im.id = carton_qty.material_id AND carton_qty.unit_type = 'carton'
        LEFT JOIN inventory_by_unit piece_qty ON im.id = piece_qty.material_id AND piece_qty.unit_type = 'piece'
        LEFT JOIN inventory_by_unit barrel_qty ON im.id = barrel_qty.material_id AND barrel_qty.unit_type = 'barrel'
        LEFT JOIN inventory_by_unit bag_qty ON im.id = bag_qty.material_id AND bag_qty.unit_type = 'bag'
        LEFT JOIN inventory_by_unit liter_qty ON im.id = liter_qty.material_id AND liter_qty.unit_type = 'liter'
        ORDER BY im.id DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    error_log('Materials retrieved successfully: Count=' . count($materials));
    
    if (count($materials) === 0) {
        echo '<tr><td colspan="10" style="text-align:center">هیچ داتایەک نییە</td></tr>';
        exit;
    }
    
    foreach ($materials as $i => $mat) {
        // Create unit type display text
        $unitTypeText = '';
        switch($mat['unit_type']) {
            case 'carton':
                $unitTypeText = 'کارتۆن (' . $mat['pieces_per_carton'] . ' دانە)';
                break;
            case 'piece':
                $unitTypeText = 'دانە';
                break;
            case 'barrel':
                $unitTypeText = 'بەرمیل (' . $mat['bags_per_barrel'] . ' دەبە × ' . $mat['liters_per_bag'] . ' لیتر)';
                break;
            case 'bag':
                $unitTypeText = 'دەبە (' . $mat['liters_per_bag'] . ' لیتر)';
                break;
            case 'liter':
                $unitTypeText = 'لیتر';
                break;
        }
        
        // Create quantity display text showing unit-specific quantities
        $quantityDisplay = '';
        $quantities = [];
        
        if ($mat['carton_quantity'] > 0) {
            $quantities[] = $mat['carton_quantity'] . ' کارتۆن';
        }
        if ($mat['piece_quantity'] > 0) {
            $quantities[] = $mat['piece_quantity'] . ' دانە';
        }
        if ($mat['barrel_quantity'] > 0) {
            $quantities[] = $mat['barrel_quantity'] . ' بەرمیل';
        }
        if ($mat['bag_quantity'] > 0) {
            $quantities[] = $mat['bag_quantity'] . ' دەبە';
        }
        if ($mat['liter_quantity'] > 0) {
            $quantities[] = $mat['liter_quantity'] . ' لیتر';
        }
        
        if (empty($quantities)) {
            $quantityDisplay = '0';
        } else {
            $quantityDisplay = implode(' + ', $quantities);
        }
        
        // Create unit price display text
        $unitPrice = '';
        if ($mat['unit_type'] == 'carton' && $mat['price_per_piece']) {
            $unitPrice = $mat['price_per_piece'] . ' دۆلار/دانە';
        } elseif ($mat['unit_type'] == 'barrel') {
            if ($mat['price_per_bag']) {
                $unitPrice = $mat['price_per_bag'] . ' دۆلار/دەبە';
            } elseif ($mat['price_per_liter']) {
                $unitPrice = $mat['price_per_liter'] . ' دۆلار/لیتر';
            }
        } elseif ($mat['unit_type'] == 'bag' && $mat['price_per_liter']) {
            $unitPrice = $mat['price_per_liter'] . ' دۆلار/لیتر';
        } elseif ($mat['unit_type'] == 'piece' || $mat['unit_type'] == 'liter') {
            $unitPrice = $mat['purchase_price_usd'] . ' دۆلار/یەکە';
        }
        
        echo '<tr>';
        echo '<td>' . ($i+1) . '</td>';
        echo '<td>' . htmlspecialchars($mat['name']) . '</td>';
        echo '<td>' . htmlspecialchars($unitTypeText) . '</td>';
        echo '<td>' . htmlspecialchars($quantityDisplay) . '</td>';
        echo '<td>' . htmlspecialchars($mat['currency_type']) . '</td>';
        echo '<td>' . htmlspecialchars($mat['purchase_price_usd']) . '</td>';
        echo '<td>' . htmlspecialchars($mat['purchase_price_iqd']) . '</td>';
        echo '<td>' . htmlspecialchars($unitPrice) . '</td>';
        
        // Create bag price display text
        $bagPrice = '';
        if ($mat['unit_type'] == 'barrel' && $mat['price_per_bag']) {
            $bagPrice = $mat['price_per_bag'] . ' دۆلار/دەبە';
        } elseif ($mat['unit_type'] == 'bag' && $mat['price_per_bag']) {
            $bagPrice = $mat['price_per_bag'] . ' دۆلار/دەبە';
        }
        echo '<td>' . htmlspecialchars($bagPrice) . '</td>';
        echo '<td>';
        echo '<button class="btn btn-sm btn-primary edit-btn" data-id="' . $mat['id'] . '" data-name="' . htmlspecialchars($mat['name']) . '" data-quantity="' . htmlspecialchars($mat['total_quantity']) . '" data-currency_type="' . htmlspecialchars($mat['currency_type']) . '" data-purchase_price_usd="' . htmlspecialchars($mat['purchase_price_usd']) . '" data-purchase_price_iqd="' . htmlspecialchars($mat['purchase_price_iqd']) . '" data-unit_type="' . htmlspecialchars($mat['unit_type']) . '" data-pieces_per_carton="' . htmlspecialchars($mat['pieces_per_carton']) . '" data-bags_per_barrel="' . htmlspecialchars($mat['bags_per_barrel']) . '" data-liters_per_bag="' . htmlspecialchars($mat['liters_per_bag']) . '" data-liters_per_barrel="' . htmlspecialchars($mat['liters_per_barrel']) . '" data-price_per_piece="' . htmlspecialchars($mat['price_per_piece']) . '" data-price_per_liter="' . htmlspecialchars($mat['price_per_liter']) . '" data-price_per_bag="' . htmlspecialchars($mat['price_per_bag']) . '" aria-label="نوێکردنەوە"><i class="bi bi-pencil"></i></button> ';
        echo '<button class="btn btn-sm btn-danger delete-btn" data-id="' . $mat['id'] . '" aria-label="سڕینەوە"><i class="bi bi-trash"></i></button>';
        echo '</td>';
        echo '</tr>';
    }
    
} catch (PDOException $e) {
    error_log('PDOException in add_material/select.php: ' . $e->getMessage());
    echo '<tr><td colspan="10" style="text-align:center">هەڵەی داتابەیس: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
} catch (Exception $e) {
    error_log('Exception in add_material/select.php: ' . $e->getMessage());
    echo '<tr><td colspan="10" style="text-align:center">هەڵەی سیستەم: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
}
