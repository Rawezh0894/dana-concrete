<?php
session_start();
// Only log errors, don't display them in JSON response
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../php-error.log');

require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

// Log session and POST data for debugging
error_log('SESSION: ' . print_r($_SESSION, true));
error_log('add_material/add.php POST: ' . print_r($_POST, true));

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    error_log('User not logged in for material addition');
    echo json_encode(['success' => false, 'message' => 'سێشن نییە! تکایە بچۆ ژوورەوە.']);
    exit;
}

if (!hasPermission('add_material')) {
    error_log('Permission denied for user: ' . $_SESSION['user_id'] . ' to add material');
    echo json_encode(['success' => false, 'message' => 'ڕێگەت پێنەدراوە!']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log('Invalid request method: ' . $_SERVER['REQUEST_METHOD']);
    echo json_encode(['success' => false, 'message' => 'تەنها POST ڕێگەپێدراوە']);
    exit;
}

try {
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
    $price_per_bag = 0;

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
            // Calculate price per bag and price per liter
            if ($currency_type === 'دۆلار' && $purchase_price_usd > 0) {
                $price_per_bag = $purchase_price_usd / $bags_per_barrel;
                $price_per_liter = $purchase_price_usd / $liters_per_barrel;
            } elseif ($currency_type === 'دینار' && $purchase_price_iqd > 0) {
                $price_per_bag = $purchase_price_iqd / $bags_per_barrel;
                $price_per_liter = $purchase_price_iqd / $liters_per_barrel;
            }
            break;
            
        case 'bag':
            $liters_per_bag = floatval($_POST['liters_per_bag_single'] ?? 1);
            if ($liters_per_bag <= 0) {
                echo json_encode(['success' => false, 'message' => 'ژمارەی لیتر لە دەبە دەبێت لە ٠ زیاتر بێت!']);
                exit;
            }
            // Calculate price per liter and price per bag
            if ($currency_type === 'دۆلار' && $purchase_price_usd > 0) {
                $price_per_liter = $purchase_price_usd / $liters_per_bag;
                $price_per_bag = $purchase_price_usd;
            } elseif ($currency_type === 'دینار' && $purchase_price_iqd > 0) {
                $price_per_liter = $purchase_price_iqd / $liters_per_bag;
                $price_per_bag = $purchase_price_iqd;
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
    error_log("Parsed vars: name='$name', unit_type='$unit_type', quantity='$quantity', currency_type='$currency_type', purchase_price_usd='$purchase_price_usd', purchase_price_iqd='$purchase_price_iqd'");

    // Validate required fields
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

    // Check if table exists first
    $checkTable = $pdo->query("SHOW TABLES LIKE 'inventory_materials'");
    if ($checkTable->rowCount() == 0) {
        error_log('Table inventory_materials does not exist');
        throw new Exception("خشتەی ماددەکان بوونی نییە");
    }

    // Check for duplicate material name
    $stmt = $pdo->prepare('SELECT id FROM inventory_materials WHERE name = ?');
    $stmt->execute([$name]);
    if ($stmt->fetch()) {
        error_log('Duplicate material name found: ' . $name);
        echo json_encode(['success' => false, 'message' => 'ئەم ناوی ماددە پێشتر تۆمارکراوە!']);
        exit;
    }

    // Insert with new unit system fields
    $stmt = $pdo->prepare("INSERT INTO inventory_materials (
        name, unit_type, current_quantity, currency_type, purchase_price_usd, purchase_price_iqd,
        pieces_per_carton, bags_per_barrel, liters_per_bag, liters_per_barrel,
        price_per_piece, price_per_liter, price_per_bag
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $result = $stmt->execute([
        $name, $unit_type, $quantity, $currency_type, $purchase_price_usd, $purchase_price_iqd,
        $pieces_per_carton, $bags_per_barrel, $liters_per_bag, $liters_per_barrel,
        $price_per_piece, $price_per_liter, $price_per_bag
    ]);
    
    if ($result) {
        error_log('Material successfully added: Name=' . $name . ', UnitType=' . $unit_type . ', Quantity=' . $quantity . ', Currency=' . $currency_type);
        echo json_encode(['success' => true, 'message' => 'ماددە بەسەرکەوتوویی زیادکرا!']);
    } else {
        error_log('Failed to add material: Name=' . $name);
        echo json_encode(['success' => false, 'message' => 'هەڵە لە زیادکردن!']);
    }

} catch (PDOException $e) {
    error_log('PDOException in add_material/add.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵەی داتابەیس: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log('Exception in add_material/add.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵەی سیستەم: ' . $e->getMessage()]);
}
