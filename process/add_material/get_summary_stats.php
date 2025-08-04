<?php
session_start();
require_once '../../config/db_conected.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

try {
    // Get total materials count
    $totalMaterials = $pdo->query("SELECT COUNT(*) as count FROM list_materials")->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Get low stock materials count (quantity <= 10)
    $lowStockMaterials = $pdo->query("SELECT COUNT(*) as count FROM list_materials WHERE quantity <= 10")->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Get most used materials (from other expenses)
    $mostUsedQuery = $pdo->query("
        SELECT m.name, COUNT(*) as usage_count 
        FROM other_expenses oe 
        JOIN list_materials m ON oe.material_id = m.id 
        WHERE oe.expense_type = 'بەکارهێنانی کاڵای کۆگا'
        GROUP BY oe.material_id, m.name 
        ORDER BY usage_count DESC 
        LIMIT 1
    ");
    $mostUsedMaterials = $mostUsedQuery->fetch(PDO::FETCH_ASSOC);
    
    // Prepare response data
    $responseData = [
        'total_materials' => (int)$totalMaterials,
        'low_stock_materials' => (int)$lowStockMaterials,
        'most_used_count' => $mostUsedMaterials ? (int)$mostUsedMaterials['usage_count'] : 0,
        'most_used_name' => $mostUsedMaterials ? $mostUsedMaterials['name'] : 'هیچ'
    ];
    
    echo json_encode([
        'success' => true,
        'data' => $responseData
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'General error: ' . $e->getMessage()
    ]);
}
?> 