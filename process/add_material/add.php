<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !hasPermission('add_material')) {
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
    $requiredFields = ['name', 'quantity', 'currency_type'];
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            echo json_encode(['status' => 'error', 'message' => "فیلدی $field پێویستە"]);
            exit;
        }
    }

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

    // Check if material name already exists
    $stmt = $pdo->prepare("SELECT id FROM list_materials WHERE name = ?");
    $stmt->execute([$name]);
    if ($stmt->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'ناوی کاڵا هەیە، تکایە ناوێکی تر هەڵبژێرە']);
        exit;
    }

    // Insert new material
    $stmt = $pdo->prepare("
        INSERT INTO list_materials (
            name, quantity, currency_type, purchase_price_usd, purchase_price_iqd
        ) VALUES (?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $name,
        $quantity,
        $currencyType,
        $purchasePriceUsd,
        $purchasePriceIqd
    ]);

    $materialId = $pdo->lastInsertId();

    echo json_encode([
        'status' => 'success',
        'message' => "کاڵای '$name' بە سەرکەوتوویی زیادکرا",
        'material_id' => $materialId
    ]);

} catch (Exception $e) {
    error_log("Error adding material: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'هەڵە لە زیادکردنی کاڵا: ' . $e->getMessage()
    ]);
}
?>
