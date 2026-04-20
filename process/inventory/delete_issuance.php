<?php
require_once '../../config/db_conected.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'msg' => 'Invalid request method']);
    exit;
}

try {
    $pdo->beginTransaction();

    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) throw new Exception("Invalid Issuance ID");

    // 1. Get info to revert stock
    $stmt = $pdo->prepare("SELECT item_id, qty FROM inv_issuance WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    if (!$row) throw new Exception("Issuance record not found");

    $item_id = $row['item_id'];
    $qty = floatval($row['qty']);

    // 2. Add back to stock
    $stmt_add = $pdo->prepare("UPDATE inv_stock SET current_qty = current_qty + ? WHERE item_id = ?");
    $stmt_add->execute([$qty, $item_id]);

    // 3. Delete record
    $pdo->prepare("DELETE FROM inv_issuance WHERE id = ?")->execute([$id]);

    $pdo->commit();
    echo json_encode(['success' => true, 'msg' => 'دەرکردنەکە بە سەرکەوتوویی سڕایەوە و بڕی کاڵاکە بۆ کۆگا گەڕێنرایەوە']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
}
?>
