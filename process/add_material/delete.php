<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !hasPermission('delete_material')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$materialId = (int)($_POST['id'] ?? 0);

if (!$materialId) {
    echo json_encode(['success' => false, 'message' => 'IDی کاڵا پێویستە']);
    exit;
}

try {
    // Check if material exists and get its details
    $stmt = $pdo->prepare("
        SELECT wm.name_ku, wm.name, wi.quantity, wi.available_quantity
        FROM warehouse_materials wm
        LEFT JOIN warehouse_inventory wi ON wm.id = wi.material_id
        WHERE wm.id = ? AND wm.is_active = 1
    ");
    $stmt->execute([$materialId]);
    $material = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$material) {
        echo json_encode(['success' => false, 'message' => 'کاڵا نەدۆزرایەوە']);
        exit;
    }

    // Check if material has stock
    if (($material['available_quantity'] ?? 0) > 0) {
        echo json_encode([
            'success' => false, 
            'message' => 'ناتوانرێت کاڵا بسڕدرێتەوە لەبەر ئەوەی ستۆکی هەیە (' . number_format($material['available_quantity'], 2) . ')'
        ]);
        exit;
    }

    // Check if material has any transactions
    $stmt = $pdo->prepare("SELECT COUNT(*) as transaction_count FROM warehouse_transactions WHERE material_id = ?");
    $stmt->execute([$materialId]);
    $transactionCount = $stmt->fetch(PDO::FETCH_ASSOC)['transaction_count'];

    if ($transactionCount > 0) {
        echo json_encode([
            'success' => false, 
            'message' => 'ناتوانرێت کاڵا بسڕدرێتەوە لەبەر ئەوەی کرداری هەیە (' . $transactionCount . ' کردار)'
        ]);
        exit;
    }

    // Begin transaction
    $pdo->beginTransaction();

    try {
        // Soft delete the material (set is_active = 0)
        $stmt = $pdo->prepare("UPDATE warehouse_materials SET is_active = 0 WHERE id = ?");
        $stmt->execute([$materialId]);

        // Delete inventory record
        $stmt = $pdo->prepare("DELETE FROM warehouse_inventory WHERE material_id = ?");
        $stmt->execute([$materialId]);

        // Log the activity
        $activityDescription = "کاڵا سڕایەوە: {$material['name_ku']}";
        
        // Use the existing log function if available
        if (function_exists('log_user_activity')) {
            log_user_activity(
                $_SESSION['user_id'],
                $_SESSION['username'] ?? 'Unknown',
                'delete',
                'warehouse_materials',
                $activityDescription,
                $materialId,
                'warehouse_materials',
                json_encode([
                    'name_ku' => $material['name_ku'],
                    'name' => $material['name'],
                    'quantity' => $material['quantity'],
                    'available_quantity' => $material['available_quantity']
                ]),
                null,
                $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
            );
        }

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => "کاڵای '{$material['name_ku']}' بە سەرکەوتوویی سڕایەوە"
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    error_log("Error deleting warehouse material: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'هەڵە لە سڕینەوەی کاڵا: ' . $e->getMessage()
    ]);
}
?>
