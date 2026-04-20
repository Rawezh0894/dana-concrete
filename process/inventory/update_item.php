<?php
require_once '../../config/db_conected.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'msg' => 'Invalid request method']);
    exit;
}

try {
    $item_id = intval($_POST['item_id'] ?? 0);
    $name = $_POST['name'] ?? '';
    $category = $_POST['category'] ?? 'Other';
    $unit = $_POST['unit'] ?? '';
    $secondary_unit = $_POST['secondary_unit'] ?? null;
    $conversion_factor = $_POST['conversion_factor'] ?? 1;

    if ($item_id <= 0) throw new Exception("Invalid Item ID");
    if (empty($name)) throw new Exception("Item name is required");

    $stmt = $pdo->prepare("UPDATE inv_items SET name = ?, category = ?, unit = ?, secondary_unit = ?, conversion_factor = ? WHERE id = ?");
    $stmt->execute([
        $name, 
        $category, 
        $unit, 
        empty($secondary_unit) ? null : $secondary_unit, 
        $conversion_factor,
        $item_id
    ]);

    echo json_encode(['success' => true, 'msg' => 'زانیارییەکانی کاڵا بە سەرکەوتوویی نوێکرایەوە']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
}
?>
