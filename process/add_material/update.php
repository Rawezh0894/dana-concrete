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
    $quantity = floatval($_POST['quantity'] ?? 0);
    $currency_type = $_POST['currency_type'] ?? 'دینار';
    $purchase_price_usd = floatval($_POST['purchase_price_usd'] ?? 0);
    $purchase_price_iqd = floatval($_POST['purchase_price_iqd'] ?? 0);

    // Log parsed variables for debugging
    error_log("Parsed vars: id='$id', name='$name', quantity='$quantity', currency_type='$currency_type', purchase_price_usd='$purchase_price_usd', purchase_price_iqd='$purchase_price_iqd'");

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

    $stmt = $pdo->prepare("UPDATE list_materials SET name=?, quantity=?, currency_type=?, purchase_price_usd=?, purchase_price_iqd=? WHERE id=?");
    if ($stmt->execute([$name, $quantity, $currency_type, $purchase_price_usd, $purchase_price_iqd, $id])) {
        error_log('Material successfully updated: ID=' . $id . ', Name=' . $name);
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
