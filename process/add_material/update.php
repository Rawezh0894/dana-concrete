<?php
session_start();
// Only log errors, don't display them in JSON response
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../php-error.log');

require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

// Log session and POST data for debugging
error_log('SESSION: ' . print_r($_SESSION, true));
error_log('add_material/update.php POST: ' . print_r($_POST, true));

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    error_log('User not logged in for material update');
    echo json_encode(['success' => false, 'message' => 'سێشن نییە! تکایە بچۆ ژوورەوە.']);
    exit;
}

if (!hasPermission('edit_material')) {
    error_log('Permission denied for user: ' . $_SESSION['user_id'] . ' to edit material');
    echo json_encode(['success' => false, 'message' => 'ڕێگەت پێنەدراوە!']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log('Invalid request method: ' . $_SERVER['REQUEST_METHOD']);
    echo json_encode(['success' => false, 'message' => 'تەنها POST ڕێگەپێدراوە']);
    exit;
}

try {
    $id = intval($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $unit_type = $_POST['unit_type'] ?? 'piece';
    $quantity = floatval($_POST['quantity'] ?? 0);
    $currency_type = $_POST['currency_type'] ?? 'دینار';
    $purchase_price_usd = floatval($_POST['purchase_price_usd'] ?? 0);
    $purchase_price_iqd = floatval($_POST['purchase_price_iqd'] ?? 0);
    
    // Unit-specific fields
    $pieces_per_carton = null;
    $bags_per_barrel = null;
    $liters_per_bag = null;
    $liters_per_barrel = null;
    $price_per_piece = 0;
    $price_per_liter = 0;

    // Validate unit type
    if (!in_array($unit_type, ['carton', 'piece', 'barrel', 'bag', 'liter'])) {
        error_log('Invalid unit type: ' . $unit_type);
        echo json_encode(['success' => false, 'message' => 'جۆری یەکە نادروستە!']);
        exit;
    }

    // Process unit-specific fields and calculate unit prices
    switch ($unit_type) {
        case 'carton':
            $pieces_per_carton = intval($_POST['pieces_per_carton'] ?? 1);
            if ($pieces_per_carton <= 0) {
                echo json_encode(['success' => false, 'message' => 'ژمارەی دانە لە کارتۆن دەبێت لە ١ زیاتر بێت!']);
                exit;
            }
            // Calculate price per piece
            if ($currency_type === 'دۆلار' && $purchase_price_usd > 0) {
                $price_per_piece = $purchase_price_usd / $pieces_per_carton;
            } elseif ($currency_type === 'دینار' && $purchase_price_iqd > 0) {
                $price_per_piece = $purchase_price_iqd / $pieces_per_carton;
            }
            break;
            
        case 'barrel':
            $bags_per_barrel = intval($_POST['bags_per_barrel'] ?? 1);
            $liters_per_bag = floatval($_POST['liters_per_bag'] ?? 1);
            if ($bags_per_barrel <= 0 || $liters_per_bag <= 0) {
                echo json_encode(['success' => false, 'message' => 'ژمارەی دەبە و لیتر دەبێت لە ٠ زیاتر بێت!']);
                exit;
            }
            $liters_per_barrel = $bags_per_barrel * $liters_per_bag;
            // Calculate price per liter
            if ($currency_type === 'دۆلار' && $purchase_price_usd > 0) {
                $price_per_liter = $purchase_price_usd / $liters_per_barrel;
            } elseif ($currency_type === 'دینار' && $purchase_price_iqd > 0) {
                $price_per_liter = $purchase_price_iqd / $liters_per_barrel;
            }
            break;
            
        case 'bag':
            $liters_per_bag = floatval($_POST['liters_per_bag_single'] ?? 1);
            if ($liters_per_bag <= 0) {
                echo json_encode(['success' => false, 'message' => 'ژمارەی لیتر لە دەبە دەبێت لە ٠ زیاتر بێت!']);
                exit;
            }
            // Calculate price per liter
            if ($currency_type === 'دۆلار' && $purchase_price_usd > 0) {
                $price_per_liter = $purchase_price_usd / $liters_per_bag;
            } elseif ($currency_type === 'دینار' && $purchase_price_iqd > 0) {
                $price_per_liter = $purchase_price_iqd / $liters_per_bag;
            }
            break;
            
        case 'piece':
        case 'liter':
            // For piece and liter, the price is already per unit
            if ($currency_type === 'دۆلار' && $purchase_price_usd > 0) {
                $price_per_piece = $purchase_price_usd;
                $price_per_liter = $purchase_price_usd;
            } elseif ($currency_type === 'دینار' && $purchase_price_iqd > 0) {
                $price_per_piece = $purchase_price_iqd;
                $price_per_liter = $purchase_price_iqd;
            }
            break;
    }

    // Log parsed variables for debugging
    error_log("Parsed vars: id='$id', name='$name', unit_type='$unit_type', quantity='$quantity', currency_type='$currency_type', purchase_price_usd='$purchase_price_usd', purchase_price_iqd='$purchase_price_iqd'");

    // Validate required fields
    if ($id <= 0) {
        error_log('Invalid material ID: ' . $id);
        echo json_encode(['success' => false, 'message' => 'ناسنامەی ماددە پێویستە!']);
        exit;
    }

    if (empty($name)) {
        error_log('Material name is empty');
        echo json_encode(['success' => false, 'message' => 'ناوی ماددە پێویستە!']);
        exit;
    }

    if (!in_array($currency_type, ['دینار', 'دۆلار'])) {
        error_log('Invalid currency type: ' . $currency_type);
        echo json_encode(['success' => false, 'message' => 'جۆری دراو نادروستە!']);
        exit;
    }

    // Check if material exists
    $checkStmt = $pdo->prepare('SELECT id, name FROM list_materials WHERE id = ?');
    $checkStmt->execute([$id]);
    $existingMaterial = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$existingMaterial) {
        error_log('Material not found: ID=' . $id);
        echo json_encode(['success' => false, 'message' => 'ماددە نەدۆزرایەوە!']);
        exit;
    }

    // Update with new unit system fields
    $stmt = $pdo->prepare("UPDATE list_materials SET 
        name=?, unit_type=?, quantity=?, currency_type=?, purchase_price_usd=?, purchase_price_iqd=?,
        pieces_per_carton=?, bags_per_barrel=?, liters_per_bag=?, liters_per_barrel=?,
        price_per_piece=?, price_per_liter=? 
        WHERE id=?");
    
    if ($stmt->execute([
        $name, $unit_type, $quantity, $currency_type, $purchase_price_usd, $purchase_price_iqd,
        $pieces_per_carton, $bags_per_barrel, $liters_per_bag, $liters_per_barrel,
        $price_per_piece, $price_per_liter, $id
    ])) {
        error_log('Material successfully updated: ID=' . $id . ', Name=' . $name . ', UnitType=' . $unit_type);
        echo json_encode(['success' => true, 'message' => 'ماددە بەسەرکەوتوویی نوێکرایەوە!']);
    } else {
        error_log('Failed to update material: ID=' . $id);
        echo json_encode(['success' => false, 'message' => 'هەڵە لە نوێکردنەوە!']);
    }

} catch (PDOException $e) {
    error_log('PDOException in add_material/update.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵەی داتابەیس: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log('Exception in add_material/update.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵەی سیستەم: ' . $e->getMessage()]);
}
