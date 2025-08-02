<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !hasPermission('delete_material')) {
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
    if (empty($_POST['id'])) {
        echo json_encode(['status' => 'error', 'message' => 'ID پێویستە']);
        exit;
    }

    $id = (int)$_POST['id'];

    // Check if material exists
    $stmt = $pdo->prepare("SELECT id, name FROM list_materials WHERE id = ?");
    $stmt->execute([$id]);
    $material = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$material) {
        echo json_encode(['status' => 'error', 'message' => 'کاڵا نەدۆزرایەوە']);
        exit;
    }

    // Check if material is being used in other tables
    // Check purchase_materials table
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM purchase_materials WHERE material_id = ?");
    $stmt->execute([$id]);
    $purchaseCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    if ($purchaseCount > 0) {
        echo json_encode(['status' => 'error', 'message' => 'ناتوانرێت کاڵا بسڕدرێتەوە، لە کڕینەکان بەکارهاتووە']);
        exit;
    }

    // Check other_expenses table
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM other_expenses WHERE material_id = ?");
    $stmt->execute([$id]);
    $expenseCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    if ($expenseCount > 0) {
        echo json_encode(['status' => 'error', 'message' => 'ناتوانرێت کاڵا بسڕدرێتەوە، لە خەرجییەکان بەکارهاتووە']);
        exit;
    }

    // Delete material
    $stmt = $pdo->prepare("DELETE FROM list_materials WHERE id = ?");
    $stmt->execute([$id]);

    echo json_encode([
        'status' => 'success',
        'message' => "کاڵای '{$material['name']}' بە سەرکەوتوویی سڕایەوە"
    ]);

} catch (Exception $e) {
    error_log("Error deleting material: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'هەڵە لە سڕینەوەی کاڵا: ' . $e->getMessage()
    ]);
}
?>
