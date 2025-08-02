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
    $quantity = floatval($_POST['quantity'] ?? 0);
    $currency_type = $_POST['currency_type'] ?? 'دینار';
    $purchase_price_usd = floatval($_POST['purchase_price_usd'] ?? 0);
    $purchase_price_iqd = floatval($_POST['purchase_price_iqd'] ?? 0);

    // Log parsed variables for debugging
    error_log("Parsed vars: name='$name', quantity='$quantity', currency_type='$currency_type', purchase_price_usd='$purchase_price_usd', purchase_price_iqd='$purchase_price_iqd'");

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
    $checkTable = $pdo->query("SHOW TABLES LIKE 'list_materials'");
    if ($checkTable->rowCount() == 0) {
        error_log('Table list_materials does not exist');
        throw new Exception("خشتەی ماددەکان بوونی نییە");
    }

    // Check for duplicate material name
    $stmt = $pdo->prepare('SELECT id FROM list_materials WHERE name = ?');
    $stmt->execute([$name]);
    if ($stmt->fetch()) {
        error_log('Duplicate material name found: ' . $name);
        echo json_encode(['success' => false, 'message' => 'ئەم ناوی ماددە پێشتر تۆمارکراوە!']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO list_materials (name, quantity, currency_type, purchase_price_usd, purchase_price_iqd) VALUES (?, ?, ?, ?, ?)");
    $result = $stmt->execute([$name, $quantity, $currency_type, $purchase_price_usd, $purchase_price_iqd]);
    
    if ($result) {
        error_log('Material successfully added: Name=' . $name . ', Quantity=' . $quantity . ', Currency=' . $currency_type);
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
