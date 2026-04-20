<?php
require_once '../../config/db_conected.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'msg' => 'Invalid request method']);
    exit;
}

try {
    $name = $_POST['name'] ?? '';
    $category = $_POST['category'] ?? 'Other';
    $unit = $_POST['unit'] ?? 'pcs';
    $secondary_unit = $_POST['secondary_unit'] ?? null;
    $conversion_factor = $_POST['conversion_factor'] ?? 1;

    if (empty($name)) throw new Exception("Item name is required");

    $stmt = $pdo->prepare("INSERT INTO inv_items (name, category, unit, secondary_unit, conversion_factor) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$name, $category, $unit, empty($secondary_unit) ? null : $secondary_unit, $conversion_factor]);
    
    echo json_encode(['success' => true, 'msg' => 'Item added successfully', 'id' => $pdo->lastInsertId()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
}
?>
