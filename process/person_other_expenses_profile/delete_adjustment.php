<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'msg' => 'Unauthorized']);
    exit;
}

if (!hasPermission('view_person_other_expenses_profile')) {
    echo json_encode(['success' => false, 'msg' => 'ڕێگە پێنەدراوە!']);
    exit;
}

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;

if (!$id) {
    echo json_encode(['success' => false, 'msg' => 'ID missing']);
    exit;
}

try {
    // Get adjustment info for notification before deleting
    $stmt = $pdo->prepare("SELECT person_id FROM person_other_expenses_adjustments WHERE id = ?");
    $stmt->execute([$id]);
    $adj = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$adj) {
        echo json_encode(['success' => false, 'msg' => 'ڕێکخستنەوە نەدۆزرایەوە']);
        exit;
    }

    $delete = $pdo->prepare("DELETE FROM person_other_expenses_adjustments WHERE id = ?");
    if ($delete->execute([$id])) {
        
        require_once __DIR__ . '/../../includes/notify.php';
        notify(
            'delete',
            'person_other_expenses_adjustments',
            $id,
            'سڕینەوەی ڕێکخستنەوەی قەرز (کەس: ' . $adj['person_id'] . ')'
        );

        echo json_encode(['success' => true, 'msg' => 'ڕێکخستنەوە بەسەرکەوتوویی سڕایەوە']);
    } else {
        echo json_encode(['success' => false, 'msg' => 'هەڵە لە سڕینەوەی ڕێکخستنەوە']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
}
