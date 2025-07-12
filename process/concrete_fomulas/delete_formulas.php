<?php
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
header('Content-Type: application/json; charset=utf-8');

if (!hasPermission('delete_concrete_formulas')) {
    echo json_encode(['success' => false, 'message' => 'ڕێگە پێنەدراوە بۆ سڕینەوە.']);
    exit;
}

if (empty($_POST['id']) || !is_numeric($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'ناسنامەی هەڵە']);
    exit;
}
$id = (int)$_POST['id'];
try {
    $stmt = $pdo->prepare('DELETE FROM concrete_formulas WHERE id = ?');
    $stmt->execute([$id]);
    if ($stmt->rowCount()) {
        echo json_encode(['success' => true, 'message' => 'فۆرمولا سڕایەوە']);
    } else {
        echo json_encode(['success' => false, 'message' => 'فۆرمولا نەدۆزرایەوە']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'هەڵەیەک ڕووی دا: ' . $e->getMessage()]);
}
