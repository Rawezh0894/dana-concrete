<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !hasPermission('edit_material')) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Access denied']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

try {
    // Validate required fields
    $requiredFields = ['id', 'name', 'quantity', 'currency_type'];
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            echo json_encode(['status' => 'error', 'message' => "فیلدی $field پێویستە"]);
            exit;
        }
    }

    $id = (int)$_POST['id'];
    $name = trim($_POST['name']);
    $quantity = (float)$_POST['quantity'];
    $currencyType = $_POST['currency_type'];
    $purchasePriceUsd = (float)($_POST['purchase_price_usd'] ?? 0);
    $purchasePriceIqd = (float)($_POST['purchase_price_iqd'] ?? 0);

    // Validate currency type
    $validCurrencyTypes = ['دینار', 'دۆلار'];
    if (!in_array($currencyType, $validCurrencyTypes)) {
        echo json_encode(['status' => 'error', 'message' => 'جۆری دراو نادروستە']);
        exit;
    }

    // Validate quantity
    if ($quantity < 0) {
        echo json_encode(['status' => 'error', 'message' => 'بڕی بەردەست دەبێت لە سفر زیاتر بێت']);
        exit;
    }

    // Check if material exists
    $stmt = $pdo->prepare("SELECT id FROM list_materials WHERE id = ?");
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'کاڵا نەدۆزرایەوە']);
        exit;
    }

    // Check if material name already exists (excluding current material)
    $stmt = $pdo->prepare("SELECT id FROM list_materials WHERE name = ? AND id != ?");
    $stmt->execute([$name, $id]);
    if ($stmt->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'ناوی کاڵا هەیە، تکایە ناوێکی تر هەڵبژێرە']);
        exit;
    }

    // Update material
    $stmt = $pdo->prepare("
        UPDATE list_materials 
        SET name = ?, quantity = ?, currency_type = ?, purchase_price_usd = ?, purchase_price_iqd = ?
        WHERE id = ?
    ");
    
    $stmt->execute([
        $name,
        $quantity,
        $currencyType,
        $purchasePriceUsd,
        $purchasePriceIqd,
        $id
    ]);

    echo json_encode([
        'status' => 'success',
        'message' => "کاڵای '$name' بە سەرکەوتوویی نوێکرایەوە"
    ]);

} catch (Exception $e) {
    error_log("Error updating material: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'هەڵە لە نوێکردنەوەی کاڵا: ' . $e->getMessage()
    ]);
}
?>
