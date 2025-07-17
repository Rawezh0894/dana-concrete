<?php
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
header('Content-Type: application/json');
if (!hasPermission('delete_other_expenses')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'ڕێگە پێنەدراو']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    if (!$id) {
        echo json_encode(['success' => false, 'msg' => 'ID پێویستە']);
        exit;
    }
    $stmt = $pdo->prepare('DELETE FROM other_expenses WHERE id = ?');
    // Get person_id, remaining_usd, remaining_iqd before delete
    $info = $pdo->prepare('SELECT person_id, remaining_usd, remaining_iqd FROM other_expenses WHERE id = ?');
    $info->execute([$id]);
    $row = $info->fetch(PDO::FETCH_ASSOC);
    $ok = $stmt->execute([$id]);
    if ($ok) {
        if ($row && $row['person_id']) {
            $update = $pdo->prepare('UPDATE other_expense_persons SET expense_usd = expense_usd - ?, expense_iqd = expense_iqd - ? WHERE id = ?');
            $update->execute([$row['remaining_usd'], $row['remaining_iqd'], $row['person_id']]);
        }
        require_once __DIR__ . '/../../includes/notify.php';
        notify('delete', 'other_expenses', $id, 'خەرجی تر سڕایەوە (ID: ' . $id . ')');
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'msg' => 'هەڵە لە سڕینەوە']);
    }
    exit;
}
echo json_encode(['success' => false, 'msg' => 'POST تەنها ڕێگەپێدراوە']);
